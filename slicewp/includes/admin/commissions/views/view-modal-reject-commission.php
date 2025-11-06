<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

?>

<div class="slicewp-screen-overlay">

    <div class="slicewp-modal-frame slicewp-modal-reject-commission">

        <div class="slicewp-modal-content">

            <div class="slicewp-modal-header">
                <h1><?php echo __( 'Reject commission', 'slicewp' ); ?><span></span></h1>
                <a href="#" class="slicewp-close-modal"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M13 11.8l6.1-6.3-1-1-6.1 6.2-6.1-6.2-1 1 6.1 6.3-6.5 6.7 1 1 6.5-6.6 6.5 6.6 1-1z"></path></svg></a>
            </div>

            <form method="POST">

                <!-- Rejection reason -->
                <div class="slicewp-field-wrapper">

                    <div class="slicewp-field-label-wrapper">
                        <label for="slicewp-modal-commission-rejection-reason"><?php echo __( 'Rejection reason', 'slicewp' ); ?></label>
                    </div>

                    <textarea id="slicewp-modal-commission-rejection-reason" name="rejection_reason"></textarea>

                </div>

                <!-- Send rejection email notification -->
				<?php $notification_settings = slicewp_get_email_notification_settings( 'affiliate_commission_rejected' ); ?>

                <?php if ( ! empty( $notification_settings['enabled'] ) ): ?>

                    <div class="slicewp-field-wrapper">

                        <div class="slicewp-switch">
                            <input id="slicewp-send-rejection-email-notification" class="slicewp-toggle slicewp-toggle-round" name="send_rejection_email_notification" type="checkbox" value="1" checked />
                            <label for="slicewp-send-rejection-email-notification"></label>
                        </div>

                        <label for="slicewp-send-rejection-email-notification"><?php echo __( 'Send commission rejected email notification to the affiliate', 'slicewp' ); ?></label>

                    </div>

                <?php endif; ?>

                <!-- Hidden and nonce -->
                <input type="hidden" name="commission_id" />

                <input type="hidden" name="slicewp_action" value="reject_commission" />
                <input type="hidden" name="_wp_http_referer" value="<?php echo esc_url( remove_query_arg( array( '_wp_http_referer', 'slicewp_message', 'updated' ) ) ); ?>" />
                <?php wp_nonce_field( 'slicewp_reject_commission', 'slicewp_token', false ); ?>

                <div class="slicewp-modal-footer">
                    <input type="submit" class="slicewp-form-submit slicewp-button-primary" value="Reject commission">
                    <a href="#" class="slicewp-close-modal slicewp-button-tertiary"><?php echo __( 'Cancel', 'slicewp' ); ?></a>
                </div>

            </form>

        </div>

    </div>

</div>