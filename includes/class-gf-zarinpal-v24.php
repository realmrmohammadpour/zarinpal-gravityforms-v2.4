<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** Gravity Forms Payment Add-On backed by ZarinPal REST API v4. */
final class ZP24_Gravity_Forms_Addon extends GFPaymentAddOn {
    protected $_version                  = ZP24_VERSION;
    protected $_min_gravityforms_version = '2.9.0';
    protected $_slug                     = 'gravityformszarinpalv24';
    protected $_path                     = 'GravityForms-master/index.php';
    protected $_full_path                = ZP24_PLUGIN_FILE;
    protected $_title                    = 'زرین‌پال نسخه 2.4';
    protected $_short_title              = 'زرین‌پال 2.4';
    protected $_supports_callbacks       = false; // Browser callback is handled by our signed, first-party endpoint.
    protected $_requires_credit_card     = false;
    private static $_instance            = null;

    public static function get_instance() {
        if ( null === self::$_instance ) {
            self::$_instance = new self();
            self::$_instance->_path = plugin_basename( ZP24_PLUGIN_FILE );
        }
        return self::$_instance;
    }

    public function init() {
        parent::init();
        add_action( 'template_redirect', array( $this, 'handle_browser_return' ), 0 );
        add_filter( 'gform_logging_supported', array( $this, 'register_logging' ) );
    }

    public function register_logging( $plugins ) {
        $plugins[ plugin_basename( ZP24_PLUGIN_FILE ) ] = 'ZarinPal GF 2.4';
        return $plugins;
    }

    public function plugin_settings_fields() {
        return array(
            array(
                'title'       => 'تنظیمات اتصال زرین‌پال',
                'description' => 'ابتدا در محیط Sandbox یک پرداخت آزمایشی موفق و ناموفق انجام دهید. این افزونه فقط پرداخت یک‌مرحله‌ای را پشتیبانی می‌کند.',
                'fields'      => array(
                    array( 'name' => 'merchant_id', 'label' => 'Merchant ID (UUID)', 'type' => 'text', 'class' => 'medium', 'required' => true, 'tooltip' => 'شناسه ۳۶ کاراکتری مرچنت زرین‌پال' ),
                    array( 'name' => 'sandbox', 'label' => 'محیط تست', 'type' => 'checkbox', 'choices' => array( array( 'name' => 'sandbox', 'label' => 'فعال‌سازی Sandbox (بدون پرداخت بانکی واقعی)' ) ) ),
                    array( 'name' => 'source_currency', 'label' => 'واحد مبالغ فرم', 'type' => 'select', 'choices' => array( array( 'label' => 'تومان (IRT)', 'value' => 'IRT' ), array( 'label' => 'ریال (IRR)', 'value' => 'IRR' ) ), 'default_value' => 'IRT', 'tooltip' => 'واحد واقعی مقدار عددی محصول در Gravity Forms، مستقل از نحوه نمایش نماد پول.' ),
                    array( 'name' => 'gateway_currency', 'label' => 'واحد مبلغ ارسال به زرین‌پال', 'type' => 'select', 'choices' => array( array( 'label' => 'تومان (IRT)', 'value' => 'IRT' ), array( 'label' => 'ریال (IRR)', 'value' => 'IRR' ) ), 'default_value' => 'IRT' ),
                ),
            ),
        );
    }

