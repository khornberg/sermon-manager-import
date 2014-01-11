<?php
/**
 * @package   Sermon Manager Import
 * @author    Kyle Hornberg
 * @license   GPLv3
 * @link      https://github.com/khornberg/sermon-manager-import
 * @copyright 2013 Kyle Hornberg
 *
 * @wordpress-plugin 
 * Plugin Name: Sermon Manager Import
 * Plugin URI: https://github.com/khornberg/sermon-manager-import
 * Description: Imports sermons into <a href="https://bitbucket.org/wpforchurch/sermon-manager-for-wordpress" target="blank">Sermon Manger for Wordpress</a> using ID3 information.
 * Version: 0.2.3
 * Author: Kyle Hornberg
 * Author URI: https://github.com/khornberg
 * Author Email:
 * Text Domain: sermon-manager-import
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Domain Path: /languages
 *
 * Copyright 2013
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License, version 3, as
 *   published by the Free Software Foundation.
 *
 *   This program is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with this program; if not, write to the Free Software
 *   Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
*/



// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once plugin_dir_path( __FILE__ ) . 'views/options.php';
require_once( plugin_dir_path( __FILE__ ) . 'class-sermon-manager-import.php' );

// Register hooks that are fired when the plugin is activated or deactivated.
// When the plugin is deleted, the uninstall.php file is loaded.
register_activation_hook( __FILE__, array( 'SermonManagerImport', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'SermonManagerImport', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'SermonManagerImport', 'get_instance' ) );

//sdg
