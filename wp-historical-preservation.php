<?php

/**
 * Plugin Name: Historical Preservation Mode
 * Plugin URI: 
 * Description: Locks down a WordPress site for historical preservation, preventing any content modifications.
 * Version: 1.0.3
 * Author: 
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
  exit;
}

// Simple settings page
add_action('admin_menu', function () {
  add_options_page(
    'Historical Preservation',
    'Historical Preservation',
    'manage_options',
    'historical-preservation',
    function () {
      if (!current_user_can('manage_options')) {
        return;
      }

      if (isset($_POST['historical_preservation_submit']) && check_admin_referer('historical_preservation_nonce')) {
        update_option('historical_preservation_enabled', isset($_POST['historical_preservation_enabled']));
        echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
      }

      $is_enabled = get_option('historical_preservation_enabled', false);
?>
    <div class="wrap">
      <h1>Historical Preservation Mode</h1>
      <form method="post">
        <?php wp_nonce_field('historical_preservation_nonce'); ?>
        <table class="form-table">
          <tr>
            <th scope="row">Enable Preservation Mode</th>
            <td>
              <label>
                <input type="checkbox" name="historical_preservation_enabled"
                  <?php checked($is_enabled); ?>>
                Lock website in current state
              </label>
            </td>
          </tr>
        </table>
        <?php submit_button('Save Changes', 'primary', 'historical_preservation_submit'); ?>
      </form>
    </div>
<?php
    }
  );
});

// Only add protection if enabled
if (get_option('historical_preservation_enabled', false)) {
  // Block access to admin pages except settings
  add_action('admin_init', function () {
    if (!is_admin()) {
      return;
    }

    // Allow access to the plugin's settings page
    if (isset($_GET['page']) && $_GET['page'] === 'historical-preservation') {
      return;
    }

    // Allow access to admin home and profile
    global $pagenow;
    $allowed_pages = array('index.php', 'profile.php', 'admin.php');
    if (in_array($pagenow, $allowed_pages)) {
      return;
    }

    // Show notice on allowed pages
    add_action('admin_notices', function () {
      echo '<div class="notice notice-warning">
                <p><strong>Historical Preservation Mode is active.</strong> Content modifications are disabled. 
                <a href="' . admin_url('options-general.php?page=historical-preservation') . '">Manage Settings</a></p>
            </div>';
    });

    // Block access to all other admin pages
    if (!isset($_GET['page']) || $_GET['page'] !== 'historical-preservation') {
      wp_die(
        'Access Denied - Site is in historical preservation mode.',
        'Access Denied',
        array('response' => 403, 'back_link' => true)
      );
    }
  }, 1);

  // Disable REST API modifications
  add_filter('rest_authentication_errors', function ($errors) {
    return new WP_Error(
      'rest_forbidden',
      'Site is in historical preservation mode.',
      array('status' => 403)
    );
  });
}

// Activation hook
register_activation_hook(__FILE__, function () {
  add_option('historical_preservation_enabled', false);
});
