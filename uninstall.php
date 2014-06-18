<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package   Sermon Manager Import
 * @author    Kyle Hornberg
 * @license   GPLv3
 * @link      https://github.com/khornberg/sermon-manager-import
 * @copyright 2013 Kyle Hornberg
 */

// If uninstall, not called from WordPress, then exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// TODO: Define uninstall functionality here