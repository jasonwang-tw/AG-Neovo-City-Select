<?php
namespace AGNeovo\CitySelect;
defined( 'ABSPATH' ) || exit;

/** Read-only operational information and a local address preview. */
final class Admin {
    const SLUG = 'agneovo-city-select';
    const DATA_VERSION = '2026-11-19';
    private static $screen = '';

    public static function register() {
        add_action( 'admin_menu', [ __CLASS__, 'menu' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'assets' ] );
        add_filter( 'plugin_action_links_' . plugin_basename( dirname( __DIR__ ) . '/agneovo-city-select.php' ), [ __CLASS__, 'links' ] );
    }

    private static function capability() {
        return class_exists( 'WooCommerce' ) ? 'manage_woocommerce' : 'manage_options';
    }

    public static function url() {
        return admin_url( ( class_exists( 'WooCommerce' ) ? 'admin.php' : 'options-general.php' ) . '?page=' . self::SLUG );
    }

    public static function menu() {
        self::$screen = add_submenu_page(
            class_exists( 'WooCommerce' ) ? 'woocommerce' : 'options-general.php',
            'AG Neovo City Select', 'City Select', self::capability(), self::SLUG, [ __CLASS__, 'render' ]
        );
    }

    public static function links( $links ) {
        if ( current_user_can( self::capability() ) ) {
            array_unshift( $links, '<a href="' . esc_url( self::url() ) . '">' . esc_html__( '設定', 'agneovo-city-select' ) . '</a>' );
        }
        return $links;
    }