    public function feed_settings_fields() {
        return array(
            array(
                'title' => 'تنظیمات پرداخت فرم',
                'fields' => array(
                    array( 'name' => 'feedName', 'label' => 'نام فید', 'type' => 'text', 'class' => 'medium', 'required' => true ),
                    array( 'name' => 'transactionType', 'label' => 'نوع تراکنش', 'type' => 'select', 'required' => true, 'choices' => array( array( 'label' => 'پرداخت یک‌مرحله‌ای محصولات و خدمات', 'value' => 'product' ) ), 'default_value' => 'product' ),
                    array( 'name' => 'paymentAmount', 'label' => 'مبلغ پرداخت', 'type' => 'select', 'required' => true, 'choices' => $this->product_amount_choices(), 'default_value' => 'form_total' ),
                    array( 'name' => 'zp24_description', 'label' => 'توضیحات تراکنش', 'type' => 'text', 'class' => 'large', 'default_value' => 'پرداخت فرم {form_title} - ورودی {entry_id}', 'tooltip' => 'متغیرهای مجاز: {form_title} ، {form_id} و {entry_id}' ),
                    array( 'name' => 'zp24_mobile_field', 'label' => 'فیلد موبایل (اختیاری)', 'type' => 'field_select' ),
                    array( 'name' => 'zp24_email_field', 'label' => 'فیلد ایمیل (اختیاری)', 'type' => 'field_select' ),
                    array( 'name' => 'zp24_desc_field', 'label' => 'فیلد توضیحات تکمیلی (اختیاری)', 'type' => 'field_select' ),
                    array( 'name' => 'conditionalLogic', 'label' => 'منطق شرطی پرداخت', 'type' => 'feed_condition', 'checkbox_label' => 'فعال‌سازی شرط پرداخت', 'instructions' => 'پرداخت را فقط در صورت برقراری شرط فعال کن.' ),
                ),
            ),
        );
    }

    /** Current settings override old options only when a new value is explicitly saved. */
    public function payment_settings() {
        $settings = $this->get_plugin_settings();
        $settings = is_array( $settings ) ? $settings : array();
        $legacy   = get_option( 'gf_zarinpal_settings', array() );
        if ( empty( $settings['merchant_id'] ) && is_array( $legacy ) && ! empty( $legacy['merchent'] ) && get_option( 'gf_zarinpal_configured' ) ) {
            $settings['merchant_id'] = trim( (string) $legacy['merchent'] );
        }
        $settings = wp_parse_args( $settings, array(
            'merchant_id'      => '',
            'source_currency'  => 'IRT',
            'gateway_currency' => 'IRT',
            'sandbox'          => false,
        ) );
        $settings['source_currency']  = in_array( $settings['source_currency'], array( 'IRT', 'IRR' ), true ) ? $settings['source_currency'] : 'IRT';
        $settings['gateway_currency'] = in_array( $settings['gateway_currency'], array( 'IRT', 'IRR' ), true ) ? $settings['gateway_currency'] : 'IRT';
        $settings['sandbox'] = ! empty( $settings['sandbox'] );
        return $settings;
    }

    public static function normalize_amount( $value ) {
        if ( ! is_scalar( $value ) || is_bool( $value ) ) {
            return new WP_Error( 'zp24_amount', 'مبلغ فرم معتبر نیست.' );
        }
        $value = trim( (string) $value );
        $value = strtr( $value, array(
            '۰'=>'0', '۱'=>'1', '۲'=>'2', '۳'=>'3', '۴'=>'4', '۵'=>'5', '۶'=>'6', '۷'=>'7', '۸'=>'8', '۹'=>'9',
            '٠'=>'0', '١'=>'1', '٢'=>'2', '٣'=>'3', '٤'=>'4', '٥'=>'5', '٦'=>'6', '٧'=>'7', '٨'=>'8', '٩'=>'9',
        ) );
        $value = str_replace( array( ',', '٬', ' ' ), '', $value );
        if ( ! preg_match( '/^\d+(?:\.\d{1,2})?$/D', $value ) ) {
            return new WP_Error( 'zp24_amount', 'مبلغ باید یک عدد مثبت و بدون اعشار باشد.' );
        }
        $parts = explode( '.', $value, 2 );
        if ( isset( $parts[1] ) && (int) $parts[1] !== 0 ) {
            return new WP_Error( 'zp24_amount_fraction', 'زرین‌پال مبلغ دارای اعشار را نمی‌پذیرد.' );
        }
        $digits = ltrim( $parts[0], '0' );
        if ( '' === $digits || strlen( $digits ) > strlen( (string) PHP_INT_MAX ) || ( strlen( $digits ) === strlen( (string) PHP_INT_MAX ) && strcmp( $digits, (string) PHP_INT_MAX ) > 0 ) ) {
            return new WP_Error( 'zp24_amount_range', 'مبلغ صفر یا خارج از محدوده مجاز است.' );
        }
        return (int) $digits;
    }

