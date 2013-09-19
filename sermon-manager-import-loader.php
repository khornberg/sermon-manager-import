<?php
/**
 * Plugin Name: Sermon Manager Import
 * Plugin URI: https://github.com/khornberg/sermon-manager-import
 * Description: Imports sermons into <a href="https://bitbucket.org/wpforchurch/sermon-manager-for-wordpress" target="blank">Sermon Manger for Wordpress</a> using ID3 information.
 * Version: 0.1
 * Author: Kyle Hornberg
 * Author URI: https://github.com/khornberg
 * Author Email:
 * License: GPLv3
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
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Add Main Page
require_once plugin_dir_path( __FILE__ ) . '/sermon-manager-import.php';

// Add Options Page
require_once plugin_dir_path( __FILE__ ) . '/views/options.php';

//sdg
