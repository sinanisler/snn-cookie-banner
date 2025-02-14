<?php
/*
Plugin Name: SNN Cookie Banner Plugin (Dynamic Block)
Description: A plugin to manage cookie consent and dynamically block scripts (e.g. Google Analytics) until user accepts cookies.
Version: 2.0
Author: Your Name
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

// Render the settings page
function snn_options_page() {
    // Handle form submission
    if ( isset($_POST['snn_options_nonce']) && wp_verify_nonce( $_POST['snn_options_nonce'], 'snn_save_options' ) ) {
        $options = array();
        $options['banner_description'] = sanitize_text_field( $_POST['banner_description'] );
        $options['accept_button']      = sanitize_text_field( $_POST['accept_button'] );
        $options['deny_button']        = sanitize_text_field( $_POST['deny_button'] );
        $options['preferences_button'] = sanitize_text_field( $_POST['preferences_button'] );
        
        // New settings for banner position and colors
        $options['banner_position']    = sanitize_text_field( $_POST['banner_position'] );
        $options['banner_bg_color']    = sanitize_text_field( $_POST['banner_bg_color'] );
        $options['banner_text_color']  = sanitize_text_field( $_POST['banner_text_color'] );
        $options['button_bg_color']    = sanitize_text_field( $_POST['button_bg_color'] );
        $options['button_text_color']  = sanitize_text_field( $_POST['button_text_color'] );
        
        // Process repeater services field
        $services = array();
        if ( isset($_POST['services']) && is_array($_POST['services']) ) {
            foreach( $_POST['services'] as $service ) {
                $services[] = sanitize_text_field( $service );
            }
        }
        $options['services'] = $services;
        
        // Scripts (no sanitization to preserve script tags)
        $options['script_head']        = $_POST['script_head'];
        $options['script_body_top']    = $_POST['script_body_top'];
        $options['script_body_bottom'] = $_POST['script_body_bottom'];
        
        // Custom CSS (allow full CSS, no sanitization)
        $options['custom_css']         = $_POST['custom_css'];
        
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
            'button_text_color'    => '#ffffff'
        );
    }
    ?>
    <div class="wrap">
        <h1>SNN Cookie Banner Plugin Settings</h1>
        <form method="post">
            <?php wp_nonce_field( 'snn_save_options', 'snn_options_nonce' ); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Cookie Banner Description</th>
                    <td>
                        <textarea name="banner_description" rows="3" style="width: 500px;"><?php echo esc_textarea( $options['banner_description'] ); ?></textarea>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Accept Button Text</th>
                    <td>
                        <input type="text" name="accept_button" value="<?php echo esc_attr( $options['accept_button'] ); ?>" style="width: 300px;">
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Deny Button Text</th>
                    <td>
                        <input type="text" name="deny_button" value="<?php echo esc_attr( $options['deny_button'] ); ?>" style="width: 300px;">
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Preferences Button Text</th>
                    <td>
                        <input type="text" name="preferences_button" value="<?php echo esc_attr( $options['preferences_button'] ); ?>" style="width: 300px;">
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Cookie Banner Position</th>
                    <td>
                        <select name="banner_position">
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
                        <input type="color" name="banner_bg_color" value="<?php echo esc_attr($options['banner_bg_color']); ?>">
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Cookie Banner Text Color</th>
                    <td>
                        <input type="color" name="banner_text_color" value="<?php echo esc_attr($options['banner_text_color']); ?>">
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Button Background Color</th>
                    <td>
                        <input type="color" name="button_bg_color" value="<?php echo esc_attr($options['button_bg_color']); ?>">
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Button Text Color</th>
                    <td>
                        <input type="color" name="button_text_color" value="<?php echo esc_attr($options['button_text_color']); ?>">
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Services (Repeater)</th>
                    <td>
                        <div id="services-repeater">
                            <?php 
                            if ( ! empty($options['services']) && is_array($options['services']) ) {
                                foreach ( $options['services'] as $service ) {
                                    ?>
                                    <div class="service-item" style="margin-bottom:5px;">
                                        <input type="text" name="services[]" value="<?php echo esc_attr( $service ); ?>" style="width: 300px;">
                                        <button class="remove-service button">Remove</button>
                                    </div>
                                    <?php
                                }
                            } else {
                                ?>
                                <div class="service-item" style="margin-bottom:5px;">
                                    <input type="text" name="services[]" value="" style="width: 300px;">
                                    <button class="remove-service button">Remove</button>
                                </div>
                                <?php
                            }
                            ?>
                        </div>
                        <button id="add-service" class="button">Add Service</button>
                        <script>
                        (function($){
                            $(document).ready(function(){
                                $('#add-service').click(function(e){
                                    e.preventDefault();
                                    var newField = '<div class="service-item" style="margin-bottom:5px;"><input type="text" name="services[]" value="" style="width: 300px;"> <button class="remove-service button">Remove</button></div>';
                                    $('#services-repeater').append(newField);
                                });
                                $('#services-repeater').on('click', '.remove-service', function(e){
                                    e.preventDefault();
                                    $(this).parent('.service-item').remove();
                                });
                            });
                        })(jQuery);
                        </script>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Script in Head (last item)</th>
                    <td>
                        <textarea name="script_head" rows="5" style="width: 500px;"><?php echo esc_textarea( $options['script_head'] ); ?></textarea>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Script in Body (top)</th>
                    <td>
                        <textarea name="script_body_top" rows="5" style="width: 500px;"><?php echo esc_textarea( $options['script_body_top'] ); ?></textarea>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Script in Body (bottom)</th>
                    <td>
                        <textarea name="script_body_bottom" rows="5" style="width: 500px;"><?php echo esc_textarea( $options['script_body_bottom'] ); ?></textarea>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Custom CSS for Cookie Banner</th>
                    <td>
                        <textarea name="custom_css" rows="5" style="width: 500px;"><?php echo isset($options['custom_css']) ? esc_textarea($options['custom_css']) : ''; ?></textarea>
                        <p class="description">
                            Use the following CSS selectors to style the banner:<br>
                            <code>#snn-cookie-banner</code> - The cookie banner container<br>
                            <code>#snn-preferences-content</code> - The preferences content container inside the banner
                        </p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

/* ============================================================================
   FRONTEND COOKIE BANNER, CUSTOM CSS & SCRIPT ENCODING
============================================================================ */

