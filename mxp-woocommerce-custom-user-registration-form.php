<?php
/*
Plugin Name: WooCommerce 註冊頁面表單客製化
Plugin URI:
Description: 調整 WooCommerce 註冊頁面表單欄位
Author: Chun
Version: 1.0
Author URI: https://www.mxp.tw/
 */

if (!defined('WPINC')) {
    die;
}

if (!defined("MXP_WCREG_FORM_VER")) {
    define("MXP_WCREG_FORM_VER", '1.0');
}

if (!defined("MXP_WCREG_FORM_SLUG")) {
    define("MXP_WCREG_FORM_SLUG", 'mxp-wcreg-form');
}

if (!defined("MXP_WCREG_FORM_DIR")) {
    define("MXP_WCREG_FORM_DIR", plugin_dir_path(__FILE__));
}

if (!defined("MXP_WCREG_FORM_URL")) {
    define("MXP_WCREG_FORM_URL", plugins_url('', __FILE__));
}

if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    // require_once ABSPATH . 'wp-admin/includes/plugin.php';
    // deactivate_plugins(plugin_basename(__FILE__));
    function mxp_deactivate_wc_reg_form_notice_error_type1() {
        $class   = 'notice notice-error';
        $message = '啟用此「註冊頁面表單」外掛必須先啟用 WooCommerce。';
        printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
    }
    add_action('admin_notices', 'mxp_deactivate_wc_reg_form_notice_error_type1');
    return;
}

function mxp_wc_reg_translate_text($translated) {
    if ('使用者名稱' === $translated) {
        return '手機號碼';
    }
    if ('電子郵件地址' === $translated) {
        return '信箱';
    }
    if ('使用者名稱 或 電子郵件' === $translated) {
        return '手機號碼 或 信箱';
    }
    return $translated;
}

add_filter('gettext', 'mxp_wc_reg_translate_text', 111);

// 增加欄位
function mxp_add_register_form_field() {
    woocommerce_form_field(
        'fullname',
        array(
            'type'     => 'text',
            'required' => true,
            'label'    => '姓名',
        ),
        (isset($_POST['fullname']) ? $_POST['fullname'] : '')
    );
    woocommerce_form_field(
        'nickname',
        array(
            'type'     => 'text',
            'required' => true,
            'label'    => '暱稱',
        ),
        (isset($_POST['nickname']) ? $_POST['nickname'] : '')
    );
    woocommerce_form_field(
        'birthday',
        array(
            'type'     => 'date',
            'required' => true,
            'label'    => '出生年月日',
        ),
        (isset($_POST['birthday']) ? $_POST['birthday'] : '')
    );
    woocommerce_form_field(
        'billing_state',
        array(
            'type'     => 'text',
            'required' => true,
            'label'    => '縣/市',
        ),
        (isset($_POST['billing_state']) ? $_POST['billing_state'] : '')
    );
    woocommerce_form_field(
        'billing_city',
        array(
            'type'     => 'text',
            'required' => true,
            'label'    => '鄉鎮市區',
        ),
        (isset($_POST['billing_city']) ? $_POST['billing_city'] : '')
    );
    woocommerce_form_field(
        'billing_postcode',
        array(
            'type'     => 'number',
            'required' => true,
            'label'    => '郵遞區號',
        ),
        (isset($_POST['billing_postcode']) ? $_POST['billing_postcode'] : '')
    );
    woocommerce_form_field(
        'billing_address_1',
        array(
            'type'     => 'text',
            'required' => true,
            'label'    => '街道地址',
        ),
        (isset($_POST['billing_address_1']) ? $_POST['billing_address_1'] : '')
    );
}
add_action('woocommerce_register_form', 'mxp_add_register_form_field');

