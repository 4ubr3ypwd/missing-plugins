<?php

/*
Plugin Name: Missing Plugins
Plugin URI:
Description: Helps you install missing plugins that were previously active
Version: 1.0
Author: Aubrey Portwood
Author URI: http://aubreypwd.com
License: GPL2
*/

/*
 *  __  __ _       _             ___ _           _
 * |  \/  (_)_____(_)_ _  __ _  | _ \ |_  _ __ _(_)_ _  ___
 * | |\/| | (_-<_-< | ' \/ _` | |  _/ | || / _` | | ' \(_-<
 * |_|  |_|_/__/__/_|_||_\__, | |_| |_|\_,_\__, |_|_||_/__/
 *                       |___/             |___/
 *
 * Missing Plugins is a plugin that will help people and developers
 * that move WordPress sites around. If any active plugins in the database
 * are missing plugin files in your plugins folder, this plugin will
 * give you an opportunity to install and activate them before WordPress
 * disables them automatically.
 *
 * GNU License
 *
 * Copyright 2015 Aubrey Portwood <aubreypwd@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

/*
 * Disable loading Missing Plugins.
 *
 * In wp-config.php use `define( 'DISABLE_MISSING_PLUGINS', true );` to disable
 * this plugin from doing anything.
 */
if ( defined( 'DISABLE_MISSING_PLUGINS' ) && DISABLE_MISSING_PLUGINS ) {
	return; // Bail, this site doesn't want to run Missing Plugins.
}

require_once( 'class-missing-plugins.php' ); // Launch Missing Plugins...
