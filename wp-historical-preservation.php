<?php

/**
 * Plugin Name: Historical Preservation Mode
 * Plugin URI: 
 * Description: Locks down a WordPress site for historical preservation, preventing any content modifications.
 * Version: 1.0.0
 * Author: 
 * License: GPL v2 or later
 */

// Prevent direct access to this file
if (!defined('ABSPATH')) {
  exit;
}

class Historical_Preservation_Mode
{
  private static $instance = null;

  public static function get_instance()
  {
    if (null === self::$instance) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  private function __construct()
  {
    // Add admin menu
    add_action('admin_menu', array($this, 'add_admin_menu'));

    // If preservation mode is enabled, hook all necessary functions
    if (get_option('historical_preservation_enabled', false)) {
      $this->enable_preservation_mode();
    }

    // Add activation hook
    register_activation_hook(__FILE__, array($this, 'activate_plugin'));
  }

  public function activate_plugin()
  {
    // Initialize plugin settings
    add_option('historical_preservation_enabled', false);
  }

  public function add_admin_menu()
  {
    add_options_page(
      'Historical Preservation Mode',
      'Historical Preservation',
      'manage_options',
      'historical-preservation',
      array($this, 'render_admin_page')
    );
  }

  public function render_admin_page()
  {
    // Handle form submission
    if (isset($_POST['historical_preservation_submit'])) {
      if (check_admin_referer('historical_preservation_nonce')) {
        $enabled = isset($_POST['historical_preservation_enabled']) ? true : false;
        update_option('historical_preservation_enabled', $enabled);

        // Reload page to apply changes
        wp_redirect(admin_url('options-general.php?page=historical-preservation'));
        exit;
      }
    }

    $enabled = get_option('historical_preservation_enabled', false);
?>
    <div class="wrap">
      <h1>Historical Preservation Mode</h1>
      <form method="post" action="">
        <?php wp_nonce_field('historical_preservation_nonce'); ?>
        <table class="form-table">
          <tr>
            <th scope="row">Enable Preservation Mode</th>
            <td>
              <label>
                <input type="checkbox" name="historical_preservation_enabled"
                  <?php checked($enabled); ?>>
                Lock website in current state
              </label>
              <p class="description">
                Warning: When enabled, this will prevent all content modifications across the site.
              </p>
            </td>
          </tr>
        </table>
        <?php submit_button('Save Changes', 'primary', 'historical_preservation_submit'); ?>
      </form>
    </div>
<?php
  }

  private function enable_preservation_mode()
  {
    // Prevent post/page modifications
    add_filter('wp_insert_post_data', array($this, 'prevent_post_changes'), 999, 2);
    add_action('pre_delete_post', array($this, 'prevent_post_deletion'), 10, 2);

    // Prevent media uploads and modifications
    add_filter('upload_mimes', array($this, 'prevent_uploads'));
    add_filter('wp_handle_upload', array($this, 'prevent_uploads_handler'));

    // Prevent widget changes
    add_filter('sidebars_widgets', array($this, 'prevent_widget_changes'), 999);

    // Prevent menu changes
    add_filter('wp_update_nav_menu', array($this, 'prevent_menu_changes'), 999);

    // Prevent theme and plugin changes
    add_filter('map_meta_cap', array($this, 'prevent_theme_plugin_changes'), 10, 4);

    // Prevent settings changes
    add_filter('pre_update_option', array($this, 'prevent_settings_changes'), 999, 3);

    // Add admin notice
    add_action('admin_notices', array($this, 'show_preservation_notice'));
  }

  // Prevention methods
  public function prevent_post_changes($data, $postarr)
  {
    if (!$this->is_preservation_exempt()) {
      wp_die('Site is in historical preservation mode. Content modifications are disabled.');
    }
    return $data;
  }

  public function prevent_post_deletion($post_id, $post)
  {
    if (!$this->is_preservation_exempt()) {
      wp_die('Site is in historical preservation mode. Content deletion is disabled.');
    }
  }

  public function prevent_uploads($mimes)
  {
    if (!$this->is_preservation_exempt()) {
      return array();
    }
    return $mimes;
  }

  public function prevent_uploads_handler($file)
  {
    if (!$this->is_preservation_exempt()) {
      wp_die('Site is in historical preservation mode. File uploads are disabled.');
    }
    return $file;
  }

  public function prevent_widget_changes($sidebars_widgets)
  {
    if (!$this->is_preservation_exempt()) {
      return get_option('sidebars_widgets');
    }
    return $sidebars_widgets;
  }

  public function prevent_menu_changes($menu_id)
  {
    if (!$this->is_preservation_exempt()) {
      wp_die('Site is in historical preservation mode. Menu modifications are disabled.');
    }
    return $menu_id;
  }

  public function prevent_theme_plugin_changes($caps, $cap, $user_id, $args)
  {
    if (!$this->is_preservation_exempt()) {
      $restricted_caps = array(
        'install_themes',
        'update_themes',
        'delete_themes',
        'install_plugins',
        'update_plugins',
        'delete_plugins',
        'edit_plugins',
        'edit_themes'
      );

      if (in_array($cap, $restricted_caps)) {
        $caps[] = 'do_not_allow';
      }
    }
    return $caps;
  }

  public function prevent_settings_changes($value, $option, $old_value)
  {
    if (!$this->is_preservation_exempt()) {
      // Allow the preservation mode setting itself to be changed
      if ($option !== 'historical_preservation_enabled') {
        return $old_value;
      }
    }
    return $value;
  }

  public function show_preservation_notice()
  {
    echo '<div class="notice notice-warning">
            <p><strong>Historical Preservation Mode is active.</strong> Content modifications are disabled.</p>
        </div>';
  }

  private function is_preservation_exempt()
  {
    // Allow super admins to bypass restrictions if needed
    return current_user_can('manage_options') &&
      isset($_GET['preservation_override']) &&
      $_GET['preservation_override'] === wp_create_nonce('preservation_override');
  }
}

// Initialize the plugin
Historical_Preservation_Mode::get_instance();