// 驗證請求
function mxp_validate_fields($username, $email, $errors) {
    if (!preg_match('/^09\d{8}$/', $username)) {
        $errors->add('username_error', '手機號碼格式錯誤。');
    }
    if (empty($_POST['fullname'])) {
        $errors->add('fullname_error', '姓名為必填欄位。');
    }
    if (empty($_POST['nickname'])) {
        $errors->add('nickname_error', '暱稱為必填欄位。');
    }
    if (empty($_POST['birthday'])) {
        $errors->add('birthday_error', '生日為必填欄位。');
    }
    if (empty($_POST['billing_state'])) {
        $errors->add('billing_state_error', '縣市為必填欄位。');
    }
    if (empty($_POST['billing_city'])) {
        $errors->add('billing_city_error', '鄉鎮市區為必填欄位。');
    }
    if (empty($_POST['billing_postcode'])) {
        $errors->add('billing_postcode_error', '郵遞區號為必填欄位。');
    }
    if (empty($_POST['billing_address_1'])) {
        $errors->add('billing_address_1_error', '街道地址為必填欄位。');
    }
    if (empty($_POST['otp_code'])) {
        $errors->add('otp_code_error', '請點選「取得驗證碼」接收簡訊驗證手機號碼。');
    } else {
        if (!mxp_verify_otp($_POST['otp_code'], $_POST['otp_timestamp'])) {
            $errors->add('otp_code_error', '簡訊驗證碼驗證失敗，請再試一次。');
            setcookie('otp_code_error', wc_clean($_POST['otp_code'] . '|' . wc_clean($_POST['otp_timestamp'])), time() + 3600, COOKIEPATH, COOKIE_DOMAIN);
        } else {
            // 驗證成功，清除 Cookie
            setcookie('otp_code_error', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
        }
    }
}
add_action('woocommerce_register_post', 'mxp_validate_fields', 10, 3);

// 儲存欄位資料
function mxp_save_register_fields($customer_id) {

    if (!empty($_POST['fullname'])) {
        update_user_meta($customer_id, 'first_name', wc_clean($_POST['fullname']));
        update_user_meta($customer_id, 'billing_first_name', wc_clean($_POST['fullname']));
    }
    if (!empty($_POST['nickname'])) {
        wp_update_user(array('ID' => $customer_id, 'display_name' => wc_clean($_POST['nickname'])));
    }
    if (!empty($_POST['birthday'])) {
        update_user_meta($customer_id, 'birthday', wc_clean($_POST['birthday']));
    }
    if (!empty($_POST['billing_state'])) {
        update_user_meta($customer_id, 'billing_state', wc_clean($_POST['billing_state']));
    }
    if (!empty($_POST['billing_city'])) {
        update_user_meta($customer_id, 'billing_city', wc_clean($_POST['billing_city']));
    }
    if (!empty($_POST['billing_postcode'])) {
        update_user_meta($customer_id, 'billing_postcode', wc_clean($_POST['billing_postcode']));
    }
    if (!empty($_POST['billing_address_1'])) {
        update_user_meta($customer_id, 'billing_address_1', wc_clean($_POST['billing_address_1']));
    }
    $user_info = get_userdata($customer_id);
    update_user_meta($customer_id, 'billing_phone', $user_info->user_login);
    update_user_meta($customer_id, 'billing_country', 'TW');
}
add_action('woocommerce_created_customer', 'mxp_save_register_fields');

// 引入前端 JS 邏輯功能
function mxp_wc_reg_form_assets() {
    wp_register_script(MXP_WCREG_FORM_SLUG . '-js', MXP_WCREG_FORM_URL . '/js/main.js', array('jquery'), MXP_WCREG_FORM_VER, false);
    wp_register_script(MXP_WCREG_FORM_SLUG . '-twzipcode-js', MXP_WCREG_FORM_URL . '/js/jquery.twzipcode.min.js', array('jquery'), MXP_WCREG_FORM_VER, false);
    if (is_account_page()) {
        wp_localize_script(MXP_WCREG_FORM_SLUG . '-js', 'MXP', array(
            'ajaxurl'           => admin_url('admin-ajax.php'),
            'is_user_logged_in' => is_user_logged_in() ? 'yes' : 'no',

            'nonce'             => wp_create_nonce(MXP_WCREG_FORM_SLUG . '-page'),
        ));
        wp_enqueue_script(MXP_WCREG_FORM_SLUG . '-twzipcode-js');
        wp_enqueue_script(MXP_WCREG_FORM_SLUG . '-js');
    }
}
add_action('wp_enqueue_scripts', 'mxp_wc_reg_form_assets');