    public static function information() {
        $data = Data::all(); $count = 0; $healthy = count( $data ) === 22;
        foreach ( $data as $state ) {
            if ( ! is_array( $state ) || ! is_string( $state['en'] ?? null ) || ! is_string( $state['zh'] ?? null ) || trim( $state['en'] ) === '' || trim( $state['zh'] ) === '' ) { $healthy = false; continue; }
            $rows = $state['cities'] ?? [];
            if ( ! is_array( $rows ) ) { $healthy = false; continue; }
            $count += count( $rows );
            foreach ( $rows as $row ) {
                if ( ! is_array( $row ) || ! is_string( $row['en'] ?? null ) || ! is_string( $row['zh'] ?? null ) || trim( $row['en'] ) === '' || trim( $row['zh'] ) === '' || ! preg_match( '/^\d{3}$/D', (string) ( $row['postcode'] ?? '' ) ) ) { $healthy = false; }
            }
        }
        $healthy = $healthy && $count === 370;
        if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'WC' ) ) {
            $status = 'dependency'; $title = __( '需要 WooCommerce', 'agneovo-city-select' );
            $detail = __( 'WooCommerce 尚未載入，地址欄位不會接管。', 'agneovo-city-select' );
        } elseif ( ! $healthy ) {
            $status = 'data'; $title = __( '地址資料需要檢查', 'agneovo-city-select' );
            $detail = __( '資料數量或格式與本版基準不符，請先核對外掛檔案。', 'agneovo-city-select' );
        } elseif ( Plugin::legacy_active() ) {
            $status = 'standby'; $title = __( '待命：RY City Select 啟用中', 'agneovo-city-select' );
            $detail = __( '目前由 RY 提供地址欄位；停用 RY 後，本外掛會自動接手。', 'agneovo-city-select' );
        } elseif ( has_filter( 'woocommerce_form_field_city', [ Frontend::class, 'field' ] ) !== false && has_action( 'wp_enqueue_scripts', [ Frontend::class, 'assets' ] ) !== false ) {
            $status = 'active'; $title = __( '已接管地址欄位', 'agneovo-city-select' );
            $detail = __( '地址欄位與頁面資源掛鉤已註冊；實際結帳、地址儲存與超商選店仍需在商店頁面驗證。', 'agneovo-city-select' );
        } else {
            $status = 'inactive'; $title = __( '地址欄位尚未接管', 'agneovo-city-select' );
            $detail = __( '請確認 WooCommerce 已完成載入，以及是否有其他程式移除地址掛鉤。', 'agneovo-city-select' );
        }
        $header = get_file_data( dirname( __DIR__ ) . '/agneovo-city-select.php', [ 'version' => 'Version' ] );
        return [ 'version' => $header['version'], 'data_version' => self::DATA_VERSION, 'states' => count( $data ), 'cities' => $count, 'healthy' => $healthy, 'status' => $status, 'title' => $title, 'detail' => $detail ];
    }

    public static function preview_data() {
        $states = []; $language = Data::chinese() ? 'zh' : 'en';
        foreach ( Data::all() as $key => $state ) {
            $cities = [];
            foreach ( $state['cities'] as $city ) {
                $cities[] = [ 'value' => $city['en'], 'label' => $city[$language], 'postcode' => $city['postcode'], 'sap' => Data::normalize( $city['en'], $key ) ];
            }
            $states[$key] = [ 'label' => $state[$language], 'sap' => Data::normalize( $key ), 'cities' => $cities ];
        }
        return [
            'states' => $states,
            'texts' => [
                'state' => __( '請選擇縣市', 'agneovo-city-select' ),
                'city' => __( '請選擇鄉鎮區', 'agneovo-city-select' ),
                'empty' => __( '尚未選擇', 'agneovo-city-select' ),
                'ready' => __( '請選擇縣市與鄉鎮區以查看結果。', 'agneovo-city-select' ),
                'complete' => __( '預覽已更新；沒有儲存地址或傳送至 SAP。', 'agneovo-city-select' ),
            ],
        ];
    }

    public static function assets( $hook ) {
        if ( ! self::$screen || $hook !== self::$screen || ! current_user_can( self::capability() ) || ! self::information()['healthy'] ) { return; }
        $file = dirname( __DIR__ ) . '/assets/admin.js';
        wp_enqueue_script( 'agneovo-city-select-admin', plugins_url( 'assets/admin.js', dirname( __DIR__ ) . '/agneovo-city-select.php' ), [], (string) filemtime( $file ), true );
        wp_localize_script( 'agneovo-city-select-admin', 'agneovoCitySelectAdmin', self::preview_data() );
    }

    public static function render() {
        if ( ! current_user_can( self::capability() ) ) {
            wp_die( esc_html__( '您沒有權限查看此頁面。', 'agneovo-city-select' ), '', [ 'response' => 403 ] );
            return;
        }
        $info = self::information();
        $notice = $info['status'] === 'active' ? 'success' : ( in_array( $info['status'], [ 'standby', 'inactive' ], true ) ? 'warning' : 'error' );
        ?>
        <div class="wrap" id="agneovo-city-select-admin">
            <h1>AG Neovo City Select</h1>
            <p><?php esc_html_e( '運作資訊與地址測試。本頁為唯讀，不需要儲存設定。', 'agneovo-city-select' ); ?></p>
            <h2><?php esc_html_e( '運作狀態', 'agneovo-city-select' ); ?></h2>
            <div class="notice notice-<?php echo esc_attr( $notice ); ?> inline"><p><strong><?php echo esc_html( $info['title'] ); ?></strong></p><p><?php echo esc_html( $info['detail'] ); ?></p></div>
            <table class="form-table" role="presentation"><tbody>
                <tr><th scope="row"><?php esc_html_e( '外掛版本', 'agneovo-city-select' ); ?></th><td><?php echo esc_html( $info['version'] ); ?></td></tr>
                <tr><th scope="row"><?php esc_html_e( '套用範圍', 'agneovo-city-select' ); ?></th><td><?php esc_html_e( '台灣傳統結帳（帳單／收件地址）、會員編輯地址、購物車運費計算。', 'agneovo-city-select' ); ?></td></tr>
                <tr><th scope="row"><?php esc_html_e( '郵遞區號行為', 'agneovo-city-select' ); ?></th><td><?php esc_html_e( '選擇鄉鎮後帶入三碼；載入或 AJAX 更新時，保留既有地址與人工較精確郵遞區號。', 'agneovo-city-select' ); ?></td></tr>
                <tr><th scope="row"><?php esc_html_e( 'SAP 地址轉換', 'agneovo-city-select' ); ?></th><td><?php esc_html_e( '使用本地中英文對照，不依賴 RY 翻譯；中文與未知既有值保持原樣。', 'agneovo-city-select' ); ?></td></tr>
            </tbody></table>
            <h2><?php esc_html_e( '地址資料', 'agneovo-city-select' ); ?></h2>
            <table class="form-table" role="presentation"><tbody>
                <tr><th scope="row"><?php esc_html_e( '資料版本', 'agneovo-city-select' ); ?></th><td><?php echo esc_html( $info['data_version'] ); ?></td></tr>
                <tr><th scope="row"><?php esc_html_e( '資料數量', 'agneovo-city-select' ); ?></th><td><?php echo esc_html( sprintf( __( '%1$d 個縣市、%2$d 筆地區／郵政選項', 'agneovo-city-select' ), $info['states'], $info['cities'] ) ); ?><p class="description"><?php esc_html_e( '包含東沙／南沙郵政選項，選項數不等同純行政區數。', 'agneovo-city-select' ); ?></p></td></tr>
                <tr><th scope="row"><?php esc_html_e( '資料檢查', 'agneovo-city-select' ); ?></th><td><?php echo esc_html( $info['healthy'] ? __( '數量與基本格式符合本版基準', 'agneovo-city-select' ) : __( '未通過，請核對外掛檔案', 'agneovo-city-select' ) ); ?></td></tr>
                <tr><th scope="row"><?php esc_html_e( '資料來源', 'agneovo-city-select' ); ?></th><td><?php esc_html_e( 'RY City Select 2.3.0 台灣資料及 WordPress.org 繁中翻譯快照；依 GPLv3 保留來源。', 'agneovo-city-select' ); ?></td></tr>
            </tbody></table>
            <h2><?php esc_html_e( '地址測試', 'agneovo-city-select' ); ?></h2>
            <p id="agneovo-city-test-help"><?php esc_html_e( '使用本地字典預覽，不讀取會員／訂單、不儲存地址，也不呼叫 SAP 或物流服務。', 'agneovo-city-select' ); ?></p>
            <?php if ( $info['healthy'] ) : ?>
                <noscript><div class="notice notice-warning inline"><p><?php esc_html_e( '地址測試需要啟用 JavaScript；其餘資訊仍可查看。', 'agneovo-city-select' ); ?></p></div></noscript>
                <table class="form-table" role="presentation"><tbody>
                    <tr><th scope="row"><label for="agneovo-city-test-state"><?php esc_html_e( '縣市', 'agneovo-city-select' ); ?></label></th><td><select id="agneovo-city-test-state" aria-describedby="agneovo-city-test-help"><option value=""><?php esc_html_e( '請選擇縣市', 'agneovo-city-select' ); ?></option></select></td></tr>
                    <tr><th scope="row"><label for="agneovo-city-test-city"><?php esc_html_e( '鄉鎮區', 'agneovo-city-select' ); ?></label></th><td><select id="agneovo-city-test-city" disabled aria-describedby="agneovo-city-test-help"><option value=""><?php esc_html_e( '請選擇鄉鎮區', 'agneovo-city-select' ); ?></option></select></td></tr>
                    <tr><th scope="row"><?php esc_html_e( '郵遞區號', 'agneovo-city-select' ); ?></th><td><output id="agneovo-city-test-postcode" for="agneovo-city-test-state agneovo-city-test-city"><?php esc_html_e( '尚未選擇', 'agneovo-city-select' ); ?></output></td></tr>
                    <tr><th scope="row"><?php esc_html_e( 'SAP 中文地址', 'agneovo-city-select' ); ?></th><td><output id="agneovo-city-test-sap" for="agneovo-city-test-state agneovo-city-test-city"><?php esc_html_e( '尚未選擇', 'agneovo-city-select' ); ?></output></td></tr>
                </tbody></table>
                <p><button type="button" class="button button-secondary" id="agneovo-city-test-reset"><?php esc_html_e( '重設測試', 'agneovo-city-select' ); ?></button></p>
                <p id="agneovo-city-test-message" role="status" aria-live="polite"><?php esc_html_e( '請選擇縣市與鄉鎮區以查看結果。', 'agneovo-city-select' ); ?></p>
            <?php else : ?>
                <p><?php esc_html_e( '資料檢查未通過，地址測試暫停使用。', 'agneovo-city-select' ); ?></p>
            <?php endif; ?>
            <h2><?php esc_html_e( '操作與回復說明', 'agneovo-city-select' ); ?></h2>
            <ol>
                <li><?php esc_html_e( '先查看運作狀態，再選擇縣市／鄉鎮，確認三碼郵遞區號與 SAP 中文預覽。', 'agneovo-city-select' ); ?></li>
                <li><?php esc_html_e( '使用現有會員登入，驗證會員帳單／收件地址、購物車運費、結帳不同收件地址及超商選店；本頁測試不等同完成上述流程驗收。', 'agneovo-city-select' ); ?></li>
                <li><?php esc_html_e( '若需回復舊介面，重新啟用 RY City Select；本外掛會自動待命。保留原外掛與會員／訂單資料。', 'agneovo-city-select' ); ?></li>
                <li><?php esc_html_e( '配送國家、配送區與運費仍由 WooCommerce 管理。六碼門牌判定、其他國家及 Checkout Block 為後續擴充項目。', 'agneovo-city-select' ); ?></li>
            </ol>
            <?php if ( class_exists( 'WooCommerce' ) ) : ?><p><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=shipping' ) ); ?>"><?php esc_html_e( 'WooCommerce 配送設定', 'agneovo-city-select' ); ?></a></p><?php endif; ?>
        </div>
        <?php
    }
}