    public static function convert_amount( $amount, $from, $to ) {
        if ( ! is_int( $amount ) || $amount <= 0 || ! in_array( $from, array( 'IRR', 'IRT' ), true ) || ! in_array( $to, array( 'IRR', 'IRT' ), true ) ) {
            return new WP_Error( 'zp24_currency', 'واحد پول یا مبلغ صحیح نیست.' );
        }
        if ( $from === $to ) {
            return $amount;
        }
        if ( 'IRR' === $from ) {
            if ( $amount % 10 !== 0 ) {
                return new WP_Error( 'zp24_currency_precision', 'مبلغ ریالی برای تبدیل دقیق به تومان باید مضرب ۱۰ باشد.' );
            }
            return intdiv( $amount, 10 );
        }
        if ( $amount > intdiv( PHP_INT_MAX, 10 ) ) {
            return new WP_Error( 'zp24_currency_overflow', 'مبلغ قابل تبدیل به ریال نیست.' );
        }
        return $amount * 10;
    }

    /** Called by GFPaymentAddOn after the Entry exists. Works with GF's built-in AJAX confirmations. */
    public function redirect_url( $feed, $submission_data, $form, $entry ) {
        $entry_id = isset( $entry['id'] ) ? absint( $entry['id'] ) : 0;
        if ( ! $entry_id || empty( $feed['meta'] ) || rgar( $feed['meta'], 'transactionType' ) !== 'product' ) {
            return '';
        }
        if ( ! isset( $submission_data['payment_amount'] ) ) {
            $this->mark_initial_error( $entry_id, 'مبلغ ارسالی Gravity Forms مشخص نیست.' );
            return $this->create_error_receipt( $entry_id, (int) $feed['id'] );
        }
        $source_amount = self::normalize_amount( $submission_data['payment_amount'] );
        $settings      = $this->payment_settings();
        $amount        = is_wp_error( $source_amount ) ? $source_amount : self::convert_amount( $source_amount, $settings['source_currency'], $settings['gateway_currency'] );
        $merchant      = trim( (string) $settings['merchant_id'] );
        if ( is_wp_error( $amount ) || ! preg_match( '/^[a-fA-F0-9]{8}-(?:[a-fA-F0-9]{4}-){3}[a-fA-F0-9]{12}$/D', $merchant ) ) {
            $message = is_wp_error( $amount ) ? $amount->get_error_message() : 'Merchant ID معتبر نیست. تنظیمات زرین‌پال را بررسی کنید.';
            $this->mark_initial_error( $entry_id, $message );
            return $this->create_error_receipt( $entry_id, (int) $feed['id'] );
        }
        $token    = wp_generate_password( 48, false, false );
        $sig_hash = hash_hmac( 'sha256', $token, wp_salt( 'auth' ) );
        $description = (string) rgar( $feed['meta'], 'zp24_description' );
        $description = str_replace( array( '{form_title}', '{form_id}', '{entry_id}' ), array( rgar( $form, 'title' ), (string) $form['id'], (string) $entry_id ), $description );
        $detail_id = rgar( $feed['meta'], 'zp24_desc_field' );
        if ( $detail_id && isset( $entry[ (string) $detail_id ] ) && is_scalar( $entry[ (string) $detail_id ] ) ) {
            $description .= ' - ' . (string) $entry[ (string) $detail_id ];
        }
        $description = trim( sanitize_text_field( $description ) );
        $description = function_exists( 'mb_substr' ) ? mb_substr( $description, 0, 255 ) : substr( $description, 0, 255 );
        if ( '' === $description ) {
            $description = 'پرداخت فرم شماره ' . absint( $form['id'] ) . '، ورودی ' . $entry_id;
        }
        $metadata = array( 'order_id' => (string) $entry_id );
        $phone_id = (string) rgar( $feed['meta'], 'zp24_mobile_field' );
        $email_id = (string) rgar( $feed['meta'], 'zp24_email_field' );
        if ( $phone_id && ! empty( $entry[ $phone_id ] ) && is_scalar( $entry[ $phone_id ] ) ) {
            $phone = preg_replace( '/[^0-9+]/', '', (string) $entry[ $phone_id ] );
            if ( preg_match( '/^(?:\+98|0098|0)?9\d{9}$/', $phone ) ) {
                $metadata['mobile'] = $phone;
            }
        }
        if ( $email_id && ! empty( $entry[ $email_id ] ) && is_scalar( $entry[ $email_id ] ) ) {
            $email = sanitize_email( $entry[ $email_id ] );
            if ( is_email( $email ) ) {
                $metadata['email'] = $email;
            }
        }
        $callback = add_query_arg( array( 'zp24_callback' => '1', 'entry_id' => $entry_id, 'token' => $token ), home_url( '/' ) );
        // Persist immutable bank-verification inputs before the outbound request.
        $meta = array(
            'zp24_token_hash'       => $sig_hash,
            'zp24_merchant'         => $merchant,
            'zp24_amount'           => $amount,
            'zp24_source_amount'    => $source_amount,
            'zp24_currency'         => $settings['gateway_currency'],
            'zp24_sandbox'          => $settings['sandbox'] ? '1' : '0',
            'zp24_feed_id'          => absint( $feed['id'] ),
            'zp24_state'            => 'initializing',
            'zp24_authority'        => '',
            'zp24_verified_ref_id'  => '',
        );
        foreach ( $meta as $key => $value ) {
            gform_update_meta( $entry_id, $key, $value );
        }
        $api      = new ZP24_Api( $settings['sandbox'] );
        $response = $api->request_payment( $merchant, $amount, $settings['gateway_currency'], $description, $callback, $metadata );
        if ( is_wp_error( $response ) || (int) rgar( $response, 'code' ) !== 100 || empty( $response['authority'] ) || ! preg_match( '/^[A-Za-z0-9_-]{20,100}$/D', $response['authority'] ) ) {
            $reason = is_wp_error( $response ) ? $response->get_error_message() : 'درخواست پرداخت توسط درگاه پذیرفته نشد (code ' . (int) rgar( $response, 'code' ) . ').';
            $this->mark_initial_error( $entry_id, $reason );
            return $this->receipt_url( $entry_id, $token );
        }
        gform_update_meta( $entry_id, 'zp24_authority', $response['authority'] );
        gform_update_meta( $entry_id, 'zp24_state', 'pending' );
        return $api->startpay_url( $response['authority'] );
    }

