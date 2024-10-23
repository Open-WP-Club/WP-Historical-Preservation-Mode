<?php

/**
 * Plugin Name: Historical Preservation Mode
 * Plugin URI: 
 * Description: Disables saving functionality across WordPress admin for historical preservation.
 * Version: 1.0.0
 * Author: 
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
  exit;
}

// Settings page
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
                Disable saving functionality across the site
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
  // Add notice
  add_action('admin_notices', function () {
    echo '<div class="notice notice-warning">
            <p><strong>Historical Preservation Mode is active.</strong> Saving functionality is disabled. 
            <a href="' . admin_url('options-general.php?page=historical-preservation') . '">Manage Settings</a></p>
        </div>';
  });

  // Add JS to disable buttons and forms
  add_action('admin_footer', function () {
    if (isset($_GET['page']) && $_GET['page'] === 'historical-preservation') {
      return;
    }
  ?>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        // Disable all submit buttons except in our settings
        const buttons = document.querySelectorAll('input[type="submit"], button[type="submit"], .button-primary, .button');
        buttons.forEach(function(button) {
          if (!button.closest('.wp-core-ui')) {
            button.disabled = true;
            button.classList.add('disabled');
          }
        });

        // Disable form submissions
        const forms = document.querySelectorAll('form');
        forms.forEach(function(form) {
          if (!form.closest('.wp-core-ui')) {
            form.onsubmit = function(e) {
              e.preventDefault();
              alert('Site is in historical preservation mode. Saving is disabled.');
              return false;
            };
          }
        });

        // Disable quick edit links
        const quickEditLinks = document.querySelectorAll('.editinline');
        quickEditLinks.forEach(function(link) {
          link.style.display = 'none';
        });

        // Disable TinyMCE if it exists
        if (typeof tinyMCE !== 'undefined') {
          tinyMCE.editors.forEach(function(editor) {
            editor.setMode('readonly');
          });
        }

        // Add disabled attribute to common inputs
        const inputs = document.querySelectorAll('input[type="text"], textarea, select');
        inputs.forEach(function(input) {
          if (!input.closest('.wp-core-ui')) {
            input.readOnly = true;
          }
        });

        // Style disabled elements
        const style = document.createElement('style');
        style.textContent = `
                button:disabled,
                input:disabled,
                .button.disabled {
                    opacity: 0.6 !important;
                    cursor: not-allowed !important;
                }
                .readonly {
                    background-color: #f0f0f1 !important;
                }
            `;
        document.head.appendChild(style);
      });
    </script>
<?php
  });

  // Prevent form submissions server-side as backup
  add_action('admin_init', function () {
    if (isset($_GET['page']) && $_GET['page'] === 'historical-preservation') {
      return;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      wp_die(
        'Saving is disabled - Site is in historical preservation mode.',
        'Action Blocked',
        array('response' => 403, 'back_link' => true)
      );
    }
  });
}

// Activation hook
register_activation_hook(__FILE__, function () {
  add_option('historical_preservation_enabled', false);
});