/**
 * 1) Show the cookie banner if the user hasn't made a choice yet
 *    (i.e., no "snn_cookie_accepted" cookie is set)
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
    
    // Determine banner position style
    $position = isset($options['banner_position']) ? $options['banner_position'] : 'left';
    $banner_style = "position: fixed; bottom: 0; width: 500px; z-index: 9999; background: {$options['banner_bg_color']}; color: {$options['banner_text_color']}; padding: 15px;";
    if ($position === 'left') {
        $banner_style .= " left: 0;";
    } elseif ($position === 'middle') {
        $banner_style .= " left: 50%; transform: translateX(-50%);";
    } elseif ($position === 'right') {
        $banner_style .= " right: 0;";
    }
    ?>
    <div id="snn-cookie-banner" style="<?php echo esc_attr($banner_style); ?>">
        <div id="snn-preferences-content" style="display:none; margin-top: 15px; border-top: 1px solid #ccc; padding-top: 15px;">
            <h2 style="margin-top: 0;">Cookie Preferences</h2>
            <?php if ( isset($options['services']) && is_array($options['services']) ) { ?>
                <ul>
                <?php foreach ( $options['services'] as $service ) { ?>
                    <li><?php echo esc_html( $service ); ?></li>
                <?php } ?>
                </ul>
            <?php } ?>
            <button id="snn-close-preferences" style="background: <?php echo esc_attr($options['button_bg_color']); ?>; color: <?php echo esc_attr($options['button_text_color']); ?>;">Close Preferences</button>
        </div>
        <p><?php echo esc_html( $options['banner_description'] ); ?></p>
        <div id="snn-banner-buttons">
            <button id="snn-accept" style="background: <?php echo esc_attr($options['button_bg_color']); ?>; color: <?php echo esc_attr($options['button_text_color']); ?>; margin-right: 10px;">
                <?php echo esc_html( $options['accept_button'] ); ?>
            </button>
            <button id="snn-deny" style="background: <?php echo esc_attr($options['button_bg_color']); ?>; color: <?php echo esc_attr($options['button_text_color']); ?>; margin-right: 10px;">
                <?php echo esc_html( $options['deny_button'] ); ?>
            </button>
            <button id="snn-preferences" style="background: <?php echo esc_attr($options['button_bg_color']); ?>; color: <?php echo esc_attr($options['button_text_color']); ?>;">
                <?php echo esc_html( $options['preferences_button'] ); ?>
            </button>
        </div>
    </div>
    <?php
}
add_action('wp_footer', 'snn_output_cookie_banner');

/**
 * 2) Always output the scripts as Base64-encoded data in hidden divs
 *    - This ensures they are never directly loaded/preloaded by the browser
 *    - We only decode & inject them if user has consented
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
    // Fallback if wp_body_open not available
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
 */
