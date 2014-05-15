# Church Theme Content Integration

Provides integration functionality between the Church Theme Content WordPress plugin and other church-related service
providers.

Please note that this plugin only imports data from service providers into the Church Theme Content plugin.
It does not export data from CTC into your service provider account. This helps to keep the plugin simpler, as well as
being safer for your data.

Currently supports:

- Fellowship One / People Sync: This allows you to import people records from a Fellowship One account into CTC as
 custom People posts.

**This project is currently in beta only.**

# Requirements

This plugin synchronises data into custom post types created by the
[Church Theme Content WP plugin](http://wordpress.org/plugins/church-theme-content/), so install that on your WordPress
site if you have not already. Note that CTC Integration will still install without CTC, but you wont be able to run
anything.

This plugin also requires the use of the PHP cURL library to communicate with Fellowship One.
This plugin, once activated, will check that it exists and inform you if it doesn't, as well as preventing you from
running the affected synchronisations.

# Installation

Create a zip file containing the church-theme-content-integration sub-folder, and install it using the standard
WordPress plugin installer.

## Fellowship One

1. Get your [API key](http://developer.fellowshipone.com/key/). Select the People, Groups, and Events realms.

2. In WP, navigate to CTC Integration -> Fellowship One. Enter your API URL, Consumer Key and Consumer Secret.
If using 2nd party credential based authentication, enter your username and password (however, note that this
will be stored as plain text in the wordpress database, so use with care!).

#### Difference between 2nd Party and 3rd Party authentication

This plugin allows you to use either authentication method. Here are some reasons to help you decide.

Use 3rd party authentication if:

- You want the best security.
- You are happy (or indeed, would prefer) to only allow those with Fellowship One accounts to be able to run the
synchronisation.

Use 2nd Party authentication if:

- You are ok with having the username and password of one of your Fellowship One accounts stored as plain text
in the WP database.
- You would like to allow individuals at your church with web admin access the ability to synchronise data even if
 they don't have a Fellowship One account.

### People Sync

In order to sync people from your Fellowship One account to CTC, the steps are as follows:

1. In your Fellowship One account, create people list(s) that define which people records you want to sync. Add the people
you want to sync to those people list(s). If you would like these people list(s) to match to groups in CTC,
name these people lists with the group name(s) as you would like them to appear in CTC.

2. In WP, under CTC Integration -> Fellowship One, review the settings:

- People Lists to Sync: Type in the names of the people list(s) that you created in Fellowship One, one on each line.

- Sync Lists to Groups?: Check this if you would like the people lists to correspond to groups in CTC. Uncheck this if
 you don't want the people records added to groups in CTC, or if you would like to manage groups in CTC manually.

- Name Format: Select the name format to be used for the name field in CTC.

- Sync Position? / Position Attribute Group: For the position field in CTC, you can optionally sync that as well. To do so,
in Fellowship One create an Attribute Group (with any name), and define Attributes in that group with the names of
your positions. Add these position attributes to the appropriate people records. Then in CTC Integration, check the
Sync Position? checkbox, and enter the name of the Fellowship One Attribute Group into the Position Attribute Group field.

- Sync ...?: These checkboxes control whether or not each of those fields will be synced. In the case of Phone and Email,
the entry designated as the preferred contact will be used.

3. Save any changes made.

4. Navigate to CTC Integration -> CTC Integration (the default options page displayed if you click the top heading).
This is the page from which all synchronisations are run. If you are using 3rd Party OAuth authentication, the button
will indicate that you need to authenticate (if you haven't already done so before). Click this to be redirected to a
page from the Service Provider with which to authenticate. You will then be redirected back to the CTC Integration page,
and the button text will have changed to indicate that you can now run the synchronisation. If using 2nd Party
Credentials based authentication, this redirecting authentication step will not occur, and you will be able to run the
synchronisation immediately.

5. Once you have clicked the run button, the page will wait for the process to complete. When it does, information
pertaining to what has occurred will be displayed under Message Log.

# Uninstallation

If you decide to uninstall the plugin, the uninstall process will delete all custom tables, and custom post meta tags,
  that this plugin uses to track associations between CTC records and the service provider records that they were
  synced from. This does not make any changes to the data that was synced, that is, the CTC fields themselves.