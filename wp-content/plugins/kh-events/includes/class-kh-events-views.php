<?php
/**
 * Events Views Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class KH_Events_Views {

    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('kh_events_calendar', array($this, 'calendar_shortcode'));
        add_shortcode('kh_events_list', array($this, 'list_shortcode'));
        add_shortcode('kh_events_day', array($this, 'day_shortcode'));
    }

    public function enqueue_scripts() {
        wp_enqueue_style('kh-events-styles', KH_EVENTS_URL . 'assets/css/kh-events.css', array(), KH_EVENTS_VERSION);
        wp_enqueue_script('kh-events-scripts', KH_EVENTS_URL . 'assets/js/kh-events.js', array('jquery'), KH_EVENTS_VERSION, true);
        wp_localize_script('kh-events-scripts', 'kh_events_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
        ));
    }

    public function ajax_load_calendar() {
        $month = intval($_POST['month']);
        $year = intval($_POST['year']);
        $category = sanitize_text_field($_POST['category']);
        $tag = sanitize_text_field($_POST['tag']);

        ob_start();
        $this->render_calendar(array('month' => $month, 'year' => $year, 'category' => $category, 'tag' => $tag));
        $html = ob_get_clean();

        wp_send_json_success(array('html' => $html));
    }

    private function render_calendar($atts) {
        $month = intval($atts['month']);
        $year = intval($atts['year']);
        $category = $atts['category'];
        $tag = $atts['tag'];

        $first_day = mktime(0, 0, 0, $month, 1, $year);
        $days_in_month = date('t', $first_day);
        $day_of_week = date('w', $first_day);

        // Get events for this month
        $events = $this->get_events_for_month($month, $year, $category, $tag);

        // Navigation
        $prev_month = $month - 1;
        $prev_year = $year;
        if ($prev_month < 1) {
            $prev_month = 12;
            $prev_year--;
        }
        $next_month = $month + 1;
        $next_year = $year;
        if ($next_month > 12) {
            $next_month = 1;
            $next_year++;
        }

        ?>
        <div class="kh-events-calendar" data-category="<?php echo esc_attr($category); ?>" data-tag="<?php echo esc_attr($tag); ?>">
            <div class="kh-calendar-navigation">
                <a href="#" class="kh-nav-link" data-month="<?php echo $prev_month; ?>" data-year="<?php echo $prev_year; ?>">&laquo; <?php _e('Previous', 'kh-events'); ?></a>
                <h2><?php echo date('F Y', $first_day); ?></h2>
                <a href="#" class="kh-nav-link" data-month="<?php echo $next_month; ?>" data-year="<?php echo $next_year; ?>"><?php _e('Next', 'kh-events'); ?> &raquo;</a>
            </div>
            <table class="kh-calendar-table">
                <thead>
                    <tr>
                        <th><?php _e('Sun', 'kh-events'); ?></th>
                        <th><?php _e('Mon', 'kh-events'); ?></th>
                        <th><?php _e('Tue', 'kh-events'); ?></th>
                        <th><?php _e('Wed', 'kh-events'); ?></th>
                        <th><?php _e('Thu', 'kh-events'); ?></th>
                        <th><?php _e('Fri', 'kh-events'); ?></th>
                        <th><?php _e('Sat', 'kh-events'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $day = 1;
                    for ($week = 0; $week < 6; $week++) {
                        echo '<tr>';
                        for ($weekday = 0; $weekday < 7; $weekday++) {
                            if ($week == 0 && $weekday < $day_of_week) {
                                echo '<td class="kh-empty-cell"></td>';
                            } elseif ($day > $days_in_month) {
                                echo '<td class="kh-empty-cell"></td>';
                            } else {
                                $date_key = sprintf('%04d-%02d-%02d', $year, $month, $day);
                                $day_events = isset($events[$date_key]) ? $events[$date_key] : array();
                                echo '<td class="kh-day-cell">';
                                echo '<div class="kh-day-number">' . $day . '</div>';
                                foreach ($day_events as $event) {
                                    echo '<div class="kh-event-item"><a href="' . get_permalink($event->ID) . '">' . get_the_title($event->ID) . '</a></div>';
                                }
                                echo '</td>';
                                $day++;
                            }
                        }
                        echo '</tr>';
                        if ($day > $days_in_month) break;
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    private function get_events_for_month($month, $year, $category = '', $tag = '') {
        $args = array(
            'post_type' => 'kh_event',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_kh_event_start_date',
                    'value' => array($year . '-' . $month . '-01', $year . '-' . $month . '-31'),
                    'compare' => 'BETWEEN',
                    'type' => 'DATE'
                )
            )
        );

        $tax_query = array();
        if (!empty($category)) {
            $tax_query[] = array(
                'taxonomy' => 'kh_event_category',
                'field' => 'slug',
                'terms' => $category
            );
        }
        if (!empty($tag)) {
            $tax_query[] = array(
                'taxonomy' => 'kh_event_tag',
                'field' => 'slug',
                'terms' => $tag
            );
        }
        if (!empty($tax_query)) {
            $args['tax_query'] = $tax_query;
        }

        $events = get_posts($args);
        $events_by_date = array();

        foreach ($events as $event) {
            $start_date = get_post_meta($event->ID, '_kh_event_start_date', true);
            if ($start_date) {
                if (!isset($events_by_date[$start_date])) {
                    $events_by_date[$start_date] = array();
                }
                $events_by_date[$start_date][] = $event;
            }
        }

        return apply_filters('kh_get_events_for_month', $events_by_date, $month, $year);
    }

    public function list_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 10,
            'category' => '',
            'tag' => '',
        ), $atts);

        ob_start();
        $this->render_list($atts);
        return ob_get_clean();
    }

    private function render_list($atts) {
        $args = array(
            'post_type' => 'kh_event',
            'posts_per_page' => intval($atts['limit']),
            'meta_key' => '_kh_event_start_date',
            'orderby' => 'meta_value',
            'order' => 'ASC',
            'meta_query' => array(
                array(
                    'key' => '_kh_event_start_date',
                    'value' => date('Y-m-d'),
                    'compare' => '>=',
                    'type' => 'DATE'
                )
            )
        );

        if (!empty($atts['category'])) {
            $args['tax_query'][] = array(
                'taxonomy' => 'kh_event_category',
                'field' => 'slug',
                'terms' => $atts['category']
            );
        }
        if (!empty($atts['tag'])) {
            $args['tax_query'][] = array(
                'taxonomy' => 'kh_event_tag',
                'field' => 'slug',
                'terms' => $atts['tag']
            );
        }

        $events = get_posts($args);
        $events = apply_filters('kh_get_events_for_list', $events, array(
            'start_date' => date('Y-m-d'),
            'end_date' => date('Y-m-d', strtotime('+1 year')),
        ));

        ?>
        <div class="kh-events-list">
            <?php if ($events): ?>
                <?php foreach ($events as $event): ?>
                    <div class="kh-event-item">
                        <h3><a href="<?php echo get_permalink($event->ID); ?>"><?php echo get_the_title($event->ID); ?></a></h3>
                        <div class="kh-event-meta">
                            <span class="kh-event-date"><?php echo get_post_meta($event->ID, '_kh_event_start_date', true); ?></span>
                            <span class="kh-event-time"><?php echo get_post_meta($event->ID, '_kh_event_start_time', true); ?> - <?php echo get_post_meta($event->ID, '_kh_event_end_time', true); ?></span>
                        </div>
                        <div class="kh-event-excerpt"><?php echo get_the_excerpt($event->ID); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p><?php _e('No upcoming events found.', 'kh-events'); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }

    public function day_shortcode($atts) {
        $atts = shortcode_atts(array(
            'date' => date('Y-m-d'),
            'category' => '',
            'tag' => '',
        ), $atts);

        ob_start();
        $this->render_day($atts);
        return ob_get_clean();
    }

    private function render_day($atts) {
        $date = $atts['date'];
        $category = $atts['category'];
        $tag = $atts['tag'];

        $args = array(
            'post_type' => 'kh_event',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_kh_event_start_date',
                    'value' => $date,
                    'compare' => '=',
                    'type' => 'DATE'
                )
            )
        );

        $tax_query = array();
        if (!empty($category)) {
            $tax_query[] = array(
                'taxonomy' => 'kh_event_category',
                'field' => 'slug',
                'terms' => $category
            );
        }
        if (!empty($tag)) {
            $tax_query[] = array(
                'taxonomy' => 'kh_event_tag',
                'field' => 'slug',
                'terms' => $tag
            );
        }
        if (!empty($tax_query)) {
            $args['tax_query'] = $tax_query;
        }

        $events = get_posts($args);

        ?>
        <div class="kh-events-day">
            <h2><?php echo date('l, F j, Y', strtotime($date)); ?></h2>
            <?php if ($events): ?>
                <?php foreach ($events as $event): ?>
                    <div class="kh-event-item">
                        <h3><a href="<?php echo get_permalink($event->ID); ?>"><?php echo get_the_title($event->ID); ?></a></h3>
                        <div class="kh-event-meta">
                            <span class="kh-event-time"><?php echo get_post_meta($event->ID, '_kh_event_start_time', true); ?> - <?php echo get_post_meta($event->ID, '_kh_event_end_time', true); ?></span>
                        </div>
                        <div class="kh-event-content"><?php echo get_the_content(null, false, $event->ID); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p><?php _e('No events on this day.', 'kh-events'); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }
}