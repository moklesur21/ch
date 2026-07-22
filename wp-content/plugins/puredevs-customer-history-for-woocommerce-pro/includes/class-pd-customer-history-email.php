<?php

defined( 'ABSPATH' ) or exit;

/*
 * PD WooCommerce Customer History Email Handler
 */

if ( ! class_exists( 'PD_Customer_History_Email' ) ) {

    class PD_Customer_History_Email {

        /**
         * Constructor
         */
        public function __construct() {
            // Constructor can be expanded later if needed
        }

        /**
         * Send email to customer
         *
         * @since 1.0.0
         * @access public static
         * @param int $user_id Customer user ID
         * @param string $subject Email subject
         * @param string $message Email message
         * @return bool True if email sent successfully, false otherwise
         */
        public static function pdwchs_send_customer_email( $user_id, $subject, $message ) {
            if ( empty( $user_id ) || empty( $subject ) || empty( $message ) ) {
                return false;
            }

            $user = get_user_by( 'id', $user_id );
            if ( ! $user ) {
                return false;
            }

            // Sanitize inputs
            $subject = sanitize_text_field( $subject );
            $message = wp_kses_post( $message );

            // Set email headers
            $headers = array(
                'Content-Type: text/html; charset=UTF-8',
                'From: ' . get_bloginfo( 'name' ) . ' <' . get_option( 'admin_email' ) . '>'
            );

            // Send the email
            $email_sent = wp_mail( $user->user_email, $subject, $message, $headers );

            // Log email activity (optional - can be expanded)
            if ( $email_sent ) {
                self::pdwchs_log_email_activity( $user_id, $subject );
            }

            return $email_sent;
        }

        /**
         * Log email activity for tracking purposes
         *
         * @since 1.0.0
         * @access public static
         * @param int $user_id Customer user ID
         * @param string $subject Email subject
         */
        public static function pdwchs_log_email_activity( $user_id, $subject ) {
            // This can be expanded to log email activities in the database
            // For now, we'll use WordPress action hooks
            do_action( 'pdwchs_customer_email_sent', $user_id, $subject );
        }

        /**
         * Get customer email templates (future enhancement)
         *
         * @since 1.0.0
         * @access public static
         * @return array Array of email templates
         */
        public static function pdwchs_get_email_templates() {
            $templates = array(
                'welcome' => array(
                    'subject' => __( 'Welcome to our store!', 'puredevs-customer-history-for-woocommerce' ),
                    'message' => __( 'Thank you for being our valued customer. We appreciate your business!', 'puredevs-customer-history-for-woocommerce' )
                ),
                'follow_up' => array(
                    'subject' => __( 'How was your recent purchase?', 'puredevs-customer-history-for-woocommerce' ),
                    'message' => __( 'We hope you are enjoying your recent purchase. Please let us know if you have any questions or feedback.', 'puredevs-customer-history-for-woocommerce' )
                ),
                'promotion' => array(
                    'subject' => __( 'Special offer just for you!', 'puredevs-customer-history-for-woocommerce' ),
                    'message' => __( 'We have a special promotion that we think you will love. Check out our latest offers!', 'puredevs-customer-history-for-woocommerce' )
                )
            );

            return apply_filters( 'pdwchs_email_templates', $templates );
        }

        /**
         * Validate email data before sending
         *
         * @since 1.0.0
         * @access public static
         * @param int $user_id Customer user ID
         * @param string $subject Email subject
         * @param string $message Email message
         * @return array Validation result with status and message
         */
        public static function pdwchs_validate_email_data( $user_id, $subject, $message ) {
            $validation = array(
                'valid' => true,
                'errors' => array()
            );

            // Validate user ID
            if ( empty( $user_id ) || ! is_numeric( $user_id ) ) {
                $validation['valid'] = false;
                $validation['errors'][] = __( 'Invalid user ID provided.', 'puredevs-customer-history-for-woocommerce' );
            }

            // Check if user exists
            $user = get_user_by( 'id', $user_id );
            if ( ! $user ) {
                $validation['valid'] = false;
                $validation['errors'][] = __( 'User not found.', 'puredevs-customer-history-for-woocommerce' );
            }

            // Validate email address
            if ( $user && ! is_email( $user->user_email ) ) {
                $validation['valid'] = false;
                $validation['errors'][] = __( 'Invalid email address for this user.', 'puredevs-customer-history-for-woocommerce' );
            }

            // Validate subject
            if ( empty( trim( $subject ) ) ) {
                $validation['valid'] = false;
                $validation['errors'][] = __( 'Email subject is required.', 'puredevs-customer-history-for-woocommerce' );
            }

            // Validate message
            if ( empty( trim( wp_strip_all_tags( $message ) ) ) ) {
                $validation['valid'] = false;
                $validation['errors'][] = __( 'Email message is required.', 'puredevs-customer-history-for-woocommerce' );
            }

            return $validation;
        }

    }

}