    private function create_error_receipt( $entry_id, $feed_id ) {
        $token = wp_generate_password( 48, false, false );
        gform_update_meta( $entry_id, 'zp24_token_hash', hash_hmac( 'sha256', $token, wp_salt( 'auth' ) ) );
        gform_update_meta( $entry_id, 'zp24_feed_id', absint( $feed_id ) );
        return $this->receipt_url( $entry_id, $token );
    }

    private function mark_initial_error( $entry_id, $message ) {
        gform_update_meta( $entry_id, 'zp24_error', sanitize_text_field( $message ) );
        gform_update_meta( $entry_id, 'zp24_state', 'error' );
        $this->safe_note( $entry_id, 'زرین‌پال: ' . sanitize_text_field( $message ) );
    }

    private function receipt_url( $entry_id, $token ) {
        return add_query_arg( array( 'zp24_receipt' => absint( $entry_id ), 'token' => $token ), home_url( '/' ) );
    }

    private function safe_note( $entry_id, $message ) {
        if ( method_exists( 'GFAPI', 'add_note' ) ) {
            GFAPI::add_note( $entry_id, 0, 'ZarinPal 2.4', $message );
        }
    }

    private function authorized_entry( $entry_id, $token ) {
        $entry = $entry_id ? GFAPI::get_entry( $entry_id ) : false;
        if ( ! is_array( $entry ) || empty( $entry['id'] ) || ! is_string( $token ) || strlen( $token ) < 32 || strlen( $token ) > 100 ) {
            return false;
        }
        $stored = (string) gform_get_meta( $entry_id, 'zp24_token_hash' );
        $given  = hash_hmac( 'sha256', $token, wp_salt( 'auth' ) );
        if ( ! $stored || ! hash_equals( $stored, $given ) || (string) gform_get_meta( $entry_id, 'zp24_feed_id' ) === '' ) {
            return false;
        }
        return $entry;
    }

    private function update_status( $entry_id, $status, $state ) {
        $entry = GFAPI::get_entry( $entry_id );
        if ( is_array( $entry ) && rgar( $entry, 'payment_status' ) !== 'Paid' ) {
            $entry['payment_status'] = $status;
            $entry['payment_method'] = 'zarinpal';
            GFAPI::update_entry( $entry );
        }
        gform_update_meta( $entry_id, 'zp24_state', $state );
    }

