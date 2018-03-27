# Dead Simple Dropbox Backups

This is a WordPress plugin that will backup a site's database and uploads to a connected Dropbox account. The plugin can also be set to automatically create backups on a daily or weekly basis.


## Setup

Currently, there is no official Dropbox app for this, so you'll need to create an app for it :/ Don't worry, it's not hard, and I am looking at how the whole official Dropbox app thing works.

After creating a Dropbox app, grab the app's 'Access Token'. Add it to the plugin's settings, this is how the plugin will communicate and verify itself with Dropbox.

After that, you just need to set how often you want the plugin to create backups!


## Help/Troubleshooting

There are a couple reasons that the plugin may not be working for you.

* You have the wrong Dropbox Token
* File permissions

### Dropbox Token

You need to make sure that you have the correct token given during the app setup page. The plugin doesn't use the App key or secret, it uses the 'Generated access token'.

### File Permissions

To upload the zipped version of the uploads and the database, the plugin needs to create the zip file and the database file. Meaning that the plugin needs its folder to have write permissions. If it doesn't then it can't create the files to upload!


## Contributing

You can report any issues found! Please give as detailed a report about what you were doing and what happened as you can.

Developers, feel free to fork the repo - make your changes - create a pull request. When you do PLEASE add a detailed description of both what your changes are AND the reason for them.
