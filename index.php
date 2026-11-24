<?php
/**
 * Plugin Name: درگاه زرین‌پال برای Gravity Forms (نسخه 2.4)
 * Description: افزونه پرداخت زرین‌پال بر پایه API v4 و چارچوب پرداخت Gravity Forms؛ با انتقال امن تنظیمات و فیدهای نسخه 2.3.
 * Version: 2.4.2
 * Requires PHP: 7.4
 * Text Domain: gf-zarinpal-v24
 * Author: MH (Independent compatibility rewrite)
 * License: GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Set early when possible; page-cache layers must also exclude these two query arguments.
if ( ( isset( $_GET['zp24_callback'] ) || isset( $_GET['zp24_receipt'] ) ) && ! defined( 'DONOTCACHEPAGE' ) ) {
    define( 'DONOTCACHEPAGE', true );
}

define( 'ZP24_VERSION', '2.4.2' );
define( 'ZP24_PLUGIN_FILE', __FILE__ );
define( 'ZP24_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

final class ZP24_Bootstrap {
    public static function activate() {
        update_option( 'zp24_needs_migration', '1', false );
    }

    public static function missing_gravity_forms() {
        if ( ! current_user_can( 'activate_plugins' ) ) {
            return;
        }
        echo '<div class="notice notice-error"><p>' . esc_html__( 'افزونه زرین‌پال 2.4 نیازمند Gravity Forms نسخه 2.9 یا جدیدتر است.', 'gf-zarinpal-v24' ) . '</p></div>';
    }

    public static function load() {
        if ( ! class_exists( 'GFForms' ) || ! method_exists( 'GFForms', 'include_payment_addon_framework' ) ) {
            add_action( 'admin_notices', array( __CLASS__, 'missing_gravity_forms' ) );
            return;
        }
        if ( class_exists( 'GFCommon' ) && isset( GFCommon::$version ) && version_compare( GFCommon::$version, '2.9.0', '<' ) ) {
            add_action( 'admin_notices', array( __CLASS__, 'missing_gravity_forms' ) );
            return;
        }
        GFForms::include_payment_addon_framework();
        require_once ZP24_PLUGIN_DIR . 'includes/class-zp24-api.php';
        require_once ZP24_PLUGIN_DIR . 'includes/class-zp24-migrator.php';
        require_once ZP24_PLUGIN_DIR . 'includes/class-zp24-persian-compat.php';
        require_once ZP24_PLUGIN_DIR . 'includes/class-gf-zarinpal-v24.php';
        GFAddOn::register( 'ZP24_Gravity_Forms_Addon' );
        add_action( 'admin_init', array( 'ZP24_Migrator', 'maybe_migrate' ), 30 );
    }
}

register_activation_hook( __FILE__, array( 'ZP24_Bootstrap', 'activate' ) );
add_action( 'gform_loaded', array( 'ZP24_Bootstrap', 'load' ), 5 );