    /** Attempt lock is an atomic wp_options INSERT, not a non-atomic entry-meta check. */
    private function acquire_lock( $entry_id ) {
        $key = 'zp24_lock_' . $entry_id;
        if ( add_option( $key, (string) time(), '', false ) ) {
            return true;
        }
        $since = (int) get_option( $key );
        if ( $since && $since < time() - 120 ) {
            delete_option( $key );
            return add_option( $key, (string) time(), '', false );
        }
        return false;
    }

    public function handle_browser_return() {
        if ( isset( $_GET['zp24_callback'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['zp24_callback'] ) ) ) {
            $this->process_browser_callback();
        }
        if ( isset( $_GET['zp24_receipt'] ) ) {
            $this->render_receipt();
        }
    }

    private function process_browser_callback() {
        if ( 'GET' !== (string) ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
            status_header( 405 );
            exit;
        }
        if ( ! defined( 'DONOTCACHEPAGE' ) ) { define( 'DONOTCACHEPAGE', true ); }
        nocache_headers();
        header( 'Referrer-Policy: no-referrer', true );
        $entry_id  = isset( $_GET['entry_id'] ) ? absint( $_GET['entry_id'] ) : 0;
        $token     = isset( $_GET['token'] ) && is_scalar( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';
        $authority = isset( $_GET['Authority'] ) && is_scalar( $_GET['Authority'] ) ? sanitize_text_field( wp_unslash( $_GET['Authority'] ) ) : '';
        $status    = isset( $_GET['Status'] ) && is_scalar( $_GET['Status'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_GET['Status'] ) ) ) : '';
        $entry     = $this->authorized_entry( $entry_id, $token );
        if ( ! $entry ) {
            status_header( 403 );
            wp_die( esc_html__( 'لینک بازگشت پرداخت معتبر نیست.', 'gf-zarinpal-v24' ), 'خطای تأیید پرداخت', array( 'response' => 403 ) );
        }
        if ( ! $this->acquire_lock( $entry_id ) ) {
            wp_safe_redirect( $this->receipt_url( $entry_id, $token ), 303 );
            exit;
        }
        try {
            $state = (string) gform_get_meta( $entry_id, 'zp24_state' );
            if ( 'paid' !== $state && 'Paid' !== rgar( $entry, 'payment_status' ) ) {
                $expected_authority = (string) gform_get_meta( $entry_id, 'zp24_authority' );
                if ( ! $expected_authority || ! $authority || ! hash_equals( $expected_authority, $authority ) || ! in_array( $status, array( 'OK', 'NOK' ), true ) ) {
                    $this->safe_note( $entry_id, 'زرین‌پال: پارامترهای Callback معتبر نیستند.' );
                    gform_update_meta( $entry_id, 'zp24_state', 'review' );
                } elseif ( $status === 'NOK' ) {
                    $this->update_status( $entry_id, 'Cancelled', 'cancelled' );
                    $this->safe_note( $entry_id, 'زرین‌پال: خریدار پرداخت را لغو کرد یا درگاه وضعیت NOK برگرداند.' );
                } else {
                    $this->process_verified_payment( $entry_id, $entry );
                }
            }
            // Resume a previously verified, already-Paid Entry if execution stopped after GF wrote its status.
            $latest = GFAPI::get_entry( $entry_id );
            if ( is_array( $latest ) && 'Paid' === rgar( $latest, 'payment_status' ) ) {
                $this->finalize_verified_entry( $entry_id, $latest );
            }
        } finally {
            delete_option( 'zp24_lock_' . $entry_id );
        }
        wp_safe_redirect( $this->receipt_url( $entry_id, $token ), 303 );
        exit;
    }

