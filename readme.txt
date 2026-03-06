=== Matomo Site Kit ===

Contributors: Openmost
Requires at least: 6.0
Tested up to: 6.9.1
Stable tag: 2.2.0
Tags: matomo, connect, analytics, tracking, ecommerce, gdpr, google analytics alternative, web analytics
Requires PHP: 8.2
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

The most complete Matomo Analytics plugin for WordPress. Server-side tracking, WooCommerce ecommerce, site search, GDPR compliance, and Tag Manager support.

== Description ==

**Matomo Site Kit** is the ultimate WordPress plugin for integrating [Matomo Analytics](https://matomo.org/) - the leading open-source, privacy-focused alternative to Google Analytics.

Whether you use Matomo Cloud or self-hosted Matomo On-Premise, this plugin provides everything you need for comprehensive website analytics while respecting your visitors' privacy.

= Why Choose Matomo Site Kit? =

* **Privacy-First Analytics** - Full GDPR, CCPA, and PECR compliance out of the box
* **No Data Sampling** - 100% accurate data, unlike Google Analytics
* **You Own Your Data** - No third-party data sharing
* **Ad-Blocker Resistant** - Server-side tracking bypasses most ad blockers
* **WooCommerce Ready** - Complete ecommerce tracking for your online store

= Three Powerful Tracking Methods =

Choose the tracking method that best fits your needs:

* **Classic JavaScript Tracking** - Traditional Matomo tracking with full feature support using `_paq.push()`. Best for most websites.

* **Matomo Tag Manager (MTM)** - Advanced tag management with dataLayer integration. Perfect for marketing teams who need flexible trigger and variable management.

* **Server-Side PHP Tracking** - Privacy-friendly tracking that works even when JavaScript is disabled or blocked. Ideal for GDPR-focused websites and bypassing ad blockers.

= Complete Feature List =

**Analytics Dashboard**
* Real-time analytics overview in WordPress admin
* Visits, page views, bounce rate, and session duration
* WordPress Dashboard widget for quick stats
* Per-post and per-page analytics metabox

**WooCommerce Ecommerce Tracking**
* Product view tracking with SKU, name, price, and categories
* Category page view tracking
* Add to cart and remove from cart events
* Cart update tracking
* Complete order tracking with revenue, tax, and shipping
* Works with all three tracking methods

**Site Search Tracking**
* Automatic WordPress search tracking
* Search keyword capture
* Category filter detection
* Search result count
* Zero configuration required

**Automatic Annotations**
* Create Matomo annotations when content is published
* Select which post types trigger annotations
* Customizable format with variables: {post_type}, {title}, {url}, {author}
* Live preview in settings

**Privacy & GDPR Compliance**
* Consent mode options (requireConsent, requireCookieConsent)
* Privacy opt-out shortcode [matomo_opt_out]
* Server-side tracking for cookieless analytics
* Exclude tracking by user role
* Compatible with cookie consent plugins

**Advanced Features**
* User ID tracking with SHA256 hashed email
* Heartbeat Timer for accurate time-on-page measurement
* DataLayer integration for Tag Manager
* Bot detection and filtering
* Noscript fallback for JavaScript-disabled browsers
* DNS prefetch and script preloading for performance
* Matomo Cloud and On-Premise support

= Perfect For =

* **Bloggers** who want simple, privacy-respecting analytics
* **WooCommerce stores** needing detailed ecommerce insights
* **Agencies** managing multiple client websites
* **Enterprise** organizations with strict data privacy requirements
* **GDPR-conscious** website owners in the EU
* **Marketing teams** using Matomo Tag Manager

= Matomo Cloud vs On-Premise =

This plugin works seamlessly with both:

* **Matomo Cloud** - Hosted solution at matomo.cloud, no server management needed
* **Matomo On-Premise** - Self-hosted on your own server for complete data ownership

== Installation ==

= Automatic Installation =

1. Go to Plugins > Add New in your WordPress admin
2. Search for "Matomo Site Kit"
3. Click "Install Now" and then "Activate"
4. Go to Site Kit > Settings to configure

= Manual Installation =

1. Download the plugin ZIP file
2. Go to Plugins > Add New > Upload Plugin
3. Upload the ZIP file and click "Install Now"
4. Activate the plugin
5. Go to Site Kit > Settings to configure

= Configuration =

**Required for all tracking methods:**
* Matomo Host URL (e.g., https://analytics.example.com or https://yoursite.matomo.cloud)
* Site ID (found in Matomo under Administration > Websites)

**Additional requirements by tracking method:**

* **Classic JavaScript** - Host URL + Site ID
* **Matomo Tag Manager** - Host URL + Container ID
* **Server-Side PHP** - Host URL + Site ID + Auth Token

**For Dashboard & Annotations:**
* Auth Token (API key from Matomo under Administration > Personal > Security)

== Frequently Asked Questions ==

= Is Matomo Site Kit free? =

Yes, Matomo Site Kit is 100% free and open source. You can use it with either Matomo Cloud (paid hosting) or self-hosted Matomo On-Premise (free).

= Is Matomo GDPR compliant? =

Yes, Matomo is designed for privacy and can be configured for full GDPR compliance. With consent mode enabled, no tracking occurs until the visitor gives consent. Server-side tracking can even work without cookies.

= How is Matomo different from Google Analytics? =

Matomo is a privacy-focused alternative where you own 100% of your data. Unlike Google Analytics, Matomo doesn't sample data, doesn't share data with third parties, and can be self-hosted for complete control.

= Should I remove existing Matomo tracking code? =

Yes, Matomo Site Kit is an all-in-one solution. Remove any existing Matomo code from your theme or other plugins to avoid duplicate tracking.

= Which tracking method should I use? =

* **Classic JavaScript** - Best for most sites. Full feature support, easy setup.
* **Matomo Tag Manager** - Best for marketing teams who need advanced tag management, triggers, and variables.
* **Server-Side PHP** - Best for privacy-focused sites, GDPR compliance, or when ad blockers are a concern.

= Can I use multiple tracking methods together? =

Yes, you can combine Server-Side PHP with either Classic JS or Tag Manager. Server-side handles the initial page view reliably, while client-side handles interactive events like clicks and form submissions.

= Does WooCommerce tracking work with all methods? =

Yes, each tracking method has its own WooCommerce ecommerce toggle. Enable the one that matches your active tracking method for complete ecommerce analytics.

= How does Site Search tracking work? =

When a visitor searches your site, the plugin automatically tracks the search keyword, any category filters applied, and the number of results found. No configuration needed - it works out of the box with WordPress search.

= What is the annotation feature? =

Annotations are notes in Matomo that mark specific dates. This plugin can automatically create annotations when you publish content, helping you correlate traffic changes with content releases.

= What format should I use for annotations? =

The default format is: `New {post_type} published: "{title}"`

Available variables:
* {post_type} - Post type label (e.g., "Post", "Page", "Product")
* {title} - The post title
* {url} - The permalink URL
* {author} - The author's display name

= Will this slow down my website? =

No, the plugin is optimized for performance with DNS prefetch, script preloading, and async loading. Server-side tracking has zero impact on front-end performance.

= Does it work with caching plugins? =

Yes, Matomo Site Kit works with all major caching plugins including WP Rocket, W3 Total Cache, WP Super Cache, and LiteSpeed Cache.

= Does it work with page builders? =

Yes, the plugin works with all page builders including Elementor, Divi, Beaver Builder, WPBakery, and Gutenberg.

= How do I add the opt-out feature for GDPR? =

Add the shortcode `[matomo_opt_out]` to any page or post. This displays a Matomo opt-out form allowing visitors to opt out of tracking.

Shortcode options:
* `[matomo_opt_out language="en"]` - Set language
* `[matomo_opt_out show_intro="0"]` - Hide introduction text
* `[matomo_opt_out background_color="ffffff"]` - Custom background
* `[matomo_opt_out font_color="000000"]` - Custom text color

= Can I exclude certain users from tracking? =

Yes, go to Settings and select which user roles to exclude. Common choices are Administrator and Editor to avoid skewing analytics with your own visits.

= How do I track logged-in users? =

Enable "User ID Tracking" in the settings. The plugin will track logged-in users using a SHA256 hash of their email address, allowing you to see user journeys across devices while maintaining privacy.

= What is Heartbeat Timer? =

Heartbeat Timer sends periodic signals to Matomo while the page is open, providing more accurate "time on page" measurements. Without it, the time spent on the last page of a session cannot be measured.

= Does server-side tracking work with Matomo Cloud? =

Yes, server-side tracking works with both Matomo Cloud and On-Premise installations. You'll need your Auth Token for it to work.

= Why aren't my WooCommerce orders being tracked? =

Make sure you've enabled the ecommerce tracking option for your active tracking method. Each method (Classic, Tag Manager, Server-Side) has its own toggle.

= Can I use this with other analytics plugins? =

While technically possible, we recommend using only Matomo Site Kit for analytics to avoid conflicts and duplicate tracking.

= How do I get support? =

For support, please visit our [GitHub repository](https://github.com/openmost/openmost-site-kit) or contact us through [openmost.io](https://openmost.io).

= How can I contribute? =

Contributions are welcome! Visit our [GitHub repository](https://github.com/openmost/openmost-site-kit) to report issues, suggest features, or submit pull requests.

== Screenshots ==

1. Dashboard
2. Settings - General
3. Settings - Tracking
4. Settings - Dashboard
5. Settings - Privacy

== Changelog ==

= 2.2.0 =
Release date: 2025-03-06

**New Features:**

* Server-Side PHP Tracking - Track page views server-side for ad-blocker resistance and enhanced privacy
* WooCommerce Ecommerce Tracking - Complete ecommerce analytics for all three tracking methods
* Site Search Tracking - Automatically track WordPress searches with keyword, category, and result count
* Automatic Annotations - Create Matomo annotations when posts are published
* Customizable annotation format with variables ({post_type}, {title}, {url}, {author})

**Improvements:**

* Restructured Settings page with intuitive tracking method cards
* Each tracking method now clearly shows its required fields
* Separated feature cards for better organization (GDPR, Heartbeat, User ID, Ecommerce, Search)
* Renamed Dashboard tab to Features tab for clarity
* Intelligent bot detection for server-side tracking
* Privacy opt-out shortcode now uses modern script-based approach
* Full WordPress Coding Standards compliance
* Enhanced security with proper escaping and sanitization throughout
* Added REST API nonce for improved security
* Performance optimizations with DNS prefetch and script preloading

**Technical:**

* Complete code audit for security and best practices
* Improved internationalization (i18n) support
* PHP 8.2+ compatibility
* WordPress 6.0+ required

= 2.1.1 =
Release date: 2025-11-24

* Tested on WordPress 6.9

= 2.1.0 =
Release date: 2025-11-24

* Refactored Settings page with tabbed interface
* Added Dashboard tab for API configuration
* Added User ID tracking feature (SHA256 hashed email)
* Added Heartbeat Timer option for classic tracking
* Improved Tag Manager dataLayer with wordpress.user_id
* Removed setup wizard in favor of streamlined settings
* Various UI/UX improvements
* Added noscript image tracker fallback

= 2.0.0 =
Release date: 2025-11-20

* Complete refactor using React and WordPress components
* New modern dashboard interface
* WordPress Dashboard widget
* Post/Page analytics metabox

= 1.1.2 =
Release date: 2024-09-25

* Fix missing function get_value()

= 1.1.0 =
Release date: 2023-06-29

* Add dataLayer sync
* Add Matomo details in dataLayer
* Fix Matomo Cloud instances support

= 1.0.2 =
Release date: 2023-06-27

* Support Matomo Cloud CDN in tracking codes

= 1.0.0 =
Release date: 2023-05-17

* Initial plugin release

== Upgrade Notice ==

= 2.2.0 =
Major feature release! New server-side tracking, WooCommerce ecommerce, site search tracking, and automatic annotations. Includes security improvements and WordPress coding standards compliance. Recommended for all users.

= 2.1.0 =
New User ID tracking and Heartbeat Timer features. Improved settings interface.

= 2.0.0 =
Complete redesign with modern React interface. New dashboard widget and post analytics.
