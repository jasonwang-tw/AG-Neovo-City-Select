<?php
namespace AGNeovo\CitySelect;
defined( 'ABSPATH' ) || exit;

final class Frontend {
    public static function register() {
        add_filter( 'woocommerce_states', [ __CLASS__, 'states' ], 10 );
        add_filter( 'woocommerce_billing_fields', [ __CLASS__, 'billing' ], 10, 2 );
        add_filter( 'woocommerce_shipping_fields', [ __CLASS__, 'shipping' ], 10, 2 );
        add_filter( 'woocommerce_form_field_city', [ __CLASS__, 'field' ], 10, 4 );
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'assets' ], 30 );
    }

    public static function states( $states ) {
        $states['TW'] = Data::states();
        return $states;
    }

    public static function billing( $fields, $country = '' ) {
        if ( ( $country === 'TW' || $country === '' ) && isset( $fields['billing_city'] ) ) { $fields['billing_city']['type'] = 'city'; }
        return $fields;
    }

    public static function shipping( $fields, $country = '' ) {
        if ( ( $country === 'TW' || $country === '' ) && isset( $fields['shipping_city'] ) ) { $fields['shipping_city']['type'] = 'city'; }
        return $fields;
    }

    public static function field( $field, $key, $args, $value ) {
        if ( ! function_exists( 'WC' ) || ! function_exists( 'woocommerce_form_field' ) ) { return $field; }
        $args['return'] = true;
        if ( ! preg_match( '/^(billing|shipping)_city$/D', $key, $match ) ) { $args['type'] = 'text'; return woocommerce_form_field( $key, $args, $value ); }
        $checkout = WC()->checkout(); $prefix = $match[1];
        $country = $checkout->get_value( $prefix . '_country' );
        $state = $checkout->get_value( $prefix . '_state' );
        if ( $country !== 'TW' ) { $args['type'] = 'text'; return woocommerce_form_field( $key, $args, $value ); }
        $state_key = Data::state_key( $state ); $language = Data::chinese() ? 'zh' : 'en';
        $args['type'] = 'select';
        $args['input_class'] = array_merge( (array) ( $args['input_class'] ?? [] ), [ 'city_select', 'agneovo-city-select' ] );
        $args['options'] = [ '' => __( 'Select an option&hellip;', 'woocommerce' ) ];
        $matched = Data::find_city( $state, $value );
        if ( $state_key !== '' ) {
            foreach ( Data::all()[$state_key]['cities'] as $city ) {
                // Keep the selected stored English/Chinese value, including alternate glyphs.
                $option_value = $matched && $city['en'] === $matched['en'] ? (string) $value : $city[$language];
                $args['options'][$option_value] = $city[$language];
            }
        }
        if ( (string) $value !== '' && ! isset( $args['options'][(string) $value] ) ) { $args['options'][(string) $value] = (string) $value; }
        return woocommerce_form_field( $key, $args, $value );
    }

    public static function assets() {
        if ( ! function_exists( 'is_cart' ) || ! ( is_cart() || is_checkout() || is_wc_endpoint_url( 'edit-address' ) ) ) { return; }
        $file = dirname( __DIR__ ) . '/agneovo-city-select.php';
        wp_enqueue_script( 'agneovo-city-select', plugins_url( 'assets/city-select.js', $file ), [ 'jquery', 'woocommerce', 'wc-country-select', 'selectWoo' ], (string) filemtime( dirname( __DIR__ ) . '/assets/city-select.js' ), true );
        wp_localize_script( 'agneovo-city-select', 'agneovoCitySelectParams', [ 'states' => Data::frontend(), 'placeholder' => __( 'Select an option&hellip;', 'woocommerce' ) ] );
    }
}
