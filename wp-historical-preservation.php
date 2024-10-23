<?php

/**
 * Plugin Name: Historical Preservation Mode
 * Plugin URI: 
 * Description: Locks down a WordPress site for historical preservation, preventing any content modifications.
 * Version: 1.0.1
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
  private $is_preservation_enabled = false;

  public static function get_instance()
  {
    if (null === self::$instance) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  private function __construct()
  {
    // Load option only once
    $this->is_preservation_enabled = get_option('historical_preservation_enabled', false);

    // Core admin functionality
    add_action('admin_menu', array($this, 'add_admin_menu'));
    add_action('admin_init', array($this, 'init_preservation_mode'));

    // Register activation hook
    register_activation_hook(__FILE__, array($this, 'activate_plugin'));
  }

  public function activate_plugin()
  {
    add_option('historical_preservation_enabled', false);
  }

  public function init_preservation_mode()
  {
    if (!$this->is_preservation_enabled) {
      return;
    }

    // Add only necessary hooks based on current context
    if (is_admin()) {
      add_action('admin_notices', array($this, 'show_preservation_notice'));

      // Prevent post/page modifications only when editing
      if ($this->is_edit_action()) {
        add_filter('wp_insert_post_data', array($this, 'prevent_content_changes'), 999);
        add_action('pre_delete_post', array($this, 'prevent_content_changes'), 10);
      }

      // Prevent uploads only when in media section
      if ($this->is_media_action()) {
        add_filter('upload_mimes', array($this, 'prevent_changes'));
        add_filter('wp_handle_upload', array($this, 'prevent_changes'));
      }

      // Prevent plugin/theme changes only when in respective sections
      if ($this->is_plugin_theme_action()) {
        add_filter('map_meta_cap', array($this, 'prevent_plugin_theme_changes'), 10, 2);
      }
    }
  }

  private function is_edit_action()
  {
    global $pagenow;
    return in_array($pagenow, array('post.php', 'post-new.php', 'edit.php'));
  }

  private function is_media_action()
  {
    global $pagenow;
    return in_array($pagenow, array('upload.php', 'media-new.php'));
  }

  private function is_plugin_theme_action()
  {
    global $pagenow;
    return in_array($pagenow, array('plugins.php', 'themes.php', 'theme-install.php', 'plugin-install.php'));
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
    if (isset($_POST['historical_preservation_submit']) && check_admin_referer('historical_preservation_nonce')) {
      $this->is_preservation_enabled = isset($_POST['historical_preservation_enabled']);
      update_option('historical_preservation_enabled', $this->is_preservation_enabled);
      echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
    }

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
                  <?php checked($this->is_preservation_enabled); ?>>
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

  public function prevent_content_changes()
  {
    if (!$this->is_super_admin_override()) {
      wp_die('Site is in historical preservation mode. Content modifications are disabled.');
    }
  }

  public function prevent_changes($input)
  {
    if (!$this->is_super_admin_override()) {
      wp_die('Site is in historical preservation mode. Modifications are disabled.');
    }
    return $input;
  }

  public function prevent_plugin_theme_changes($caps, $cap)
  {
    if (!$this->is_super_admin_override()) {
      $restricted_caps = array(
        'install_themes',
        'update_themes',
        'delete_themes',
        'install_plugins',
        'update_plugins',
        'delete_plugins'
      );

      if (in_array($cap, $restricted_caps)) {
        return array('do_not_allow');
      }
    }
    return $caps;
  }

  public function show_preservation_notice()
  {
    echo '<div class="notice notice-warning">
            <p><strong>Historical Preservation Mode is active.</strong> Content modifications are disabled.</p>
        </div>';
  }

  private function is_super_admin_override()
  {
    return current_user_can('manage_options') &&
      isset($_GET['preservation_override']) &&
      $_GET['preservation_override'] === wp_create_nonce('preservation_override');
  }
}

// Initialize the plugin
add_action('plugins_loaded', array('Historical_Preservation_Mode', 'get_instance'));
