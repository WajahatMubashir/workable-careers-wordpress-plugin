=== Job Board for Workable ===
Contributors: wajahatmubashir
Tags: jobs, career, workable, job board, recruitment
Requires at least: 5.8
Tested up to: 6.7
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Display and filter Workable job listings via shortcodes. Simple setup. GDPR-friendly.

== Description ==

Display job listings from Workable on your WordPress site with advanced filtering and customizable styling.

This plugin is not affiliated with or endorsed by Workable.

**Key Features:**

* **Easy Integration** - Connect directly to your Workable account using API credentials
* **Responsive Design** - Mobile-friendly job board that looks great on all devices
* **Advanced Filtering** - Search by job title, filter by department and location
* **Custom Colors** - Customize colors to match your brand using built-in color pickers
* **Application Forms** - Dynamic job application forms with file upload support
* **SEO Friendly** - Clean URLs and proper markup for search engine optimization
* **Accessibility** - ARIA labels and keyboard navigation support
* **Caching** - Built-in caching for optimal performance

**Two Simple Shortcodes:**

* `[wjbfw_job_filters]` - Display search and filter controls
* `[wjbfw_job_board]` - Display the job listings grouped by department

For backward compatibility, the old shortcode names are also supported:
* `[job_board_for_workable_filters]` - Display search and filter controls (deprecated)
* `[job_board_for_workable]` - Display the job listings grouped by department (deprecated)

**Perfect for:**

* Companies using Workable for recruitment
* HR departments wanting to showcase open positions
* Career pages on corporate websites
* Recruitment agencies
* Job portal websites

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/job-board-for-workable/` directory, or install through WordPress admin
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to Settings → Job Board for Workable to configure the plugin
4. Enter your Workable API token and subdomain
5. Customize colors if desired
6. Add shortcodes to your pages where you want to display jobs

**Getting Your Workable API Credentials:**

1. Log in to your Workable account
2. Go to Settings → Integrations → API
3. Generate a new API token
4. Copy your account subdomain (e.g., if your URL is company.workable.com, use "company")

== Frequently Asked Questions ==

= Do I need a Workable account to use this plugin? =

Yes, you need an active Workable account and API access to fetch job listings.

= How do I get my API token? =

Log in to Workable, go to Settings → Integrations → API, and generate a new token.

= Can I customize the appearance? =

Yes! The plugin includes color customization options in the settings page. You can change colors for filters, department titles, job titles, locations, and borders.

= Are the job applications sent to Workable? =

Yes, all applications are submitted directly to your Workable account and will appear in your candidate pipeline.

= Does it work with my theme? =

The plugin is designed to work with any properly coded WordPress theme and includes responsive styling.

= Can I filter jobs by location? =

Yes, the plugin supports filtering by country, city, and remote positions.

= Is there a cache system? =

Yes, job listings are cached for 5 minutes to improve performance. You can force refresh by adding ?wjbfw_refresh=1 to the page URL.

== Screenshots ==

1. Job board display with department grouping
2. Advanced filtering controls
3. Job detail view with application form
4. Admin settings page with color customization
5. Mobile responsive design

== Changelog ==

= 1.0.0 =
* Initial release
* Workable API integration for job listings
* Job board display with department grouping
* Advanced filtering (search, department, location)
* Dynamic job application forms with file upload
* Color customization options
* Responsive design with accessibility features
* Caching system for optimal performance

== Upgrade Notice ==

= 1.0.0 =
Initial release with complete job board functionality and color customization options.

== Support ==

For support, feature requests, or bug reports, please contact the plugin author or visit the plugin's support forum.

== Privacy & Data ==

**Third-Party Service Integration:**
This plugin integrates with Workable, a third-party recruitment platform. Here's what data is exchanged:

**Data Fetched from Workable:**
- Job listings (read-only) are retrieved from Workable's REST API to display on your website
- No personal or sensitive information is collected during job listing retrieval

**Data Sent to Workable:**
- When candidates submit job applications through the plugin's forms, their information (including resumes and cover letters) is sent directly to Workable via their API
- Application data includes: name, email, phone, resume files, cover letters, and any custom fields

**Privacy & Tracking:**
- This plugin does NOT set tracking cookies
- This plugin does NOT track users
- This plugin does NOT send data to any service other than Workable
- No candidate data is stored locally on your WordPress site beyond temporary transient caches

**Important Links:**
- [Workable Privacy Policy](https://workable.com/privacy)
- [Workable Terms of Service](https://workable.com/terms)

For complete information about how your candidate data is handled, please review Workable's privacy policy and terms of service.

== Technical Requirements ==

* WordPress 5.0 or higher
* PHP 7.4 or higher
* Active Workable account with API access
* cURL support (standard on most hosting providers)

== Advanced Usage ==

**Custom Styling:**
While the plugin includes color customization options, developers can override styles using custom CSS. All elements use semantic class names with the "wjbfw-" prefix.

**Cache Management:**
- Job listings are cached for 5 minutes
- Force cache refresh by adding ?wjbfw_refresh=1 to the page URL
- Cache is automatically cleared when settings are updated

**Hooks and Filters:**
The plugin provides various WordPress hooks for developers to extend functionality. Contact support for developer documentation.
