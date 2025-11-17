<?php
namespace KH_SMMA\Admin;

use WP_Query;

use function __;
use function absint;
use function apply_filters;
use function add_action;
use function add_menu_page;
use function add_query_arg;
use function admin_url;
use function check_admin_referer;
use function current_user_can;
use function esc_attr;
use function esc_html;
use function esc_html__;
use function esc_html_e;
use function esc_url;
use function get_post_meta;
use function get_posts;
use function get_the_title;
use function is_wp_error;
use function sanitize_text_field;
use function sanitize_textarea_field;
use function strtotime;
use function submit_button;
use function time;
use function update_post_meta;
use function wp_date;
use function wp_die;
use function wp_insert_post;
use function wp_nonce_field;
use function wp_safe_redirect;
use const DAY_IN_SECONDS;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use KH_SMMA\Services\TokenRepository;

class AdminInterface {
    /** @var TokenRepository */
    private $tokens;

    public function __construct( TokenRepository $tokens ) {
        $this->tokens = $tokens;
    }

    public function register() {
        add_action( 'admin_menu', array( $this, 'register_menu' ) );
        add_action( 'admin_post_kh_smma_connect_account', array( $this, 'handle_account_connect' ) );
        add_action( 'admin_post_kh_smma_schedule_post', array( $this, 'handle_schedule_post' ) );
    }

    public function register_menu() {
        add_menu_page(
            __( 'KH Social Manager', 'kh-smma' ),
            __( 'KH Social', 'kh-smma' ),
            'manage_options',
            'kh-smma-dashboard',
            array( $this, 'render_dashboard' ),
            'dashicons-share-alt2',
            27
        );
    }

