=== Cartpipe QuickBooks Desktop Integration for WooCommerce ===
Contributors: Cartpipe
Donate Link: 
Tags: woocommerce, quickbooks, quickbooks desktop, xero, freshbooks, accounting, ecommerce, integrations
Requires at least: 3.0.1
Tested up to: 4.5.2
Stable tag: 1.1.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Cartpipe QuickBooks Desktop Integration for WooCommerce

== Description ==

Cartpipe QuickBooks Desktop Integration API for WooCommerce is the easy-to-use, easy-to-configure QuickBooks Desktop Integration you've been looking for. It painlessly connects WooCommerce with your QuickBooks Desktop Accounting Software 
Once installed, the plugin will:

*	Send WooCommerce customers to QuickBooks
*	Sync website prices with the values from QuickBooks
*	Sync website on-hand qty with QuickBooks 
*	Send orders from the website to QuickBooks as sales receipts, sales orders, or invoices
*	Import items from WooCommerce into QuickBooks 
*	Import items from QuickBooks into WooCommerce

Please note, this plugin is the API used by the Cartpipe for Desktop windows app found [here](https://www.cartpipe.com/services/cartpipe-for-quickbooks-desktop/) 
Once setup, you can schedule the app to run on a schedule or manually.

== Installation ==

Installation / configuration is straightforward. 

1. Install Cartpipe API either via the plugin installing in your wordpress admin, or by uploading the files to your server and extracting.
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to Cartpipe 
1. Select the Order Transfer Status to use. This says what order status should be sent to QuickBooks. 
1. Select or change the Default Order Sync Status. This says whether a product should / shouldn't sync with QuickBooks by default 
1. You shouldn't need to change the Number of Products in API request setting unless you see performance issues with the sync. If you do, decrease this number. 
1. Create a WooCommerce > Settings > API and make sure the "Enable Rest API" option is enabled. Enable it if it's not. 
1. On the WooCommerce > Settings > API page, click the Keys/Apps section link, and then click the Add Key button
1. You can use any description you'd like for the Description field, but make sure the Permissions dropdown is set to Read/Write. Click the Generate API Key button.
1. Save the Consumer Key and Consumer Secret for use in the Cartpipe for QuickBooks Desktop app. 


Once you've gone through those steps, you just need to create an account an get the Cartpipe QuickBooks Desktop app [here](https://www.cartpipe.com/services/cartpipe-for-quickbooks-desktop/ "QuickBooks Desktop Integration for WooCommerce") to be taken to Cartpipe.com 
When the app's been installed on your PC, enter your license and Consumer Key / Consumer Secret you generated above, and click the "Connect to QuickBooks" button in the app. WooCommerce is now connected to your QuickBooks Desktop App. 
== Frequently Asked Questions ==

= Do I need an account on Cartpipe.com? =

Yes. You need to have an account to get the Cartpipe Desktop app. 

= Do I need the Cartpipe for Desktop App? =
Yes. This plugin is the API that the Cartpipe for Desktop App uses to communicate with your website. 

= Where can I get the Cartpipe for Desktop app? =

You can get the app [here](https://www.cartpipe.com/services/cartpipe-for-quickbooks-desktop/, "QuickBooks Desktop Integration for WooCommerce")

= Do I need a QuickBooks Online account? =

No. This plugin is for use with QuickBooks Desktop software and the Cartpipe for Desktop App. 

= Does this work with desktop versions of QuickBooks? =
Yes.

= Does this work with Mac versions of QuickBooks? =
No.

== Screenshots ==

1. Cartpipe API settings
2. Cartpipe for Desktop App  


== Changelog ==
= 1.1.6 =
* Set default empty array on cpd init.

= 1.1.5 =
* Restore product import into WC.

= 1.1.4 =
* Update readme. Add download link

= 1.1.3 =
* First release

== Upgrade Notice ==
Initial Release