    private function process_verified_payment( $entry_id, $entry ) {
        $merchant  = (string) gform_get_meta( $entry_id, 'zp24_merchant' );
        $amount    = (int) gform_get_meta( $entry_id, 'zp24_amount' );
        $authority = (string) gform_get_meta( $entry_id, 'zp24_authority' );
        $source    = (int) gform_get_meta( $entry_id, 'zp24_source_amount' );
        if ( ! $merchant || $amount <= 0 || $source <= 0 || ! $authority ) {
            gform_update_meta( $entry_id, 'zp24_state', 'review' );
            $this->safe_note( $entry_id, 'زرین‌پال: داده‌های پرداخت اولیه ناقص است؛ نیازمند بررسی.' );
            return;
        }
        $api      = new ZP24_Api( '1' === (string) gform_get_meta( $entry_id, 'zp24_sandbox' ) );
        $response = $api->verify_payment( $merchant, $amount, $authority );
        if ( is_wp_error( $response ) ) {
            gform_update_meta( $entry_id, 'zp24_state', 'review' );
            $this->safe_note( $entry_id, 'زرین‌پال: وضعیت پرداخت نامشخص، اختلال در Verify. ' . $response->get_error_code() );
            return;
        }
        $code = (int) rgar( $response, 'code' );
        $ref  = isset( $response['ref_id'] ) && is_scalar( $response['ref_id'] ) ? sanitize_text_field( (string) $response['ref_id'] ) : '';
        if ( 101 === $code ) {
            $previous = (string) gform_get_meta( $entry_id, 'zp24_verified_ref_id' );
            if ( ! $previous || ! $ref || ! hash_equals( $previous, $ref ) ) {
                gform_update_meta( $entry_id, 'zp24_state', 'review' );
                $this->safe_note( $entry_id, 'زرین‌پال: کد ۱۰۱ بدون سابقه تأیید محلی یکسان؛ نیازمند تطبیق دستی.' );
                return;
            }
        } elseif ( 100 !== $code || '' === $ref ) {
            gform_update_meta( $entry_id, 'zp24_state', 'review' );
            $this->safe_note( $entry_id, 'زرین‌پال: Verify ناموفق یا نامشخص؛ کد ' . $code );
            return;
        }
        gform_update_meta( $entry_id, 'zp24_verified_ref_id', $ref );
        gform_update_meta( $entry_id, 'zp24_state', 'committing' );
        $action = array(
            'type'             => 'complete_payment',
            'amount'           => $source,
            'transaction_type' => 'payment',
            'payment_method' => 'zarinpal',
            'transaction_id'   => $ref,
            'entry_id'         => $entry_id,
            'payment_status'   => 'Paid',
            'note'             => 'پرداخت موفق زرین‌پال؛ کد رهگیری: ' . $ref,
        );
        $this->complete_payment( $entry, $action );
        $updated_entry = GFAPI::get_entry( $entry_id );
        if ( is_array( $updated_entry ) && 'Paid' === rgar( $updated_entry, 'payment_status' ) ) {
            $this->finalize_verified_entry( $entry_id, $updated_entry );
        } else {
            gform_update_meta( $entry_id, 'zp24_state', 'review' );
            $this->safe_note( $entry_id, 'زرین‌پال: Verify موفق اما ثبت وضعیت Gravity Forms نیازمند بررسی است.' );
        }
    }

    /** Recoverable, idempotent state finalization and dispatch for dependent GF feeds. */
    private function finalize_verified_entry( $entry_id, $entry ) {
        $ref = (string) gform_get_meta( $entry_id, 'zp24_verified_ref_id' );
        if ( '' === $ref || ! hash_equals( $ref, (string) rgar( $entry, 'transaction_id' ) ) || 'Paid' !== rgar( $entry, 'payment_status' ) ) {
            return;
        }
        gform_update_meta( $entry_id, 'zp24_state', 'paid' );
        if ( '1' === (string) gform_get_meta( $entry_id, 'zp24_delayed_feeds_done' ) ) {
            return;
        }
        $form_id = absint( rgar( $entry, 'form_id' ) );
        $form    = GFAPI::get_form( $form_id );
        $feed_id = absint( gform_get_meta( $entry_id, 'zp24_feed_id' ) );
        $payment_feed = $feed_id ? GFAPI::get_feed( $feed_id ) : false;
        if ( ! is_array( $form ) || ! is_array( $payment_feed ) || (int) rgar( $payment_feed, 'form_id' ) !== $form_id ) {
            $this->safe_note( $entry_id, 'زرین‌پال: فید مرتبط برای اجرای افزودنی‌های وابسته یافت نشد؛ بررسی دستی لازم است.' );
            return;
        }
        // Set before dispatch: payment callbacks cannot invoke an add-on feed twice.
        gform_update_meta( $entry_id, 'zp24_delayed_feeds_done', '1' );
        $this->trigger_payment_delayed_feeds( $ref, $payment_feed, $entry, $form );
    }

