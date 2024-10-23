<?php

/**
 * Plugin Name: Historical Preservation Mode
 * Plugin URI: 
 * Description: Locks down a WordPress site for historical preservation, preventing any content modifications.
 * Version: 1.0.2
 * Author: 
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
  exit;
}

class Historical_Preservation_Mode
{

  public function __construct()
  {
    add_action('admin_init', array($this, 'init_plugin'));
    add_action('admin_menu', array($this, 'add_menu_page'));
  }

  public function init_plugin()
  {
    if (!get_option('historical_preservation_enabled', false)) {
      return;
    }

    // Core protection hooks
    add_filter('user_has_cap', array($this, 'modify_user_caps'), 10, 3);
    add_action('admin_notices', array($this, 'display_notice'));

    // Block direct access to edit screens
    add_action('current_screen', array($this, 'block_edit_screens'));
  }

  public function add_menu_page()
  {
    add_options_page(
      'Historical Preservation',
      'Historical Preservation',
      'manage_options',
      'historical-preservation',
      array($this, 'render_settings_page')
    );
  }

  public function render_settings_page()
  {
    if (!current_user_can('manage_options')) {
      return;
    }

    $is_enabled = get_option('historical_preservation_enabled', false);

    if (isset($_POST['historical_preservation_submit'])) {
      if (check_admin_referer('historical_preservation_nonce')) {
        $is_enabled = isset($_POST['historical_preservation_enabled']);
        update_option('historical_preservation_enabled', $is_enabled);
        echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
      }
    }

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
              <p class="description">
                Warning: When enabled, this will prevent all content modifications.
              </p>
            </td>
          </tr>
        </table>
        <?php submit_button('Save Changes', 'primary', 'historical_preservation_submit'); ?>
      </form>
    </div>
<?php
  }

  public function modify_user_caps($allcaps, $caps, $args)
  {
    // Skip for super admin override
    if ($this->is_super_admin_override()) {
      return $allcaps;
    }

    // List of capabilities to remove
    $restricted_caps = array(
      'edit_posts',
      'edit_pages',
      'edit_published_posts',
      'edit_published_pages',
      'edit_private_posts',
      'edit_private_pages',
      'edit_others_posts',
      'edit_others_pages',
      'publish_posts',
      'publish_pages',
      'delete_posts',
      'delete_pages',
      'delete_private_posts',
      'delete_private_pages',
      'delete_published_posts',
      'delete_published_pages',
      'delete_others_posts',
      'delete_others_pages',
      'manage_categories',
      'manage_links',
      'upload_files',
      'install_plugins',
      'update_plugins',
      'delete_plugins',
      'install_themes',
      'update_themes',
      'delete_themes',
      'edit_theme_options',
      'customize'
    );

    foreach ($restricted_caps as $cap) {
      if (isset($allcaps[$cap])) {
        $allcaps[$cap] = false;
      }
    }

    return $allcaps;
  }

  public function block_edit_screens()
  {
    if ($this->is_super_admin_override()) {
      return;
    }

    $screen = get_current_screen();
    if (!$screen) {
      return;
    }

    $blocked_screens = array(
      'post',
      'edit',
      'page',
      'upload',
      'media',
      'theme-editor',
      'plugin-editor',
      'theme-install',
      'plugin-install',
      'widgets'
    );

    if (in_array($screen->base, $blocked_screens)) {
      wp_die(
        'Access Denied - Site is in historical preservation mode.',
        'Access Denied',
        array('response' => 403, 'back_link' => true)
      );
    }
  }

  public function display_notice()
  {
    echo '<div class="notice notice-warning">
            <p><strong>Historical Preservation Mode is active.</strong> Content modifications are disabled.</p>
        </div>';
  }

  private function is_super_admin_override()
  {
    return (
      current_user_can('manage_options') &&
      isset($_GET['preservation_override']) &&
      wp_verify_nonce($_GET['preservation_override'], 'preservation_override')
    );
  }
}

// Initialize plugin
function initialize_historical_preservation()
{
  new Historical_Preservation_Mode();
}
add_action('plugins_loaded', 'initialize_historical_preservation');

// Activation hook
function activate_historical_preservation()
{
  add_option('historical_preservation_enabled', false);
}
register_activation_hook(__FILE__, 'activate_historical_preservation');
