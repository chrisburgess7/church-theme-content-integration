=== Plugin Name ===
Contributors: chrisburgess7
Tags: church, churches, integration, fellowshipone
Requires at least: 3.9
Tested up to: 3.9.1
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Provides integration between the Church Theme Content plugin and other church-related service providers.

== Description ==

This plugin is designed for users of the [Church Theme Content WP plugin](http://wordpress.org/plugins/church-theme-content/).
It adds the ability to synchronise people data from Fellowship One into CTC people records.

## Requirements

- The [Church Theme Content WP plugin](http://wordpress.org/plugins/church-theme-content/). Note that CTC Integration
will still install without CTC, but you wont be able to run anything. Tested against CTC 1.0.8+
- The PHP cURL library to communicate with Fellowship One. This plugin, once activated, will check that cURL exists and
inform you if it doesn't, as well as preventing you from running the affected synchronisations.

== Installation ==

## From your WordPress dashboard

1. Visit 'Plugins > Add New'
2. Search for 'Church Theme Content Integration'
3. Activate Church Theme Content Integration from your Plugins page.

## From WordPress.org

1. Download Church Theme Content Integration.
2. Upload the 'church-theme-content-integration' directory to your '/wp-content/plugins/' directory, using your favorite method (ftp, sftp, scp, etc...)
3. Activate Church Theme Content Integration from your Plugins page.

## Setup (Important!)

### Fellowship One

In order to sync data from your Fellowship One account:

1. Get your [API key](http://developer.fellowshipone.com/key/):
  - Select the People, Groups, and Events realms. Currently only the People and Group realms are required, but support for Events may be added later.

2. In WP, navigate to CTC Integration -> Fellowship One. Enter your API URL (base URL only, minus the '/v1/'),
   Consumer Key and Consumer Secret.

3. **If** you are using 2nd party credential based authentication, enter your username and password. However, note that this
will be stored as plain text in the wordpress database, so use with care!

#### Difference between 2nd Party and 3rd Party authentication

This plugin allows you to use either authentication method.
To keep it simple, if in doubt, use 3rd party authentication as it is more secure. Use 2nd-party only if you know the risks.

Here are some reasons to help you decide.

Use 3rd party authentication if:

- You want the best security.
- You are happy (or indeed, would prefer) to only allow those with Fellowship One accounts to be able to run the
synchronisation.

Use 2nd Party authentication if:

- You are ok with having the username and password of one of your Fellowship One accounts stored as plain text
in the WP database.
- You would like to allow individuals at your church with web admin access the ability to synchronise data even if
 they don't have a Fellowship One account.

#### People Sync Setup

1. In Fellowship One, create people list(s) of the people that you want to sync.

2. In Wordpress Admin, under CTC Integration -> Fellowship One, add the names of those people list(s) to the 'People Lists to Sync' option.

## Uninstallation

If you decide to uninstall the plugin, the uninstall process will delete all custom tables, and custom post meta tags,
  that this plugin uses to track associations between CTC records and the service provider records that they were
  synced from. This does not make any changes to the data that was synced, that is, the CTC fields themselves.

== Frequently Asked Questions ==

= Can I sync data from Church Theme Content to my X account? =

No. All synchronisations are uni-directional, that is, from your service provider account into Church Theme Content.
This makes for a simpler and safer plugin.

== Changelog ==

= 0.4.2 =
* Documentation updates

= 0.4.1 =
* Documentation updates
* Minor code improvements

= 0.4 =
* First release

== Upgrade Notice ==

= 0.4.2 =
Documentation updates only.

= 0.4.1 =
Only minor code improvements and documentation updates.

= 0.4 =
First release

== Fellowship One Options ==

- People Lists to Sync: Type in the names of the people list(s) that you created in Fellowship One, one on each line.

- Sync Lists to Groups?: Check this if you would like the people list(s) to correspond to groups of the same name in CTC.
Uncheck this if you don't want the people records added to groups in CTC, or if you would like to manage groups in CTC manually.

- Name Format: Select the name format to be used for the name field in CTC.

- Sync Position? / Position Attribute Group: For the position field in CTC, you can optionally sync that as well. To do so,
in Fellowship One create an Attribute Group (with any name), and define Attributes in that group with the names of
your positions. Add these position attributes to the appropriate people records. Then in CTC Integration, check the
Sync Position? checkbox, and enter the name of the Fellowship One Attribute Group into the Position Attribute Group field.

- Sync ...?: These checkboxes control whether or not each of those fields will be synced. In the case of Phone and Email,
the entry designated as the preferred contact will be used.
