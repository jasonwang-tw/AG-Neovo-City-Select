<?php
/**
 * Plugin Name: AG Neovo City Select
 * Description: Taiwan address selection for classic WooCommerce forms, with independent ERP normalization.
 * Version: 1.1.0
 * Requires at least: 6.8
 * Requires PHP: 8.2
 * Requires Plugins: woocommerce
 * Author: AG Neovo
 * License: GPLv3
 * Text Domain: agneovo-city-select
 */
namespace AGNeovo\CitySelect {
    defined( 'ABSPATH' ) || exit;
    require_once __DIR__ . '/includes/class-data.php';
    require_once __DIR__ . '/includes/class-frontend.php';
    require_once __DIR__ . '/includes/class-admin.php';
    Admin::register();

    final class Plugin {
        const LEGACY = 'ry-wc-city-select/ry-wc-city-select.php';
        public static function legacy_active() {
            $network = is_multisite() ? (array) get_site_option( 'active_sitewide_plugins', [] ) : [];
            return in_array( self::LEGACY, (array) get_option( 'active_plugins', [] ), true ) || isset( $network[self::LEGACY] );
        }
        public static function boot() {
            if ( ! function_exists( 'WC' ) || self::legacy_active() ) { return; }
            Frontend::register();
        }
    }
    add_action( 'woocommerce_init', [ Plugin::class, 'boot' ], 20 );
}
namespace {
    if ( ! function_exists( 'agneovo_city_select_normalize_taiwan_part' ) ) {
        function agneovo_city_select_normalize_taiwan_part( $value, $state = '' ) {
            return \AGNeovo\CitySelect\Data::normalize( $value, $state );
        }
    }
}
