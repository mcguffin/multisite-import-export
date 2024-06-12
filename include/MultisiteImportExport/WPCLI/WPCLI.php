<?php

namespace MultisiteImportExport\WPCLI;

use MultisiteImportExport\Core;

class WPCLI extends Core\Singleton {

	/**
	 *	@inheritdoc
	 */
	protected function __construct() {
		\WP_CLI::add_command( 'mu import', [ new Commands\ImportExport(), 'import' ], [
//			'before_invoke'	=> 'a_callable',
//			'after_invoke'	=> 'another_callable',
			'shortdesc'		=> 'Multisite Import&#x2F;Export commands',
//			'when'			=> 'before_wp_load',
			'is_deferred'	=> false,
		] );
		\WP_CLI::add_command( 'mu export', [ new Commands\ImportExport(), 'export' ], [
//			'before_invoke'	=> 'a_callable',
//			'after_invoke'	=> 'another_callable',
			'shortdesc'		=> 'Multisite Import&#x2F;Export commands',
//			'when'			=> 'before_wp_load',
			'is_deferred'	=> false,
		] );
	}
}
