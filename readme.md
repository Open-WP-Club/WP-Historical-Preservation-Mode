# WordPress Historical Preservation Mode

A lightweight WordPress plugin that helps preserve websites by disabling content modification capabilities while maintaining full read access to all areas of the WordPress admin.

## Description

The WordPress Historical Preservation Mode plugin is designed for situations where you need to maintain a website in a read-only state for historical or archival purposes. Instead of completely blocking access to admin areas, this plugin takes a more user-friendly approach by disabling save functionality while preserving the ability to view all content.

## Features

- Disables all save/update functionality across WordPress admin
- Maintains full read access to all admin areas
- Makes form fields read-only
- Disables TinyMCE editors
- Prevents form submissions
- Simple on/off toggle in WordPress Settings
- Minimal performance impact
- No complex configuration needed

## Installation

1. Download the plugin from [GitHub repository](https://github.com/Open-WP-Club/WP-Historical-Preservation-Mode)
2. Upload the plugin files to the `/wp-content/plugins/wp-historical-preservation-mode` directory
3. Activate the plugin through the 'Plugins' screen in WordPress
4. Navigate to Settings > Historical Preservation to enable/disable the preservation mode

## Usage

1. Go to WordPress Admin > Settings > Historical Preservation
2. Check the box to enable preservation mode
3. Click "Save Changes"
4. Once enabled, all save/update functionality will be disabled across the admin area
5. To disable preservation mode, simply uncheck the box and save changes

## Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher

## Frequently Asked Questions

### Can I still view all admin pages when preservation mode is enabled?

Yes, you maintain full read access to all areas of WordPress admin. Only the ability to save or modify content is disabled.

### How do I disable preservation mode?

Simply go to Settings > Historical Preservation and uncheck the enable box.

### Will this affect the front end of my website?

No, the plugin only affects administrative functionality. Your website will continue to work normally for visitors.

### Can I selectively enable editing for certain users?

Currently, the preservation mode applies globally when enabled. Future versions may include role-based exceptions.

## Changelog

### 1.0.0

- Initial release
- Basic preservation functionality
- Admin settings page
- Form submission prevention
- Button and input field disabling

## License

GPL v2 or later

## Author

Gabriel Kanev  
[OpenWPClub.com](https://openwpclub.com)

## Contributing

We welcome contributions! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## Support

For support, please open an issue in the [GitHub repository](https://github.com/Open-WP-Club/WP-Historical-Preservation-Mode/issues).

## Security

If you discover any security-related issues, please email the author instead of using the issue tracker.
