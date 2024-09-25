=== Matomo Site Kit by Openmost ===

Contributors: Openmost
Requires at least: 6.0
Tested up to: 6.6.2
Stable tag: 1.1.1
Tags: matomo, piwik, analytics, site, kit, tracking, statistics, stats, analytic
Requires PHP: 7.2
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

== Description ==

Openmost Site Kit allow you website to expose a DataLayer ready to use for your Matomo instance.
You can easly provide Matomo or Matomo Tag Manager tracking code. Expose a full data layer with contextual data.
Get in touch with the dashboard and respect the law with GDPR script easy deployment.


== Installation ==

No skills required

In order to make this plugin working, you have to set your Matomo host (something like ```https://matomo.my-company.com```)
You need a site id and a container ID if you want to use the Tag Manager (recommended).
The token auth (API key) is required to display data in the dashboard menu.

== Frequently Asked Questions ==

= Should I remove default Matomo code present in my theme or provided by other extensions ? =

Yes, Openmost Site Kit is a all in one Matomo plugin that provide all you need to collect data on WordPress.

= Can I add custom data to Data Layer ? =

Yes you can, the data layer provided by this plugin is generic, you can add your own data.

= How to visualize data layer content ? =

To visualize data layer content, you may open the developper console in your web browser and type for ```_mtm```.
This will allow you to view and navigate in the data layer.

== Screenshots ==

1. Datalayer settings

== Changelog ==

= 1.1.1 =
Release date: 2024-09-25

Fix missing function get_value()

= 1.1.0 =
Release date: 2023-06-29

Add dataLayer sync
Add Matomo details in dataLayer
Fix Matomo cloud instances support

= 1.0.2 =
Release date: 2023-06-27

Support Matomo Cloud CDN in tracking codes

= 1.0.0 =
Release date: 2023-05-17

Plugin first release, enjoy all the features !
