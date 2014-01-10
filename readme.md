# Sermon Manager Import
- Contributors: khornberg
- Tags: sermon, sermon manager, mp3, podcasting, id3, podcast, podcaster, audio, music, spokenword
- Requires at least: 3.5
- Tested up to: 3.8
- Stable tag: 0.2.2
- License: GPLv3

Imports sermons into Sermon Manager using ID3 information.

## Description

Imports sermons into [Sermon Manager for Wordpress](https://bitbucket.org/wpforchurch/sermon-manager-for-wordpress) using ID3 information. Only MP3 files are currently supported. Files can either be uploaded via the WordPress uploader or through another method. The plugin adds files to the `wp-content/uploads/sermon-manager-import` folder by default. A different folder can be specified in the options. The plugin only searches the base folder specified! Files added through the WordPress uploader will show up in the media library as unattached. The files are then attached to the sermon when imported. 

When the sermon is posted, the file is moved to the uploads folder using the organization method selected in the WordPress settings. Sermons can be posted in the `publish` or `draft` status.

## Installation

1. Upload the plugin directory to the `/wp-content/plugins/` directory via FTP or `git clone https://github.com/khornberg/sermon-manager-import` in the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress

## Frequently Asked Questions

### Why do my uploads keep going to the sermon-manager-import folder?
While this plugin is activated, mp3 files will go to the folder specified in the `Import Options`. I recommend you activate this plugin only when needed and disable it when not needed.

### What is ID3?
[ID3](http://en.wikipedia.com/wiki/ID3) is metadata for most MP3 files. When you use a media player (e.g. iTunes, Windows Media Player, etc.) the title, artist, etc. is stored within each file in the ID3 format.

### I get a `Fatal error: Maximum execution time of 30 seconds exceeded`
Likely you are importing a lot of sermons. Refresh the page and import all of the remaining sermons again. Repeat as necessary if that doesn't do it. Your server is set to run a process for limited time. When importing many sermons, you reach this limit and the server lets you know.

### Does this work with Amazon S3 or other serivces?
Not sure. Please let me know.

### Can I help?
Sure can. See the Contributing section below.

## Contributing
If you want to contribute go to [Github](github.com), fork, and send a pull request. Issues and comments are welcome as well.

### **WARNINGS**
* All uploads identified as `audio/mp3` (usually only MP3 files) are uploaded to the import folder specified. All other files will be uploaded to the normal upload directory.  
* When posting a file that is an unattached entry, the unattached entry will be deleted. Normally, this is not an issue and is only a temporary entry. However, if you manually attached the uploaded media to a post, it will not work after importing the sermon. This is an unlikely scenario.  
* This plugin does not have the ability to add media already in the WordPress media library to sermon manager. To do this, one would manually (ssh, ftp, etc) move the files to the specified import folder. Then continue as normal. This method will delete the previous entry in the media library. If you have the media attached to another post, the old post will not work.
* While this plugin is activated, mp3 files will go to the folder specified in the `Import Options`. I recommend you activate this plugin only when needed and disable it when not needed.

## Screenshots

![Screenshot Menu](screenshot-2.png)
![Screenshot Import](screenshot-1.png)
![Screenshot Options](screenshot-3.png)

## TODO (if interest is expressed)
- Add featured image from ID3 embedded picture
- Import other types of audio files

## Changelog

### 0.2.2
* Sets new podcasting options as of Sermon Manager 1.8
* Add explaination when files are not imported
* Made details screen more clear

### 0.2.1
* Added option to set service type based on merdiem
* Remove old files

### 0.2
* Added GUI to allow customized bind of ID3 tags to Sermon Manager fields (e.g. comment to bible passage, date from the file name, etc.)  
* Warns if sermon manager not activated, will not import if the sermon manager plugin is not activated 
* Added support to import sermon as a draft  
* Added Options page
* Can find multiple date formats and use them for the date of the sermon
* Code refactor for use on WordPress Multisite, efficiency, maintainability
* Removed bootstrap dependency  
* Updated WordPress help screen

### 0.1
* Initial Release

## Upgrade Notice

### 0.2.1
Added option to set service type based on merdiem

### 0.2
Added many options including specifying the ID3 tags used to import into Sermon Manager.

## Thank you
Thank you Jack for creating a useful and well documented plugin. Other themes and plugins are available at [WordPress for Church](http://www.wpforchurch.com/).

Tom McFarlin's [WordPress Plugin Boilerplate](https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate).  

James Heinrich's [getID3](https://github.com/JamesHeinrich/getID3).  

Paul Sheldrake's [MP3 to Post Plugin](www.fractured-state.com/2011/09/mp3-to-post-plugin).
