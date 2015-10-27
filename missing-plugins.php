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

/*  Copyright 2015 Aubrey Portwood <aubreypwd@icloud.com>

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

require_once( 'class-missing-plugins.php' );

if ( class_exists( 'Missing_Plugins' ) && ! isset( $missing_plugins ) ) {
	$missing_plugins = new Missing_Plugins();
}