    public function render_dashboard() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $accounts  = get_posts( array( 'post_type' => 'kh_smma_account', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
        $campaigns = get_posts( array( 'post_type' => 'kh_smma_campaign', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
        $queue     = get_posts( array( 'post_type' => 'kh_smma_schedule', 'numberposts' => 10, 'orderby' => 'date', 'order' => 'DESC' ) );
        $library_assets = apply_filters( 'kh_smma_marketing_assets', array() );
        ?>
        <div class="wrap kh-smma-admin">
            <h1><?php esc_html_e( 'KH Social Media Management & Automation', 'kh-smma' ); ?></h1>
            <p><?php esc_html_e( 'Connect accounts, schedule posts, and monitor queue health.', 'kh-smma' ); ?></p>

            <h2><?php esc_html_e( '1. Connect Social Account', 'kh-smma' ); ?></h2>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="kh-smma-form">
                <?php wp_nonce_field( 'kh_smma_connect_account' ); ?>
                <input type="hidden" name="action" value="kh_smma_connect_account" />
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="kh-smma-account-name"><?php esc_html_e( 'Account Label', 'kh-smma' ); ?></label></th>
                        <td><input type="text" id="kh-smma-account-name" name="account_name" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="kh-smma-provider"><?php esc_html_e( 'Provider', 'kh-smma' ); ?></label></th>
                        <td>
                            <select id="kh-smma-provider" name="provider" required>
                                <option value="manual">Manual Export</option>
                                <option value="meta">Meta (FB/Instagram)</option>
                                <option value="linkedin">LinkedIn</option>
                                <option value="twitter">X / Twitter</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="kh-smma-token"><?php esc_html_e( 'Access Token / Notes', 'kh-smma' ); ?></label></th>
                        <td><textarea id="kh-smma-token" name="token" class="large-text" rows="3" placeholder="Store API tokens or connection notes (stored encrypted when possible)"></textarea></td>
                    </tr>
                </table>
                <?php submit_button( __( 'Save Account', 'kh-smma' ) ); ?>
            </form>
            <?php if ( ! empty( $accounts ) ) : ?>
                <h3><?php esc_html_e( 'Connected Accounts', 'kh-smma' ); ?></h3>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Account', 'kh-smma' ); ?></th>
                            <th><?php esc_html_e( 'Provider', 'kh-smma' ); ?></th>
                            <th><?php esc_html_e( 'Status', 'kh-smma' ); ?></th>
                            <th><?php esc_html_e( 'Actions', 'kh-smma' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $accounts as $account ) :
                            $provider = get_post_meta( $account->ID, '_kh_smma_provider', true );
                            $status   = get_post_meta( $account->ID, '_kh_smma_status', true );
                            ?>
                            <tr>
                                <td><?php echo esc_html( $account->post_title ); ?></td>
                                <td><?php echo esc_html( ucfirst( $provider ?: 'manual' ) ); ?></td>
                                <td><span class="kh-smma-status kh-smma-status-<?php echo esc_attr( $status ); ?>"><?php echo esc_html( ucfirst( $status ?: 'disconnected' ) ); ?></span></td>
                                <td>
                                    <?php if ( 'manual' !== $provider ) : ?>
                                        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                                            <?php wp_nonce_field( 'kh_smma_oauth_start' ); ?>
                                            <input type="hidden" name="action" value="kh_smma_oauth_start" />
                                            <input type="hidden" name="account_id" value="<?php echo esc_attr( $account->ID ); ?>" />
                                            <input type="hidden" name="provider" value="<?php echo esc_attr( $provider ); ?>" />
                                            <?php submit_button( __( 'Reconnect', 'kh-smma' ), 'secondary', '', false ); ?>
                                        </form>
                                    <?php else : ?>
                                        <?php esc_html_e( 'Manual export only', 'kh-smma' ); ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <hr />

            <h2><?php esc_html_e( '2. Quick Schedule', 'kh-smma' ); ?></h2>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( 'kh_smma_schedule_post' ); ?>
                <input type="hidden" name="action" value="kh_smma_schedule_post" />
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Schedule Title', 'kh-smma' ); ?></th>
                        <td><input type="text" name="schedule_title" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Account', 'kh-smma' ); ?></th>
                        <td>
                            <select name="schedule_account" required>
                                <option value=""><?php esc_html_e( 'Select account', 'kh-smma' ); ?></option>
                                <?php foreach ( $accounts as $account ) : ?>
                                    <option value="<?php echo esc_attr( $account->ID ); ?>"><?php echo esc_html( $account->post_title ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Campaign', 'kh-smma' ); ?></th>
                        <td>
                            <select name="schedule_campaign">
                                <option value=""><?php esc_html_e( 'Optional campaign', 'kh-smma' ); ?></option>
                                <?php foreach ( $campaigns as $campaign ) : ?>
                                    <option value="<?php echo esc_attr( $campaign->ID ); ?>"><?php echo esc_html( $campaign->post_title ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Message', 'kh-smma' ); ?></th>
                        <td><textarea name="schedule_message" rows="4" class="large-text" required></textarea></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Marketing Asset', 'kh-smma' ); ?></th>
                        <td>
                            <select name="marketing_asset">
                                <option value=""><?php esc_html_e( 'None', 'kh-smma' ); ?></option>
                                <?php foreach ( $library_assets as $asset_id => $asset_label ) : ?>
                                    <option value="<?php echo esc_attr( $asset_id ); ?>"><?php echo esc_html( $asset_label ); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php esc_html_e( 'Pull pre-approved copy from Marketing Suite library (filter via kh_smma_marketing_assets).', 'kh-smma' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Scheduled Time', 'kh-smma' ); ?></th>
                        <td><input type="datetime-local" name="schedule_time" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Delivery Mode', 'kh-smma' ); ?></th>
                        <td>
                            <select name="delivery_mode">
                                <option value="auto"><?php esc_html_e( 'Auto publish via API', 'kh-smma' ); ?></option>
                                <option value="manual_export"><?php esc_html_e( 'Manual export queue', 'kh-smma' ); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php submit_button( __( 'Queue Post', 'kh-smma' ) ); ?>
            </form>

            <hr />

            <h2><?php esc_html_e( 'Queue Snapshot', 'kh-smma' ); ?></h2>
            <table class="widefat kh-smma-queue">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Scheduled For', 'kh-smma' ); ?></th>
                        <th><?php esc_html_e( 'Title', 'kh-smma' ); ?></th>
                        <th><?php esc_html_e( 'Account', 'kh-smma' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'kh-smma' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $queue ) ) : ?>
                        <tr>
                            <td colspan="4"><?php esc_html_e( 'No scheduled posts yet.', 'kh-smma' ); ?></td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ( $queue as $item ) :
                            $scheduled_at = get_post_meta( $item->ID, '_kh_smma_scheduled_at', true );
                            $status       = get_post_meta( $item->ID, '_kh_smma_schedule_status', true );
                            $account_id   = get_post_meta( $item->ID, '_kh_smma_account_id', true );
                            ?>
                            <tr>
                                <td><?php echo esc_html( $scheduled_at ? wp_date( 'Y-m-d H:i', (int) $scheduled_at ) : '—' ); ?></td>
                                <td><strong><?php echo esc_html( $item->post_title ); ?></strong></td>
                                <td><?php echo esc_html( $account_id ? get_the_title( $account_id ) : '—' ); ?></td>
                                <td><span class="kh-smma-status kh-smma-status-<?php echo esc_attr( $status ); ?>"><?php echo esc_html( ucfirst( $status ) ); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <h2><?php esc_html_e( 'Calendar (Next 7 Days)', 'kh-smma' ); ?></h2>
            <table class="widefat kh-smma-calendar">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Date', 'kh-smma' ); ?></th>
                        <th><?php esc_html_e( 'Scheduled Posts', 'kh-smma' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $this->get_calendar_slots() as $slot ) : ?>
                        <tr>
                            <td><?php echo esc_html( $slot['label'] ); ?></td>
                            <td><?php echo esc_html( $slot['count'] ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function handle_account_connect() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Insufficient permissions.', 'kh-smma' ) );
        }

        check_admin_referer( 'kh_smma_connect_account' );

        $account_name = sanitize_text_field( $_POST['account_name'] ?? '' );
        $provider     = sanitize_text_field( $_POST['provider'] ?? '' );
        $token        = sanitize_textarea_field( $_POST['token'] ?? '' );

        $allowed_providers = array( 'manual', 'meta', 'linkedin', 'twitter' );
        if ( ! in_array( $provider, $allowed_providers, true ) ) {
            $provider = 'manual';
        }

        $post_id = wp_insert_post( array(
            'post_type'   => 'kh_smma_account',
            'post_title'  => $account_name,
            'post_status' => 'publish',
        ) );

        if ( is_wp_error( $post_id ) ) {
            wp_die( esc_html( $post_id->get_error_message() ) );
        }

        update_post_meta( $post_id, '_kh_smma_provider', $provider );
        update_post_meta( $post_id, '_kh_smma_status', 'connected' );
        update_post_meta( $post_id, '_kh_smma_credentials', array(
            'notes' => $token,
        ) );

        if ( ! empty( $token ) ) {
            $token_id = $this->tokens->save_token( $post_id, array(
                'provider' => $provider,
                'token'    => $token,
            ) );
            update_post_meta( $post_id, '_kh_smma_token_id', $token_id );
        } elseif ( 'manual' !== $provider ) {
            // Flag account as requiring OAuth.
            update_post_meta( $post_id, '_kh_smma_status', 'oauth_required' );
        }

        wp_safe_redirect( add_query_arg( array( 'page' => 'kh-smma-dashboard', 'message' => 'account-connected' ), admin_url( 'admin.php' ) ) );
        exit;
    }

    public function handle_schedule_post() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Insufficient permissions.', 'kh-smma' ) );
        }

        check_admin_referer( 'kh_smma_schedule_post' );

        $title          = sanitize_text_field( $_POST['schedule_title'] ?? '' );
        $account_id     = absint( $_POST['schedule_account'] ?? 0 );
        $campaign_id    = absint( $_POST['schedule_campaign'] ?? 0 );
        $message        = sanitize_textarea_field( $_POST['schedule_message'] ?? '' );
        $time           = sanitize_text_field( $_POST['schedule_time'] ?? '' );
        $delivery       = sanitize_text_field( $_POST['delivery_mode'] ?? 'auto' );
        $marketing_asset = sanitize_text_field( $_POST['marketing_asset'] ?? '' );

        if ( empty( $account_id ) || empty( $time ) ) {
            wp_safe_redirect( add_query_arg( array( 'page' => 'kh-smma-dashboard', 'message' => 'missing-fields' ), admin_url( 'admin.php' ) ) );
            exit;
        }

        $timestamp = strtotime( $time );
        if ( ! $timestamp || $timestamp < time() ) {
            $timestamp = time();
        }

        if ( strlen( $message ) < 5 ) {
            wp_safe_redirect( add_query_arg( array( 'page' => 'kh-smma-dashboard', 'message' => 'message-too-short' ), admin_url( 'admin.php' ) ) );
            exit;
        }

        $allowed_delivery = array( 'auto', 'manual_export' );
        if ( ! in_array( $delivery, $allowed_delivery, true ) ) {
            $delivery = 'auto';
        }

        $asset_content = '';
        if ( $marketing_asset ) {
            $asset_content = apply_filters( 'kh_smma_resolve_asset_content', '', $marketing_asset );
        }

        $schedule_id = wp_insert_post( array(
            'post_type'   => 'kh_smma_schedule',
            'post_title'  => $title,
            'post_content'=> $message,
            'post_status' => 'publish',
        ) );

        if ( is_wp_error( $schedule_id ) ) {
            wp_die( esc_html( $schedule_id->get_error_message() ) );
        }

        update_post_meta( $schedule_id, '_kh_smma_account_id', $account_id );
        update_post_meta( $schedule_id, '_kh_smma_campaign_id', $campaign_id );
        update_post_meta( $schedule_id, '_kh_smma_payload', array(
            'message' => $message,
            'asset'   => $asset_content,
        ) );
        update_post_meta( $schedule_id, '_kh_smma_scheduled_at', $timestamp );
        update_post_meta( $schedule_id, '_kh_smma_delivery_mode', $delivery );
        update_post_meta( $schedule_id, '_kh_smma_schedule_status', 'pending' );

        wp_safe_redirect( add_query_arg( array( 'page' => 'kh-smma-dashboard', 'message' => 'scheduled' ), admin_url( 'admin.php' ) ) );
        exit;
    }

    private function get_calendar_slots() {
        $slots = array();
        $start = strtotime( 'today midnight' );

        for ( $i = 0; $i < 7; $i++ ) {
            $day_start = $start + ( DAY_IN_SECONDS * $i );
            $day_end   = $day_start + DAY_IN_SECONDS;

            $query = new WP_Query( array(
                'post_type'      => 'kh_smma_schedule',
                'post_status'    => 'publish',
                'posts_per_page' => 1,
                'fields'         => 'ids',
                'meta_query'     => array(
                    array(
                        'key'     => '_kh_smma_scheduled_at',
                        'value'   => array( $day_start, $day_end ),
                        'compare' => 'BETWEEN',
                        'type'    => 'NUMERIC',
                    ),
                ),
            ) );

            $count = (int) $query->found_posts;
            wp_reset_postdata();

            $slots[] = array(
                'label' => wp_date( 'l, M j', $day_start ),
                'count' => $count,
            );
        }

        return $slots;
    }
}
