<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** Non-destructive v2.3 -> v2.4 migration: original options and gf_zarinpal are never deleted. */
final class ZP24_Migrator {
    public static function maybe_migrate() {
        if ( ! current_user_can( 'manage_options' ) || ! class_exists( 'GFAPI' ) ) {
            return;
        }
        add_action( 'admin_notices', array( __CLASS__, 'show_notice' ) );
        if ( '1' !== (string) get_option( 'zp24_needs_migration', '1' ) ) {
            return;
        }
        self::migrate_settings();
        self::migrate_feeds();
    }

    private static function migrate_settings() {
        $addon = ZP24_Gravity_Forms_Addon::get_instance();
        $current = $addon->get_plugin_settings();
        $current = is_array( $current ) ? $current : array();
        $legacy  = get_option( 'gf_zarinpal_settings', array() );
        if ( ! empty( $current['merchant_id'] ) || ! is_array( $legacy ) || empty( $legacy['merchent'] ) ) {
            return;
        }
        $current['merchant_id']      = sanitize_text_field( trim( (string) $legacy['merchent'] ) );
        $current['source_currency']  = 'IRT';
        $current['gateway_currency'] = 'IRT';
        $current['sandbox']          = '0';
        $addon->update_plugin_settings( $current );
    }

    private static function migrate_feeds() {
        global $wpdb;
        $table = $wpdb->prefix . 'gf_zarinpal';
        if ( ! preg_match( '/^[a-zA-Z0-9_]+$/D', $table ) ) {
            return;
        }
        $exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );
        if ( $exists !== $table ) {
            update_option( 'zp24_needs_migration', '0', false );
            return;
        }
        // Table identifier is checked against a conservative allowlist above.
        $rows = $wpdb->get_results( 'SELECT id, form_id, is_active, meta FROM `' . $table . '` ORDER BY id ASC', ARRAY_A );
        if ( ! is_array( $rows ) ) {
            return;
        }
        $map     = get_option( 'zp24_migration_map', array() );
        $map     = is_array( $map ) ? $map : array();
        $warning = array();
        $created = 0;
        $all_ok  = true;
        foreach ( $rows as $row ) {
            $legacy_id = absint( $row['id'] );
            $form_id   = absint( $row['form_id'] );
            if ( ! $legacy_id || ! $form_id || ( isset( $map[ $legacy_id ] ) && absint( $map[ $legacy_id ] ) > 0 ) ) {
                continue;
            }
            $form = GFAPI::get_form( $form_id );
            if ( ! is_array( $form ) ) {
                $warning[] = 'فید قدیمی ' . $legacy_id . ': فرم شماره ' . $form_id . ' پیدا نشد.';
                $all_ok = false;
                continue;
            }
            $old = maybe_unserialize( $row['meta'] );
            $old = is_array( $old ) ? $old : array();
            $meta = array(
                'feedName'          => 'ZarinPal migrated #' . $legacy_id,
                'transactionType'   => 'product',
                'paymentAmount'     => 'form_total',
                'zp24_description'  => isset( $old['desc_pm'] ) ? sanitize_text_field( $old['desc_pm'] ) : 'پرداخت فرم {form_title} - ورودی {entry_id}',
                'zp24_mobile_field' => isset( $old['customer_fields_mobile'] ) ? sanitize_text_field( $old['customer_fields_mobile'] ) : '',
                'zp24_email_field'  => isset( $old['customer_fields_email'] ) ? sanitize_text_field( $old['customer_fields_email'] ) : '',
                'zp24_desc_field'   => isset( $old['customer_fields_desc'] ) ? sanitize_text_field( $old['customer_fields_desc'] ) : '',
                'zp24_legacy_id'    => $legacy_id,
            );
            $needs_review = false;
            if ( ! empty( $old['zarinpal_conditional_enabled'] ) ) {
                $converted = self::convert_condition( $old );
                if ( $converted ) {
                    $meta['feed_condition_conditional_logic'] = '1';
                    $meta['feed_condition_conditional_logic_object'] = $converted;
                } else {
                    $needs_review = true;
                    $warning[] = 'فید قدیمی ' . $legacy_id . ': منطق شرطی قابل انتقال قطعی نیست؛ فید جدید غیرفعال شد.';
                }
            }
            if ( ! empty( $old['update_post_action1'] ) && $old['update_post_action1'] !== 'default' || ! empty( $old['update_post_action2'] ) && $old['update_post_action2'] !== 'default' && $old['update_post_action2'] !== 'dont' ) {
                $needs_review = true;
                $warning[] = 'فید قدیمی ' . $legacy_id . ': تنظیم وضعیت نوشته سفارشی بوده؛ فید جدید برای بررسی غیرفعال شد.';
            }
            $existing = GFAPI::get_feeds( null, $form_id, 'gravityformszarinpalv24', null );
            if ( is_array( $existing ) ) {
                foreach ( $existing as $feed ) {
                    if ( (int) rgar( $feed['meta'], 'zp24_legacy_id' ) === $legacy_id ) {
                        $map[ $legacy_id ] = absint( $feed['id'] );
                        continue 2;
                    }
                }
            }
            $id = GFAPI::add_feed( $form_id, $meta, 'gravityformszarinpalv24' );
            if ( is_wp_error( $id ) || ! is_numeric( $id ) || (int) $id < 1 ) {
                $warning[] = 'فید قدیمی ' . $legacy_id . ': ایجاد فید جدید ناموفق بود.';
                $all_ok = false;
                continue;
            }
            // Keep imported payment feeds disabled until their price, currency and conditions are reviewed.
            $disabled = GFAPI::update_feed_property( $id, 'is_active', 0 );
            if ( is_wp_error( $disabled ) || false === $disabled ) {
                $warning[] = 'فید جدید ' . $id . ': غیرفعال‌کردن فید منتقل‌شده ناموفق بود؛ باید فوری از پیشخوان بررسی شود.';
                $all_ok = false;
            }
            $map[ $legacy_id ] = (int) $id;
            ++$created;
        }
        update_option( 'zp24_migration_map', $map, false );
        update_option( 'zp24_migration_report', array( 'created' => $created, 'warnings' => $warning ), false );
        if ( $all_ok ) {
            update_option( 'zp24_needs_migration', '0', false );
        }
    }

    private static function convert_condition( $old ) {
        $fields    = isset( $old['zarinpal_conditional_field_id'] ) ? (array) $old['zarinpal_conditional_field_id'] : array();
        $operators = isset( $old['zarinpal_conditional_operator'] ) ? (array) $old['zarinpal_conditional_operator'] : array();
        $values    = isset( $old['zarinpal_conditional_value'] ) ? (array) $old['zarinpal_conditional_value'] : array();
        $rules = array();
        $allowed = array( 'is', 'isnot', '>', '<', 'contains', 'starts_with', 'ends_with' );
        foreach ( $fields as $key => $field ) {
            if ( ! is_scalar( $field ) || '' === (string) $field ) {
                continue;
            }
            $op = isset( $operators[ $key ] ) ? (string) $operators[ $key ] : 'is';
            if ( ! in_array( $op, $allowed, true ) || ! preg_match( '/^\d+(?:\.\d+)?$/D', (string) $field ) ) {
                return false;
            }
            $rules[] = array(
                'fieldId'  => (string) $field,
                'operator' => $op,
                'value'    => isset( $values[ $key ] ) && is_scalar( $values[ $key ] ) ? sanitize_text_field( (string) $values[ $key ] ) : '',
            );
        }
        if ( ! $rules ) {
            return false;
        }
        return array( 'conditionalLogic' => array(
            'actionType' => 'show',
            'logicType'  => isset( $old['zarinpal_conditional_type'] ) && $old['zarinpal_conditional_type'] === 'any' ? 'any' : 'all',
            'rules'      => $rules,
        ) );
    }

    public static function show_notice() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        $report = get_option( 'zp24_migration_report', array() );
        if ( ! is_array( $report ) || ! isset( $report['created'] ) ) {
            return;
        }
        if ( empty( $report['created'] ) && empty( $report['warnings'] ) ) {
            return;
        }
        echo '<div class="notice notice-warning"><p><strong>زرین‌پال 2.4:</strong> ';
        echo esc_html( (string) absint( $report['created'] ) ) . ' فید از نسخه 2.3 منتقل شد. همه فیدهای منتقل‌شده به‌طور پیش‌فرض غیرفعال‌اند؛ پس از بررسی واحد پول و شرایط، آن‌ها را فعال کنید.';
        echo '</p>';
        foreach ( (array) ( $report['warnings'] ?? array() ) as $warning ) {
            echo '<p>' . esc_html( $warning ) . '</p>';
        }
        echo '</div>';
    }
}
