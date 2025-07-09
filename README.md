=== Workable Job Board ===
Contributors:      wajahat, yourusername
Tags:              jobs, job board, workable, careers, recruitment, hr, listings, ajax
Requires at least: 5.4
Tested up to:      6.5
Requires PHP:      7.2
Stable tag:        1.0.1
License:           GPLv2 or later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Display and manage job listings from your Workable account directly on your WordPress site, including search, filters, AJAX application, and secure admin settings.

== Description ==

**Workable Job Board** displays jobs from your Workable ATS account on any WordPress site with search and filter options. Let candidates apply directly from your site using a secure, mobile-friendly form.

**Features:**
* Fetch and display jobs directly from Workable using their API.
* Filter jobs by department, location, or search by title.
* Candidates can view full job details and submit applications (with resume upload) via AJAX – no page reloads.
* Securely manage your API token and subdomain via WordPress admin settings (no need to edit code).
* Responsive and easy to style.
* Supports all modern browsers and devices.
* Built with best practices for WordPress.org.

**Ideal for:**
* Companies using Workable for recruiting and want a custom-branded job board.
* Agencies or businesses who want to control the application experience on their own website.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/` or install via the WordPress Plugins screen.
2. Activate the plugin.
3. Go to **Settings → Workable Job Board** and enter your Workable API Token and subdomain.
4. Use the `[workable_job_filters]` and `[workable_job_board]` shortcodes on any page to display the job filters and job board.

== Frequently Asked Questions ==

= What do I need from Workable? =

You need an API token and your Workable account subdomain. See [Workable API docs](https://workable.readme.io/reference/job-candidates-create) or your Workable admin area.

= Is the plugin secure? =

Yes, all sensitive info is stored in the WordPress options table and never visible to users. AJAX requests are protected by nonces.

= Can I customize the styles? =

Yes! Override or extend `/assets/css/workable-job-board.css` in your theme or with custom CSS.

= Can I add this to any page? =

Yes, use the shortcodes `[workable_job_filters]` and `[workable_job_board]` wherever you like, in any page or post.

= Will this work with caching plugins? =

AJAX endpoints and application forms are designed to work even if your site uses caching.

== Screenshots ==

1. Main job board with filters and search.
2. Job detail popup and application form.
3. WordPress admin settings screen for Workable integration.

== Changelog ==

= 1.0.0 =
* Initial public release.
* Fetch jobs from Workable API, display, filter, search, and AJAX application support.
* Admin settings for API token and subdomain.
* All JS separated and WordPress.org ready.

== Upgrade Notice ==

= 1.0.0 =
First public release.

== Credits ==

Developed by Wajahat
Powered by the Workable API.

== License ==

This plugin is licensed under the GPLv2 or later.
