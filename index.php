<?php

/*
Plugin Name: Multisite Import&#x2F;Export
Plugin URI: http://wordpress.org/
Description: WPCLI plugin for import and export blogs in a multisite envirnonment.
Author: mcguffin
Version: 0.1.1
Author URI: https://github.com/mcguffin
License: GPL3
Requires WP: 6.0
Requires PHP: 8.1
Text Domain: multisite-import-export
Domain Path: /languages/
Network: true
Update URI: https://github.com/mcguffin/multisite-import-export/raw/main/.wp-release-info.json
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

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WPCLI\WPCLI::instance();
}

// Enable WP auto update
add_filter( 'update_plugins_github.com', function( $update, $plugin_data, $plugin_file, $locales ) {

	if ( ! preg_match( "@{$plugin_file}$@", __FILE__ ) ) { // not our plugin
		return $update;
	}

	$response = wp_remote_get( $plugin_data['UpdateURI'] );

	if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) > 200 ) { // response error
		return $update;
	}

	return json_decode( wp_remote_retrieve_body( $response ), true, 512 );
}, 10, 4 );
