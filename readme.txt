=== Sermon Manager Import ===
- Contributors: khornberg
- Tags: sermon, sermon manager, mp3, podcasting, id3, podcast, podcaster, audio, music, spokenword
- Requires at least: 3.0
- Tested up to: 3.6
- Stable tag: 0.1
- License: GPLv3

Imports sermons into Sermon Manager using ID3 information.

== Description ==

Imports sermons into [Sermon Manager for Wordpress](https://bitbucket.org/wpforchurch/sermon-manager-for-wordpress) using ID3 information. Only MP3 files are currently supported. Files can either be uploaded via the WordPress uploader or put on the server through another method. The plugin adds files to the `wp-content/uploads/sermon-manager-import` folder. Files added through the WordPress uploader will show up in the media library as unattached. *WARNING* when posting a file like this the unattached entry will be deleted. Normally, this is not an issue and is only a temporary entry. However, if you manually attached the uploaded media to a post, it will not work after importing the sermon. This is an unlikely senario.

When the sermon is posted, the file is moved to the uploads folder using the organization method selected in the WordPress settings. Sermons are posted in the `publish` status.

This plugin does not have the ability to add media already in the WordPress media library to sermon manager. To do this, one would manually (ssh, ftp, etc) move the files to the sermon-manager-import folder. Then continue as normal. *WARNING* this will delete the previous entry in the media library. If you have the media attached to another post, that old post will not work.

== Installation ==

1. Upload the plugin directory to the `/wp-content/plugins/` directory via FTP or `git clone https://github.com/khornberg/sermon-manager-import` in the `/wp-content/plugins/` directory.  
2. Activate the plugin through the 'Plugins' menu in WordPress

== Contributing ==
If you want to contribute go to [Github](github.com), fork, and send a pull request. Issues and comments are welcome as well.

== **WARNING** ==
This plugin is currently customized for my local church. You should not use it without first modifying the code.  
1. Remove the if/else statement at `#372` in the `sermon-manager-import.php` file.   
2. Modify the array at `#378` to match the ID3 fields of your sermons.  

== Screenshots ==

[Screenshot Menu](Screenshot1.png)
[Screenshot Import](Screenshot2.png)

== TODO ==
- Prevent activation if sermon manager not activated
- Activation, deactivation, uninstall functions
- Add support to publish as draft
- Code refactor
- Add GUI to allow customized bind of ID3 tags to Sermon Manager fields (e.g. comment to bible passage, date from the file name, etc.)
- Add support for video import
- Add support for other audio formats and id3 versions
- Test picture upload from ID3 embedded pictures
- Remove bootstrap dependecy
- Add PHP Unit tests

== Changelog ==

= 0.1 =
* Inital Release

== Thank you ==
Thank you Jack for creating a useful and well documented plugin. Other themes and plugins are available at [WordPress for Church](http://www.wpforchurch.com/).

Tom McFarlin's [WordPress Plugin Boilerplate](https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate).

James Heinrich's [getID3](https://github.com/JamesHeinrich/getID3).
