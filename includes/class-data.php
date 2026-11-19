<?php
namespace AGNeovo\CitySelect;
defined( 'ABSPATH' ) || exit;

final class Data {
    private static $data;

    public static function all() {
        if ( self::$data === null ) {
            $raw = file_get_contents( dirname( __DIR__ ) . '/data/taiwan-addresses.json' );
            $data = json_decode( (string) $raw, true );
            self::$data = is_array( $data ) ? $data : [];
        }
        return self::$data;
    }

    private static function key( $value ) {
        $value = trim( preg_replace( '/\s+/u', ' ', (string) $value ) );
        return mb_strtoupper( str_replace( '臺', '台', $value ), 'UTF-8' );
    }

    public static function chinese() {
        return strpos( determine_locale(), 'zh' ) === 0;
    }

    public static function state_key( $value ) {
        $needle = self::key( $value );
        foreach ( self::all() as $key => $state ) {
            foreach ( [ $key, $state['en'], $state['zh'] ] as $alias ) {
                if ( self::key( $alias ) === $needle ) { return $key; }
            }
        }
        return '';
    }

    public static function states() {
        $states = []; $language = self::chinese() ? 'zh' : 'en';
        foreach ( self::all() as $key => $state ) { $states[$key] = $state[$language]; }
        return $states;
    }

    public static function find_city( $state, $value ) {
        $key = self::state_key( $state ); $needle = self::key( $value );
        if ( $key === '' || $needle === '' ) { return null; }
        foreach ( self::all()[$key]['cities'] as $city ) {
            if ( self::key( $city['en'] ) === $needle || self::key( $city['zh'] ) === $needle ) { return $city; }
        }
        return null;
    }

    /** Always Chinese for ERP output; existing Chinese and unknown values stay untouched. */
    public static function normalize( $value, $state = '' ) {
        $value = trim( (string) $value );
        if ( $value === '' || preg_match( '/\p{Han}/u', $value ) ) { return $value; }
        if ( $state === '' ) {
            $key = self::state_key( $value );
            return $key !== '' ? str_replace( '臺', '台', self::all()[$key]['zh'] ) : $value;
        }
        $city = self::find_city( $state, $value );
        return $city ? $city['zh'] : $value;
    }

    public static function frontend() {
        $data = []; $language = self::chinese() ? 'zh' : 'en';
        foreach ( self::all() as $key => $state ) {
            $rows = [];
            foreach ( $state['cities'] as $city ) { $rows[] = [ 'name' => $city[$language], 'aliases' => [ $city['en'], $city['zh'], str_replace( '台', '臺', $city['zh'] ) ], 'postcode' => $city['postcode'] ]; }
            $data[$key] = [ 'aliases' => [ $key, $state['en'], $state['zh'], str_replace( '台', '臺', $state['zh'] ) ], 'cities' => $rows ];
        }
        return $data;
    }
}
