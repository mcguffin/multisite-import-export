<?php

/*
Plugin Name: Multisite Import&#x2F;Export
Plugin URI: http://wordpress.org/
Description: Enter description here.
Author: mcguffin
Version: 0.0.1
Author URI: https://github.com/mcguffin
License: GPL3
Requires WP: 4.8
Requires PHP: 5.6
Text Domain: multisite-import-export
Domain Path: /languages/
*/

/*  Copyright 2024 mcguffin

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

/*
Plugin was generated with Jörn Lund's WP Skelton
https://github.com/mcguffin/wp-skeleton
*/


namespace MultisiteImportExport;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}


require_once __DIR__ . DIRECTORY_SEPARATOR . 'include/autoload.php';

Core\Core::instance( __FILE__ );

if ( is_admin() || defined( 'DOING_AJAX' ) ) {
}