    private function render_receipt() {
        if ( ! defined( 'DONOTCACHEPAGE' ) ) { define( 'DONOTCACHEPAGE', true ); }
        nocache_headers();
        header( 'X-Robots-Tag: noindex, nofollow', true );
        header( 'Referrer-Policy: no-referrer', true );
        $entry_id = absint( $_GET['zp24_receipt'] );
        $token = isset( $_GET['token'] ) && is_scalar( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';
        $entry = $this->authorized_entry( $entry_id, $token );
        if ( ! $entry ) {
            wp_die( 'دسترسی به رسید مجاز نیست.', 'زرین‌پال', array( 'response' => 403 ) );
        }
        $state = (string) gform_get_meta( $entry_id, 'zp24_state' );
        if ( 'error' === $state && ! in_array( rgar( $entry, 'payment_status' ), array( 'Paid', 'Failed' ), true ) ) {
            $this->update_status( $entry_id, 'Failed', 'error' );
            $entry = GFAPI::get_entry( $entry_id );
        }
        $paid  = ( 'paid' === $state && 'Paid' === rgar( $entry, 'payment_status' ) );
        if ( $paid ) {
            $title = 'پرداخت با موفقیت انجام شد';
            $message = 'تراکنش شما توسط زرین‌پال تأیید و در سیستم ثبت شد.';
        } elseif ( 'cancelled' === $state ) {
            $title = 'پرداخت لغو شد';
            $message = 'پرداختی ثبت نشده است. برای تلاش مجدد فرم را از نو تکمیل کنید.';
        } elseif ( 'error' === $state ) {
            $title = 'درگاه پرداخت در دسترس نیست';
            $message = 'شروع پرداخت ناموفق بود. لطفاً با پشتیبانی تماس بگیرید یا دوباره تلاش کنید.';
        } else {
            $title = 'نتیجه پرداخت نیازمند بررسی است';
            $message = 'پرداخت هنوز قطعی نشده است. لطفاً تا تأیید نهایی از ثبت دوباره پرداخت خودداری کنید.';
        }
        status_header( 200 );
        get_header();
        echo '<main dir="rtl" style="min-height:55vh;display:grid;place-items:center;padding:48px 16px;background:#f7f8fc">';
        echo '<section style="max-width:540px;width:100%;box-sizing:border-box;background:#fff;border:1px solid #e5e7ef;border-radius:20px;padding:36px 28px;text-align:center;box-shadow:0 12px 40px rgba(15,23,42,.04)">';
        echo '<h1 style="font-size:25px;line-height:1.6;margin:0 0 16px;color:#182335">' . esc_html( $title ) . '</h1>';
        echo '<p style="line-height:2;color:#4b5563;font-size:15px">' . esc_html( $message ) . '</p>';
        echo '<p style="color:#64748b;font-size:14px;margin-top:20px">شماره ورودی: ' . esc_html( (string) $entry_id ) . '</p>';
        if ( $paid ) {
            $ref = (string) gform_get_meta( $entry_id, 'zp24_verified_ref_id' );
            echo '<p style="font-weight:700;color:#167a51">کد رهگیری: <bdi>' . esc_html( $ref ) . '</bdi></p>';
        }
        if ( in_array( $state, array( 'review', 'committing' ), true ) ) {
            $authority = (string) gform_get_meta( $entry_id, 'zp24_authority' );
            if ( $authority ) {
                $retry = add_query_arg( array( 'zp24_callback' => '1', 'entry_id' => $entry_id, 'token' => $token, 'Authority' => $authority, 'Status' => 'OK' ), home_url( '/' ) );
                echo '<p><a rel="nofollow" href="' . esc_url( $retry ) . '" style="display:inline-block;padding:10px 22px;background:#1b4984;color:#fff;border-radius:9px;text-decoration:none">بررسی دوباره وضعیت</a></p>';
            }
        }
        echo '<a href="' . esc_url( home_url( '/' ) ) . '" style="display:inline-block;margin-top:16px;color:#345b91;text-decoration:none">بازگشت به سایت</a>';
        echo '</section></main>';
        get_footer();
        exit;
    }
}