function snn_output_banner_js() {
    ?>
    <script>
    (function(){
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

        // Dynamically inject script into <head> or <body> etc.
        function injectScript(decodedCode, position) {
            // Create a temporary container to parse any <script> tags
            var tempDiv = document.createElement('div');
            tempDiv.innerHTML = decodedCode;

            // We look for <script> tags inside the decoded content
            var scripts = tempDiv.querySelectorAll('script');
            scripts.forEach(function(s){
                var newScript = document.createElement('script');
                
                // Copy attributes
                for (var i = 0; i < s.attributes.length; i++) {
                    var attr = s.attributes[i];
                    newScript.setAttribute(attr.name, attr.value);
                }
                
                newScript.text = s.text || '';
                // Decide where to place the script
                if (position === 'head') {
                    document.head.appendChild(newScript);
                } else if (position === 'body_top') {
                    // Insert right after body open
                    var body = document.body;
                    if (body.firstChild) {
                        body.insertBefore(newScript, body.firstChild);
                    } else {
                        body.appendChild(newScript);
                    }
                } else {
                    // For body_bottom or default, insert at the end of body
                    document.body.appendChild(newScript);
                }
            });

            // Also keep any non-script HTML (like noscript or other tags) 
            // if you want them inserted. 
            // For simplicity, we are only injecting the <script> tags, 
            // but you could also insert other markup if needed.
        }

        function injectAllConsentScripts() {
            // Find all divs with data-script
            var hiddenDivs = document.querySelectorAll('[id^="snn-script-"][data-script]');
            hiddenDivs.forEach(function(div){
                var encoded = div.getAttribute('data-script');
                var position = div.getAttribute('data-position') || 'body_bottom';
                if (encoded) {
                    var decoded = atob(encoded); // base64 decode
                    injectScript(decoded, position);
                }
            });
        }

        // Event handlers for the banner
        var acceptBtn = document.getElementById('snn-accept');
        var denyBtn = document.getElementById('snn-deny');
        var prefsBtn = document.getElementById('snn-preferences');
        var closePrefsBtn = document.getElementById('snn-close-preferences');

        if (acceptBtn) {
            acceptBtn.addEventListener('click', function(){
                setCookie('snn_cookie_accepted', 'true', 365);
                document.getElementById('snn-cookie-banner').style.display = 'none';
                // Inject scripts immediately
                injectAllConsentScripts();
            });
        }
        if (denyBtn) {
            denyBtn.addEventListener('click', function(){
                setCookie('snn_cookie_accepted', 'false', 365);
                document.getElementById('snn-cookie-banner').style.display = 'none';
            });
        }
        if (prefsBtn) {
            prefsBtn.addEventListener('click', function(){
                document.getElementById('snn-preferences-content').style.display = 'block';
            });
        }
        if (closePrefsBtn) {
            closePrefsBtn.addEventListener('click', function(){
                document.getElementById('snn-preferences-content').style.display = 'none';
            });
        }

        // If already accepted in a previous visit
        if (getCookie('snn_cookie_accepted') === 'true') {
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
