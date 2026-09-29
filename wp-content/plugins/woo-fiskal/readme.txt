=== WooFiskal ===
Contributors: Switch One
Tags: payment, credit card, fiskalizacija, woocommerce, fiskal, racuni
Requires at least: 4.0
Tested up to: 5.9
Stable tag: 1.4.4
Requires PHP: 5.6
License: GNU General Public License v2.0 (or later)
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

Automatska fiskalizacija WooCommerce narudžbi kroz WebRačun servis

== Changelog ==

= 1.4.4 =
* Fixed bulk status change error in PHP 8.0.
* Fixed memory limit exhaustions on plugin activation ( while checking orders for previous complete-fiskal = 1 meta)

= 1.4.3 =
* Fixed product sale price combined with coupon bug

= 1.4.2 =
* Fixed empty product TAX issue

= 1.4.1 =
* Added wf_ prefix to all functions and files to avoid conflicts 

= 1.4 =
* Removed function that disableds sending email on order completed status change 

= 1.3 =
* Fixed coupon percentage and tax combination

= 1.2 =
* Added support for taxes.
* Added option for automatic data sending if order status is "Processing".
* Added custom status "completed-fiskal" to order preview lightbox

= 1.1 =
* Added support for variable products.
* Added support for R1 invoices.

= 1.0 =
* Added description and instructions.
* Added option for automatic data sending if order status is "Completed"
* Added WebRačun API connection for invoices
