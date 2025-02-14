<?php
/*
Plugin Name: SNN Cookie Banner
Requires PHP: 8.0
Description: A plugin to manage cookie consent and dynamically block scripts (e.g. Google Analytics) until the user accepts cookies. Now includes support for Google Consent Mode v2 integration.
Author: sinanisler.com
Author URI: https://sinanisler.com/
Version: 0.3
*/


if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Define option key constant
define('SNN_OPTIONS', 'snn_cookie_options');

/* ============================================================================
   ADMIN SETTINGS PAGE & FORM
============================================================================ */

// Add a custom admin menu item at a high position value so it appears at the bottom
function snn_add_admin_menu() {
    add_menu_page(
        'SNN Cookie Settings',        // Page title
        'SNN Cookie',                 // Menu title
        'manage_options',             // Capability
        'snn_cookie',                 // Menu slug
        'snn_options_page',           // Callback function
        'dashicons-shield',           // Icon
        10000                         // Position (high value to push it down)
    );
}
add_action('admin_menu', 'snn_add_admin_menu');

// Render the settings page with two tabs: General and Scripts & Services
function snn_options_page() {
    // Handle form submission
    if ( isset($_POST['snn_options_nonce']) && wp_verify_nonce( $_POST['snn_options_nonce'], 'snn_save_options' ) ) {
        $options = array();
        // ----- General Settings Tab -----
        $options['banner_description'] = sanitize_text_field( $_POST['banner_description'] );
        $options['accept_button']      = sanitize_text_field( $_POST['accept_button'] );
        $options['deny_button']        = sanitize_text_field( $_POST['deny_button'] );
        $options['preferences_button'] = sanitize_text_field( $_POST['preferences_button'] );
        $options['banner_position']    = sanitize_text_field( $_POST['banner_position'] );
        $options['banner_bg_color']    = sanitize_text_field( $_POST['banner_bg_color'] );
        $options['banner_text_color']  = sanitize_text_field( $_POST['banner_text_color'] );
        $options['button_bg_color']    = sanitize_text_field( $_POST['button_bg_color'] );
        $options['button_text_color']  = sanitize_text_field( $_POST['button_text_color'] );
        
        // ----- Scripts & Services Tab -----
        // Consent Mode checkbox moved to this tab.
        $options['enable_consent_mode'] = isset($_POST['enable_consent_mode']) ? 'yes' : 'no';
        
        $services = array();
        if ( isset($_POST['services']) && is_array($_POST['services']) ) {
            foreach( $_POST['services'] as $service ) {
                $services[] = sanitize_text_field( $service );
            }
        }
        $options['services']           = $services;
        $options['script_head']        = $_POST['script_head'];         // Allow HTML
        $options['script_body_top']    = $_POST['script_body_top'];       // Allow HTML
        $options['script_body_bottom'] = $_POST['script_body_bottom'];    // Allow HTML
        $options['custom_css']         = $_POST['custom_css'];            // Allow full CSS
        
        update_option( SNN_OPTIONS, $options );
        echo '<div class="updated"><p>Settings saved.</p></div>';
    }
    
    // Get existing options or set defaults
    $options = get_option( SNN_OPTIONS );
    if ( !is_array($options) ) {
        $options = array(
            'banner_description'   => 'This website uses cookies for analytics and functionality.',
            'accept_button'        => 'Accept',
            'deny_button'          => 'Deny',
            'preferences_button'   => 'Preferences',
            'services'             => array('Google Analytics', 'Facebook Pixel'),
            'script_head'          => '',
            'script_body_top'      => '',
            'script_body_bottom'   => '',
            'custom_css'           => '',
            'banner_position'      => 'left',
            'banner_bg_color'      => '#333333',
            'banner_text_color'    => '#ffffff',
            'button_bg_color'      => '#555555',
            'button_text_color'    => '#ffffff',
            'enable_consent_mode'  => 'yes'
        );
    }
    ?>
    <div class="wrap">
        <h1>SNN Cookie Banner Plugin Settings</h1>
        <!-- Admin CSS -->
        <style>
            .snn-textarea { width: 500px; }
            .snn-input { width: 300px; }
            .snn-color-picker { }
            .snn-services-repeater .snn-service-item { margin-bottom: 5px; }
            .snn-custom-css-textarea { width: 500px; }
            /* Tabs styling */
            .snn-tabs { margin-bottom: 20px; }
            .snn-tab { cursor:pointer; display: inline-block; margin-right: 10px; padding: 8px 12px; border: 1px solid #ccc; border-bottom: none; background: #f1f1f1; }
            .snn-tab.active { background: #fff; font-weight: bold; }
            .snn-tab-content { border: 1px solid #ccc; padding: 15px; display: none; }
            .snn-tab-content.active { display: block; }
        </style>
        <!-- Tabs Navigation -->
        <div class="snn-tabs">
            <span class="snn-tab active" data-tab="general">General Settings</span>
            <span class="snn-tab" data-tab="scripts">Scripts &amp; Services</span>
        </div>
        <form method="post">
            <?php wp_nonce_field( 'snn_save_options', 'snn_options_nonce' ); ?>
            <!-- General Settings Tab Content -->
            <div id="general" class="snn-tab-content active">
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Cookie Banner Description</th>
                        <td>
                            <textarea name="banner_description" rows="3" class="snn-textarea snn-banner-description"><?php echo esc_textarea( $options['banner_description'] ); ?></textarea>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Accept Button Text</th>
                        <td>
                            <input type="text" name="accept_button" value="<?php echo esc_attr( $options['accept_button'] ); ?>" class="snn-input snn-accept-button">
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Deny Button Text</th>
                        <td>
                            <input type="text" name="deny_button" value="<?php echo esc_attr( $options['deny_button'] ); ?>" class="snn-input snn-deny-button">
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Preferences Button Text</th>
                        <td>
                            <input type="text" name="preferences_button" value="<?php echo esc_attr( $options['preferences_button'] ); ?>" class="snn-input snn-preferences-button">
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Cookie Banner Position</th>
                        <td>
                            <select name="banner_position" class="snn-select snn-banner-position">
                                <option value="left" <?php selected($options['banner_position'], 'left'); ?>>Left</option>
                                <option value="middle" <?php selected($options['banner_position'], 'middle'); ?>>Middle</option>
                                <option value="right" <?php selected($options['banner_position'], 'right'); ?>>Right</option>
                            </select>
                            <p class="description">Select the horizontal position of the cookie banner on your website.</p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Cookie Banner Background Color</th>
                        <td>
                            <input type="color" name="banner_bg_color" value="<?php echo esc_attr($options['banner_bg_color']); ?>" class="snn-color-picker">
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Cookie Banner Text Color</th>
                        <td>
                            <input type="color" name="banner_text_color" value="<?php echo esc_attr($options['banner_text_color']); ?>" class="snn-color-picker">
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Button Background Color</th>
                        <td>
                            <input type="color" name="button_bg_color" value="<?php echo esc_attr($options['button_bg_color']); ?>" class="snn-color-picker">
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Button Text Color</th>
                        <td>
                            <input type="color" name="button_text_color" value="<?php echo esc_attr($options['button_text_color']); ?>" class="snn-color-picker">
                        </td>
                    </tr>
                </table>
            </div>
            <!-- Scripts & Services Tab Content -->
            <div id="scripts" class="snn-tab-content">
                <table class="form-table">
                    <!-- New: Consent Mode checkbox (first item) -->
                    <tr valign="top">
                        <th scope="row">Enable Google Consent Mode v2</th>
                        <td>
                            <input type="checkbox" name="enable_consent_mode" value="yes" <?php checked( ($options['enable_consent_mode'] ?? 'no'), 'yes' ); ?>>
                            <span class="description">When enabled, the plugin will update Google Consent Mode (gtag) based on user consent.</span>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Services (Repeater)</th>
                        <td>
                            <div id="services-repeater" class="snn-services-repeater">
                                <?php 
                                if ( ! empty($options['services']) && is_array($options['services']) ) {
                                    foreach ( $options['services'] as $service ) {
                                        ?>
                                        <div class="snn-service-item">
                                            <input type="text" name="services[]" value="<?php echo esc_attr( $service ); ?>" class="snn-input snn-service-input">
                                            <button class="remove-service snn-remove-service button">Remove</button>
                                        </div>
                                        <?php
                                    }
                                } else {
                                    ?>
                                    <div class="snn-service-item">
                                        <input type="text" name="services[]" value="" class="snn-input snn-service-input">
                                        <button class="remove-service snn-remove-service button">Remove</button>
                                    </div>
                                    <?php
                                }
                                ?>
                            </div>
                            <button id="add-service" class="button snn-add-service">Add Service</button>
                            <script>
                            (function($){
                                $(document).ready(function(){
                                    $('#add-service').click(function(e){
                                        e.preventDefault();
                                        var newField = '<div class="snn-service-item"><input type="text" name="services[]" value="" class="snn-input snn-service-input"> <button class="remove-service snn-remove-service button">Remove</button></div>';
                                        $('#services-repeater').append(newField);
                                    });
                                    $('#services-repeater').on('click', '.remove-service', function(e){
                                        e.preventDefault();
                                        $(this).closest('.snn-service-item').remove();
                                    });
                                });
                            })(jQuery);
                            </script>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Script in Head (last item)</th>
                        <td>
                            <textarea name="script_head" rows="5" class="snn-textarea snn-script-textarea"><?php echo esc_textarea( $options['script_head'] ); ?></textarea>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Script in Body (top)</th>
                        <td>
                            <textarea name="script_body_top" rows="5" class="snn-textarea snn-script-textarea"><?php echo esc_textarea( $options['script_body_top'] ); ?></textarea>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Script in Body (bottom)</th>
                        <td>
                            <textarea name="script_body_bottom" rows="5" class="snn-textarea snn-script-textarea"><?php echo esc_textarea( $options['script_body_bottom'] ); ?></textarea>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Custom CSS for Cookie Banner</th>
                        <td>
                            <textarea name="custom_css" rows="5" class="snn-textarea snn-custom-css-textarea"><?php echo isset($options['custom_css']) ? esc_textarea($options['custom_css']) : ''; ?></textarea>
                            <p class="description">
                                Use the following CSS selectors to style the banner:<br>
                                <code>.snn-cookie-banner</code> - The cookie banner container<br>
                                <code>.snn-preferences-content</code> - The preferences content container inside the banner<br>
                                <code>.snn-banner-text</code> - The banner text<br>
                                <code>.snn-banner-buttons .snn-button</code> - The banner buttons (Accept, Deny, Preferences)<br>
                                <code>.snn-preferences-title</code> - The title in the preferences content<br>
                                <code>.snn-services-list</code> - The list of services<br>
                                <code>.snn-service-item</code> - Each individual service item
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
            <?php submit_button(); ?>
        </form>
        <!-- Tabs Script -->
        <script>
        (function($){
            $(document).ready(function(){
                $('.snn-tab').click(function(){
                    var tab = $(this).data('tab');
                    $('.snn-tab').removeClass('active');
                    $(this).addClass('active');
                    $('.snn-tab-content').removeClass('active');
                    $('#' + tab).addClass('active');
                });
            });
        })(jQuery);
        </script>
    </div>
    <?php
}

/* ============================================================================
   FRONTEND COOKIE BANNER, CUSTOM CSS & SCRIPT ENCODING
============================================================================ */

/**
 * 1) Show the cookie banner if the user hasn't made a choice yet
 */
function snn_output_cookie_banner() {
    if ( isset( $_COOKIE['snn_cookie_accepted'] ) ) {
        // User has already accepted or denied – do not show the banner.
        return;
    }

    $options = get_option( SNN_OPTIONS );
    if ( ! $options ) {
        $options = array(
            'banner_description' => 'This website uses cookies for analytics and functionality.',
            'accept_button'      => 'Accept',
            'deny_button'        => 'Deny',
            'preferences_button' => 'Preferences',
            'services'           => array('Google Analytics', 'Facebook Pixel'),
            'banner_position'    => 'left',
            'banner_bg_color'    => '#333333',
            'banner_text_color'  => '#ffffff',
            'button_bg_color'    => '#555555',
            'button_text_color'  => '#ffffff'
        );
    }
    
    // Determine banner position class and output dynamic CSS
    $position = isset($options['banner_position']) ? $options['banner_position'] : 'left';
    ?>
    <style id="snn-dynamic-styles">
    .snn-cookie-banner {
       position: fixed;
       bottom: 0;
       width: 500px;
       z-index: 9999;
       padding: 15px;
       background: <?php echo esc_attr($options['banner_bg_color']); ?>;
       color: <?php echo esc_attr($options['banner_text_color']); ?>;
    }
    .snn-cookie-banner.left { left: 0; }
    .snn-cookie-banner.middle { left: 50%; transform: translateX(-50%); }
    .snn-cookie-banner.right { right: 0; }
    
    .snn-preferences-content {
        display: none;
        margin-top: 15px;
        border-top: 1px solid #ccc;
        padding-top: 15px;
    }
    .snn-banner-buttons .snn-button {
        margin-right: 10px;
        background: <?php echo esc_attr($options['button_bg_color']); ?>;
        color: <?php echo esc_attr($options['button_text_color']); ?>;
        border: none;
        padding: 8px 12px;
        cursor: pointer;
    }
    .snn-banner-buttons .snn-button:last-child {
       margin-right: 0;
    }
    .snn-preferences-title {
        margin-top: 0;
    }
    </style>
    <div id="snn-cookie-banner" class="snn-cookie-banner <?php echo esc_attr($position); ?>">
        <div class="snn-preferences-content">
            <h2 class="snn-preferences-title">Cookie Preferences</h2>
            <?php if ( isset($options['services']) && is_array($options['services']) ) { ?>
                <ul class="snn-services-list">
                <?php foreach ( $options['services'] as $service ) { ?>
                    <li class="snn-service-item"><?php echo esc_html( $service ); ?></li>
                <?php } ?>
                </ul>
            <?php } ?>
        </div>
        <p class="snn-banner-text"><?php echo esc_html( $options['banner_description'] ); ?></p>
        <div class="snn-banner-buttons">
            <button class="snn-button snn-accept"><?php echo esc_html( $options['accept_button'] ); ?></button>
            <button class="snn-button snn-deny"><?php echo esc_html( $options['deny_button'] ); ?></button>
            <button class="snn-button snn-preferences"><?php echo esc_html( $options['preferences_button'] ); ?></button>
        </div>
    </div>
    <?php
}
add_action('wp_footer', 'snn_output_cookie_banner');

/**
 * 2) Always output the scripts as Base64-encoded data in hidden divs
 */

// a) Script in <head> (encoded)
function snn_store_script_head() {
    $options = get_option( SNN_OPTIONS );
    if ( !empty($options['script_head']) ) {
        ?>
        <div 
            id="snn-script-head" 
            data-script="<?php echo esc_attr( base64_encode($options['script_head']) ); ?>" 
            data-position="head" 
            style="display: none;">
        </div>
        <?php
    }
}
add_action('wp_head', 'snn_store_script_head', 9999);

// b) Script in body (top)
function snn_store_script_body_top() {
    $options = get_option( SNN_OPTIONS );
    if ( !empty($options['script_body_top']) ) {
        ?>
        <div 
            id="snn-script-body-top" 
            data-script="<?php echo esc_attr( base64_encode($options['script_body_top']) ); ?>" 
            data-position="body_top" 
            style="display: none;">
        </div>
        <?php
    }
}
if ( function_exists('wp_body_open') ) {
    add_action('wp_body_open', 'snn_store_script_body_top');
} else {
    add_action('wp_head', 'snn_store_script_body_top', 0);
}

// c) Script in body (bottom)
function snn_store_script_body_bottom() {
    $options = get_option( SNN_OPTIONS );
    if ( !empty($options['script_body_bottom']) ) {
        ?>
        <div 
            id="snn-script-body-bottom" 
            data-script="<?php echo esc_attr( base64_encode($options['script_body_bottom']) ); ?>" 
            data-position="body_bottom" 
            style="display: none;">
        </div>
        <?php
    }
}
add_action('wp_footer', 'snn_store_script_body_bottom', 99);

/**
 * 3) Add JavaScript to:
 *    - Set/Check cookies
 *    - Dynamically inject scripts from the hidden divs (if consent is given)
 *    - Update Google Consent Mode v2 using gtag if enabled
 */
function snn_output_banner_js() {
    $options = get_option(SNN_OPTIONS);
    ?>
    <script>
    (function(){
        // Google Consent Mode integration flag from settings
        var enableConsentMode = <?php echo (($options['enable_consent_mode'] ?? 'no') === 'yes' ? 'true' : 'false'); ?>;
        
        function updateGoogleConsent(consentValue) {
            if(enableConsentMode && typeof gtag === 'function'){
                gtag('consent', 'update', {
                    'ad_storage': consentValue,
                    'analytics_storage': consentValue,
                    'ad_user_data': consentValue,
                    'ad_personalization': consentValue
                });
            }
        }
        
        function setCookie(name, value, days) {
            var expires = "";
            if (days) {
                var date = new Date();
                date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                expires = "; expires=" + date.toUTCString();
            }
            document.cookie = name + "=" + (value || "") + expires + "; path=/";
        }

        function getCookie(name) {
            var nameEQ = name + "=";
            var ca = document.cookie.split(';');
            for (var i = 0; i < ca.length; i++) {
                var c = ca[i].trim();
                if (c.indexOf(nameEQ) === 0) {
                    return c.substring(nameEQ.length, c.length);
                }
            }
            return null;
        }

        // Dynamically inject script into the specified position
        function injectScript(decodedCode, position) {
            var tempDiv = document.createElement('div');
            tempDiv.innerHTML = decodedCode;

            var scripts = tempDiv.querySelectorAll('script');
            scripts.forEach(function(s){
                var newScript = document.createElement('script');
                for (var i = 0; i < s.attributes.length; i++) {
                    var attr = s.attributes[i];
                    newScript.setAttribute(attr.name, attr.value);
                }
                newScript.text = s.text || '';
                if (position === 'head') {
                    document.head.appendChild(newScript);
                } else if (position === 'body_top') {
                    var body = document.body;
                    if (body.firstChild) {
                        body.insertBefore(newScript, body.firstChild);
                    } else {
                        body.appendChild(newScript);
                    }
                } else {
                    document.body.appendChild(newScript);
                }
            });
        }

        function injectAllConsentScripts() {
            var hiddenDivs = document.querySelectorAll('[id^="snn-script-"][data-script]');
            hiddenDivs.forEach(function(div){
                var encoded = div.getAttribute('data-script');
                var position = div.getAttribute('data-position') || 'body_bottom';
                if (encoded) {
                    var decoded = atob(encoded);
                    injectScript(decoded, position);
                }
            });
        }

        // Event handlers for the banner buttons
        var acceptBtn = document.querySelector('.snn-accept');
        var denyBtn = document.querySelector('.snn-deny');
        var prefsBtn = document.querySelector('.snn-preferences');

        if (acceptBtn) {
            acceptBtn.addEventListener('click', function(){
                setCookie('snn_cookie_accepted', 'true', 365);
                document.getElementById('snn-cookie-banner').style.display = 'none';
                updateGoogleConsent('granted');
                injectAllConsentScripts();
            });
        }
        if (denyBtn) {
            denyBtn.addEventListener('click', function(){
                setCookie('snn_cookie_accepted', 'false', 365);
                document.getElementById('snn-cookie-banner').style.display = 'none';
                updateGoogleConsent('denied');
            });
        }
        if (prefsBtn) {
            prefsBtn.addEventListener('click', function(){
                var prefsContent = document.querySelector('.snn-preferences-content');
                if (prefsContent.style.display === 'none' || prefsContent.style.display === '') {
                    prefsContent.style.display = 'block';
                } else {
                    prefsContent.style.display = 'none';
                }
            });
        }

        // If already accepted on a previous visit, inject the scripts and update consent mode
        if (getCookie('snn_cookie_accepted') === 'true') {
            updateGoogleConsent('granted');
            injectAllConsentScripts();
        }
    })();
    </script>
    <?php
}
add_action('wp_footer', 'snn_output_banner_js', 100);

/**
 * 4) Output custom CSS for the cookie banner
 */
function snn_output_custom_css() {
    $options = get_option( SNN_OPTIONS );
    if ( !empty($options['custom_css']) ) {
        echo "<style id='snn-custom-css'>" . $options['custom_css'] . "</style>";
    }
}
add_action('wp_head', 'snn_output_custom_css');
?>
