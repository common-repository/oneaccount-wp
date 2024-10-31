<?php

require_once "vendor/autoload.php";
require_once "OneaccountLibrary.php";
require_once "OneaccountTransientOneaccountEngine.php";
if (!defined('IDNA_DEFAULT')) {
    define('IDNA_DEFAULT', 0);
}
/*
Plugin Name: Oneaccount WP
Description: Secure universal authentication system
Version: 1.0.1
Author: Oila studio
Author URI: https://oneaccount.app
*/

/*  Copyright 2021  Oila studio  (email: contact@oneaccount.app)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

function oneaccount_scripts()
{
    wp_enqueue_script(
        'oneaccount',
        plugin_dir_url(__FILE__) . '/oneaccount.min.js',
        array(),
        false,
        true
    );
}

add_action('login_footer', 'oneaccount_register_form_add_block');

function oneaccount_register_form_add_block()
{
    ?>
    <div class="oneaccount-container">
        <button class="oneaccount-button oneaccount-show">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 300"
                 style="height: 18px; width: 18px; vertical-align: sub; margin-right: 10px;">
                <g fill="none" fill-rule="evenodd">
                    <mask id="a">
                        <rect width="100%" height="100%" fill="#fff"></rect>
                        <path fill="#000"
                              d="M148.65 225.12c-30.6-5.51-71.54-106.68-55.76-137.06 14.38-27.7 102.01-13.66 116.08 20.9 13.82 33.97-32.89 121.1-60.32 116.16zm-30.35-76.6c0 18.24 13.68 33.02 30.55 33.02s30.54-14.78 30.54-33.02c0-18.25-13.67-33.03-30.54-33.03-16.87 0-30.55 14.78-30.55 33.03z"></path>
                    </mask>
                    <path fill="#fff"
                          d="M153.27 298.95c60.25-10.84 140.8-209.72 109.75-269.44C234.72-24.95 62.25 2.66 34.57 70.6c-27.2 66.77 64.72 238.06 118.7 228.34z"
                          mask="url(#a)"></path>
                </g>
            </svg>
            Sign up with One account
        </button>
    </div>
    <?php
}

add_action('login_head', 'oneaccount_login_errors');

function oneaccount_login_errors()
{
    global $error;
    $message = sanitize_text_field($_REQUEST['error_message']);

    if ($message) {
        $error = $message;
    }
}

add_action('admin_menu', 'oneaccount_plugin_setup_menu');

function oneaccount_plugin_setup_menu()
{
    add_menu_page(
        'Oneaccount',
        'Oneaccount',
        'manage_options',
        'oneaccount-plugin',
        'oneaccount_admin',
        plugins_url('oneaccount/oneaccount.png')
    );
}

function oneaccount_admin()
{
    //must check that the user has the required capability
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    // variables for the field and option names
    $opt_name = 'oneaccount_api_key';
    $hidden_field_name = 'mt_submit_hidden';
    $data_field_name = 'oneaccount_api_key';

    // Read in existing option value from database
    $opt_val = get_option($opt_name);

    // See if the user has posted us some information
    // If they did, this hidden field will be set to 'Y'
    if (wp_verify_nonce(
            $_POST['nonce_code'],
            'oneaccount-nonce'
        ) && isset($_POST[$hidden_field_name]) && sanitize_text_field($_POST[$hidden_field_name]) == 'Y') {
        // Read their posted value
        $opt_val = sanitize_text_field($_POST[$data_field_name]);

        // Save the posted value in the database
        update_option($opt_name, $opt_val);

        // Put a "settings saved" message on the screen

        ?>
        <div class="updated"><p><strong><?php _e('settings saved.', 'menu-test'); ?></strong></p></div>
        <?php
    }

    // Now display the settings editing screen

    echo '<div class="wrap">';

    // header

    echo "<h2>" . __('One account Settings', 'oneaccount-settings') . "</h2>";

    // settings form

    ?>

    <form name="form1" method="post" action="">
        <input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">
        <input type="hidden" name="nonce_code" value="<?= wp_create_nonce('oneaccount-nonce') ?>">

        <p><?php _e("External ID:", 'oneaccount-api-key'); ?>
            <input type="text" style="min-width: 300px" name="<?php echo $data_field_name; ?>"
                   value="<?php echo $opt_val; ?>" size="20">
        </p>
        <hr/>

        <p class="submit">
            <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>"/>
        </p>

    </form>
    </div>

    <?php
}


add_action('wp_ajax_oneaccountauth', 'oneaccount_oneaccountauth');
add_action('wp_ajax_nopriv_oneaccountauth', 'oneaccount_oneaccountauth');
//wc_add_notice( apply_filters( 'login_errors', 'haha' ), 'error' );
if (!function_exists('getallheaders')) {
    function getallheaders()
    {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}
function oneaccount_oneaccountauth()
{
    global $wpdb;
    $headers = getallheaders();
    $oneAccount = new OneaccountLibrary(new OneaccountTransientOneaccountEngine(MINUTE_IN_SECONDS * 10));
    $data = json_decode(file_get_contents('php://input'), true);

    if (!($headers['Authorization'])) {
        $oneAccount->auth($data ?: $_POST, null);
        echo json_encode(['success' => true]);
    } else {
        try {
            try {
                $data = $oneAccount->auth($data, $headers['Authorization']);
                $user = get_user_by('email', $data['email']);
                if ($user !== false) {
                    wp_clear_auth_cookie();
                    wp_set_current_user($user->ID);
                    wp_set_auth_cookie($user->ID);
                } else {
                    $userId = register_new_user($data['login'] ?: $data['email'], $data['email']);

                    if ($userId instanceof WP_Error) {
                        wp_send_json_error(
                            [
                                'redirect_url' => wp_login_url() . '?error_message=' . $userId->get_error_message(),
                                'success' => false
                            ]
                        );
                        wp_die();
                    }

                    wp_clear_auth_cookie();
                    wp_set_current_user($userId);
                    wp_set_auth_cookie($userId);
                }
                echo json_encode(['success' => true]);

                wp_die();
            } catch (Throwable $exception) {
                $error = new WP_Error('001', 'No user information was retrieved: 001.', []);

                wp_send_json_error(
                    [
                        'redirect_url' => wp_login_url() . '?error_message=' . $error->get_error_message(),
                        'success' => false
                    ]
                );
                wp_die();
            }
        } catch (Throwable $exception) {
            $error = new WP_Error('002', 'No user information was retrieved: 002.', []);
            wp_send_json_error(
                [
                    'redirect_url' => wp_login_url() . '?error_message=' . $error->get_error_message(),
                    'success' => false
                ]
            );
            wp_die();
        }
    }


    wp_die(); // this is required to terminate immediately and return a proper response
}

function oneaccount_login_stylesheet()
{
    wp_enqueue_script(
        'oneaccount',
        plugin_dir_url(__FILE__) . '/oneaccount.min.js',
        array(),
        false,
        true
    );
    ?>
    <script>

        if (window.oneaccount) {
            initOneaccount();
        } else {
            document.addEventListener("oneaccount-loaded", initOneaccount);
        }

        // init the library
        function initOneaccount() {
            window.oneaccount.init("<?php echo get_option('oneaccount_api_key')?>", {
                // NOTE: Please check the Library options page for more customisations
                iOSRedirectURL: "<?php echo get_site_url()?>", // required
                callbackURL: "<?php echo admin_url('admin-ajax.php')?>?action=oneaccountauth", // required
            });
        }

        document.addEventListener("oneaccount-authenticated", function (event) {
            var data = event.detail;
            if (!data.success) {
                window.location.href = data.data.redirect_url;
            } else {
                window.location = "/";
            }
        });
    </script>
    <style>
        .oneaccount-container {
            margin: 0 auto;
            padding: 15px;
            color: #ffffff;
            text-align: center;
            font-size: 20px;
        }

        .oneaccount-button {
            width: 320px;
            background-color: rgb(250, 73, 0);
            color: white;
            font-size: 16px;
            border: 0;
            cursor: pointer;
            display: inline-block;
            min-height: 1em;
            outline: 0;
            border: none;
            vertical-align: baseline;
            font-family: "Nunito Sans", sans-serif;
            margin: 0 .25em 0 0;
            padding: .78571429em 1.5em !important;
            text-transform: none;
            text-shadow: none;
            font-weight: 700;
            line-height: 1em;
            font-style: normal;
            text-align: center;
            text-decoration: none;
            border-radius: .28571429rem;
            box-shadow: inset 0 0 0 1px transparent, inset 0 0 0 0 rgba(34, 36, 38, .15);
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
            -webkit-transition: opacity .1s ease, background-color .1s ease, color .1s ease, background .1s ease, -webkit-box-shadow .1s ease;
            -webkit-transition: opacity .1s ease, background-color .1s ease, color .1s ease, box-shadow .1s ease, background .1s ease;
            transition: opacity .1s ease, background-color .1s ease, color .1s ease, box-shadow .1s ease, background .1s ease;
            will-change: auto;
            -webkit-tap-highlight-color: transparent;

        }
    </style>
    <?php
}


add_action('login_enqueue_scripts', 'oneaccount_login_stylesheet');
?>
