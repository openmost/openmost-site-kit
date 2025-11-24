=== Matomo Site Kit ===

Contributors: Openmost
Requires at least: 6.0
Tested up to: 6.8
Stable tag: 2.1.0
Tags: matomo, analytics, tracking, statistics, privacy
Requires PHP: 8.2
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A complete Matomo integration for WordPress with dashboard, data layer and code injection.

== Description ==

Matomo Site Kit allows your WordPress instance to connect with Matomo Cloud or On-Premise.
You can easily provide Matomo or Matomo Tag Manager tracking code.
Get in touch with the dashboard and respect the law with GDPR script easy deployment.

**Features:**

* Dashboard with analytics overview
* Classic Matomo tracking code injection
* Matomo Tag Manager (MTM) support with dataLayer
* User ID tracking (SHA256 hashed)
* GDPR consent mode options
* Privacy opt-out shortcode
* Post/Page analytics metabox
* WordPress Dashboard widget
* Exclude tracking by user role

== Installation ==

No skills required

In order to make this plugin working, you have to set your Matomo host (something like `https://matomo.my-company.com`)
You need a site id and a container ID if you want to use the Tag Manager (recommended).
The token auth (API key) is required to display data in the dashboard menu.

== Frequently Asked Questions ==

= Should I remove default Matomo code present in my theme or provided by other extensions? =

Yes, Matomo Site Kit is an all-in-one Matomo plugin that provides all you need to collect data on WordPress.

= Can I add custom data to Data Layer? =

Yes you can, the data layer provided by this plugin is generic, you can add your own data.

= How to visualize data layer content? =

To visualize data layer content, you may open the developer console in your web browser and type `_mtm`.
This will allow you to view and navigate in the data layer.

== Screenshots ==

1. Dashboard
2. Settings - General
3. Settings - Tracking
4. Settings - Dashboard
5. Settings - Privacy

== Changelog ==

= 2.1.0 =
Release date: 2025-11-24

* Refactored Settings page with tabbed interface
* Added Dashboard tab for API configuration
* Added User ID tracking feature (SHA256 hashed email)
* Added Heartbeat Timer option for classic tracking
* Improved Tag Manager dataLayer with wordpress.user_id
* Removed setup wizard in favor of streamlined settings
* Various UI/UX improvements
* Added noscript image tracker fallback for users with JavaScript disabled
* Privacy opt-out shortcode now uses iframe for WordPress coding standards compliance

= 2.0.0 =
Release date: 2025-11-20

* Complete refactor using React and WP components
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
* Fix Matomo cloud instances support

= 1.0.2 =
Release date: 2023-06-27

* Support Matomo Cloud CDN in tracking codes

= 1.0.0 =
Release date: 2023-05-17

* Plugin first release
