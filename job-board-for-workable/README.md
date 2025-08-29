# Job Board for Workable WordPress Plugin

A powerful WordPress plugin that integrates with the Workable API to display job listings on your website with advanced filtering and customizable styling.

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![WordPress](https://img.shields.io/badge/wordpress-5.0+-green.svg)
![PHP](https://img.shields.io/badge/php-7.4+-purple.svg)
![License](https://img.shields.io/badge/license-GPL2+-red.svg)

## 🚀 Features

- **Seamless Workable Integration** - Connect directly to your Workable account
- **Responsive Design** - Mobile-friendly interface that works on all devices
- **Advanced Filtering** - Search by title, filter by department and location
- **Custom Color Controls** - Match your brand with built-in color pickers
- **Dynamic Application Forms** - Auto-generated forms with file upload support
- **Performance Optimized** - Built-in caching system for fast loading
- **Accessibility Ready** - ARIA labels and keyboard navigation
- **SEO Friendly** - Clean markup and URLs

## 📋 Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- Active Workable account with API access
- cURL support (standard on most hosting)

## 🔧 Installation

### Via WordPress Admin

1. Download the plugin zip file
2. Go to WordPress Admin → Plugins → Add New → Upload Plugin
3. Choose the zip file and click "Install Now"
4. Activate the plugin

### Manual Installation

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate through the 'Plugins' menu in WordPress
3. Configure settings at Settings → Job Board for Workable

## ⚙️ Configuration

### 1. Get Workable API Credentials

1. Log in to your Workable account
2. Navigate to Settings → Integrations → API
3. Generate a new API token
4. Note your subdomain (e.g., `company` from `company.workable.com`)

### 2. Plugin Setup

1. Go to **Settings → Job Board for Workable**
2. Enter your API token and subdomain
3. Customize colors to match your brand
4. Save settings

### 3. Add to Your Site

Use these shortcodes in your posts/pages:

```
[job_board_for_workable_filters]
[job_board_for_workable]
```

## 🎨 Customization Options

The plugin includes color customization for:

- **Filter Controls** - Border colors for search and filter inputs
- **Department Titles** - Color for section headings
- **Job Titles** - Color for job title links
- **Job Locations** - Color for location text
- **Item Borders** - Bottom border colors for job listings

## 📖 Shortcodes

### `[job_board_for_workable_filters]`
Displays search and filtering controls including:
- Job title search
- Department filter (auto-populated)
- Location filter (countries, cities, remote)

### `[job_board_for_workable]`
Displays the main job board with:
- Jobs grouped by department
- Click-to-expand job details
- Integrated application forms
- Responsive design

## 🔧 Technical Details

### File Structure
```
workable-job-board/
├── workable-job-board.php     # Main plugin file
├── includes/
│   ├── api.php                # Workable API integration
│   ├── ajax-apply.php         # Application form handlers
│   ├── enqueue.php            # Asset management
│   ├── settings-page.php      # Admin interface
│   └── shortcodes.php         # Frontend shortcodes
├── assets/
│   ├── css/
│   │   └── workable-job-board.css
│   └── js/
│       └── workable-job-board.js
├── CLAUDE.md                  # Technical documentation
├── readme.txt                 # WordPress.org readme
└── README.md                  # This file
```

### API Integration

The plugin uses the Workable API v3 endpoints:
- `/jobs` - Fetch job listings with pagination
- `/jobs/{shortcode}` - Get individual job details
- `/jobs/{shortcode}/application_form` - Get application form structure
- `/jobs/{shortcode}/candidates` - Submit applications

### Caching System

- Job listings cached for 5 minutes using WordPress transients
- Cache automatically invalidated on settings updates
- Manual cache refresh available via `?wjbfw_refresh=1` parameter

### Security Features

- WordPress nonce verification for all AJAX requests
- Input sanitization and validation
- File upload type and size validation
- Secure API token storage

## 🎯 Usage Examples

### Basic Implementation
```html
<!-- On your careers page -->
<h1>Join Our Team</h1>
[job_board_for_workable_filters]
[job_board_for_workable]
```

### With Custom Styling
```css
/* Override plugin styles */
.wjbfw-dept-title {
    font-family: 'Your Brand Font', sans-serif;
}

.wjbfw-job-item {
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
```

## 🐛 Troubleshooting

### Common Issues

**Jobs not displaying:**
- Verify API token and subdomain are correct
- Check that jobs are published in Workable
- Ensure WordPress can make external HTTP requests

**Styling issues:**
- Clear any caching plugins
- Check for theme CSS conflicts
- Verify color settings are saved properly

**Application submissions failing:**
- Confirm API token has proper permissions
- Check file upload limits on your server
- Verify network connectivity to Workable

### Debug Mode

Enable WordPress debug mode to see detailed error messages:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## 🔌 Hooks & Filters

### Available Hooks

```php
// Modify job data before display
apply_filters('wjbfw_job_data', $job_data);

// Customize application form fields
apply_filters('wjbfw_application_fields', $fields);

// Modify API request arguments
apply_filters('wjbfw_api_args', $args);
```

### Custom Development

For custom modifications, see `CLAUDE.md` for detailed technical documentation.

## 📝 Changelog

### Version 1.0.4
- ✨ Added color customization options
- 📚 Enhanced settings page with shortcode documentation
- 🔗 Added settings link to plugin page
- ♿ Improved accessibility features
- 🎨 Added default theme color detection

### Version 1.0.3
- 🛠️ Improved error handling
- 📁 Enhanced file upload validation
- 📱 Better mobile responsiveness
- ⚡ Performance optimizations

[View full changelog in readme.txt]

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the GPL2+ License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

- **Documentation**: See `CLAUDE.md` for technical details
- **Issues**: Report bugs via GitHub Issues
- **WordPress.org**: Plugin support forum
- **Email**: Contact plugin author for commercial support

## 🙏 Acknowledgments

- Built for integration with [Workable](https://workable.com)
- Uses WordPress coding standards
- Inspired by modern job board UX patterns

---

**Made with ❤️ for the WordPress community**