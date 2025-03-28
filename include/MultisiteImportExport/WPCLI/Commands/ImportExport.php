<?php

namespace MultisiteImportExport\WPCLI\Commands;

use MultisiteImportExport\Helper;
use WP_CLI;

class ImportExport extends \WP_CLI_Command {


	/**
	 * Export a Site
	 *
	 * ## OPTIONS
	 *
	 * <domain>
	 * : Domain
	 *
	 * <target_dir>
	 * : Target Directory
	 *
	 * [--skip_db]
	 * : Dont export DB Dump
	 *
	 * [--skip_fs]
	 * : Dont export Uploads
	 *
	 *
	 * ## EXAMPLES
	 *
	 *     # Export site to
	 *     $ wp bsb export foo.bar.quux /somewhere/over/the/rainbow
	 *
	 */
	public function export( $args, $assoc_args ) {

		global $wpdb, $table_prefix;
		$assoc_args = wp_parse_args( $assoc_args, [
			'skip_db' => false,
			'skip_fs' => false,
		]);
		$success = true;

		list( $domain, $target_dir ) = $args;
		$blog = $wpdb->get_row( $wpdb->prepare("SELECT blog_id, domain FROM $wpdb->blogs WHERE domain = %s", $args[0] ) );
		if ( ! $blog ) {
			WP_CLI::error( 'No such blog' );
			return;
		}

		$target_dir = untrailingslashit( $target_dir );

		if ( ! is_dir( $target_dir ) ) {
			mkdir( $target_dir, 0777, true );
		}

		$target_dir = realpath( $target_dir );

		do_action('muimex/before_export', $blog->blog_id, $target_dir );

		switch_to_blog( $blog->blog_id );
		$outfile_prefix = sprintf(
			'%1$s/%2$s-%3$s',
			$target_dir,
			date('Ymd'),
			$domain
		);
		$tables = $wpdb->get_col( $wpdb->prepare(
			"SHOW TABLES LIKE %s",
			$wpdb->esc_like( $wpdb->prefix ) . '%'
		) );
		if ( ! $assoc_args['skip_db'] ) {
			WP_CLI::line( 'Dump database');
			if ( defined( 'MUIMEX_MYSQLDUMP_CLI' ) ) {
				$dump_cmd = sprintf(
					'%1$s %2$s %3$s --complete-insert --quick --result-file=%4$s.sql',
					WPMU_MYSQLDUMP_CLI,
					escapeshellarg(DB_NAME),
					implode( ' ', $tables ),
					$outfile_prefix
				);

			} else {
				$dump_cmd = sprintf(
					'mysqldump -u %1$s -p%2$s -h %3$s %4$s %5$s --complete-insert --quick --result-file=%6$s.sql',
					escapeshellarg(DB_USER),
					escapeshellarg(DB_PASSWORD),
					escapeshellarg(DB_HOST),
					escapeshellarg(DB_NAME),
					implode( ' ', $tables ),
					$outfile_prefix
				);
			}

			passthru( $dump_cmd, $result_code );
			if ( $result_code ) {
				WP_CLI::error( 'Error dumping database' );
				return;
			}
		}

		if ( ! $assoc_args['skip_fs'] ) {
			WP_CLI::line( 'Export uploads');
			$upload_dirs = wp_upload_dir();

			$zip = 'Darwin' === PHP_OS
				? 'zip -rX'
				: 'zip -r';
			$zip_append = 'Darwin' === PHP_OS
				? '-x *\._*'
				: '';
			$zip_cmd = sprintf(
				'cd %2$s && rm -f %1$s-uploads.zip && %3$s %1$s-uploads.zip . %4$s',
				$outfile_prefix,
				$upload_dirs['basedir'],
				$zip,
				$zip_append
			);
			WP_CLI::line( $zip_cmd );
			passthru( $zip_cmd, $result_code );
			if ( $result_code ) {
				WP_CLI::error( 'Error dumping uploads' );
				return;
			}
		}

		// blog meta
		file_put_contents(
			"{$outfile_prefix}.json",
			json_encode( [
				'blog_id'     => (int) $blog->blog_id,
				'base_prefix' => $wpdb->base_prefix,
				'prefix'      => $wpdb->prefix,
				'domain'      => $blog->domain,
				'tables'      => $tables,
			], JSON_PRETTY_PRINT )
		);

		do_action('muimex/after_export', $blog->blog_id, $target_dir );

		WP_CLI::success( sprintf('Exported %s to %s', $domain, $target_dir ) );
	}

	/**
	 * Import a Site.
	 *
	 *
	 * ## OPTIONS
	 *
	 * <domain>
	 * : Domain
	 *
	 * <source_json>
	 * : Path to source json
	 *
	 * [--author=<author>]
	 * : Post author ID. Defaults to first user with capability publish_pages
	 *
	 * [--skip_db]
	 * : Dont import DB Dump
	 *
	 * [--skip_fs]
	 * : Dont import Uploads
	 *
	 * [--skip_authors]
	 * : Keep post author IDs as is.
	 *
	 * [--skip_urls]
	 * : Dont update URLs
	 *
	 *
	 * ## EXAMPLES
	 *
	 *     # import a site
	 *     $ wp bsb import foo.bar.quux /somewhere/over/the/rainbow.json
	 *
	 */
	public function import( $args, $assoc_args ) {

		global $wpdb;

		$assoc_args = wp_parse_args( $assoc_args, [
			'author'       => 0,
			'skip_db'      => false,
			'skip_fs'      => false,
			'skip_urls'    => false,
			'skip_authors' => false,
		]);

		list( $domain, $source_json ) = $args;

		if ( ! file_exists( $source_json ) ) {
			WP_CLI::error( 'Source JSON not found' );
			return;
		}

		if ( ! ( $source_blog = json_decode( file_get_contents( $source_json ) ) ) ) {
			WP_CLI::error( 'Error reading Source JSON' );
			return;
		}

		do_action('muimex/before_import', $args[0], $assoc_args );

		$target_blog = $wpdb->get_row( $wpdb->prepare("SELECT blog_id, domain FROM $wpdb->blogs WHERE domain = %s", $args[0] ) );

		if ( $target_blog ) {
			WP_CLI::line( sprintf( 'Updating blog %d', $target_blog->blog_id ) );
			do_action('muimex/import/update_blog', $target_blog->blog_id );
		} else {
			WP_CLI::line( sprintf( 'Creating new %s', $domain ) );
			$target_blog = (object) [
				'blog_id' => (int) wp_insert_site( [ 'domain' => $domain ] ),
				'domain'  => $domain,
			];
			do_action('muimex/import/created_blog', $target_blog->blog_id );
		}

		$source_blog->blog_id = (int) $source_blog->blog_id;
		$target_blog->blog_id = (int) $target_blog->blog_id;

		$infile_prefix = str_replace( '.json', '', $source_json );

		if ( ! $assoc_args['skip_db'] ) {
			$sql_file = "{$infile_prefix}.sql";
			if ( ! file_exists( $sql_file ) ) {
				WP_CLI::error( 'SQL File does not exist' );
			}

			$sql_tmp_file = false;

			$sql = file_get_contents( $sql_file );

			// set table prefixes
			if ( $target_blog->blog_id !== $source_blog->blog_id ) {
				$source_tables = $source_blog->tables;
				$target_tables = array_map( function( $table ) use ( $source_blog, $target_blog, $wpdb ) {
					return str_replace(
						$source_blog->prefix,
						$wpdb->get_blog_prefix( $target_blog->blog_id ),
						$table
					);

				}, $source_blog->tables );

				$sql          = str_replace( $source_tables, $target_tables, $sql );
				$sql_tmp_file = tempnam(sys_get_temp_dir(),'bsb'.$target_blog->blog_id );
				file_put_contents( $sql_tmp_file, $sql );
				$sql_file     = $sql_tmp_file;
			}
			if ( defined('MUIMEX_MYSQL_CLI') ) {
				// allow custom mysql command
				$mysql_cmd = sprintf(
					'%1$s %2$s < %3$s',
					MUIMEX_MYSQL_CLI,
					DB_NAME,
					$sql_file
				);
			} else {
				// default mysql with user and pwassword
				$mysql_cmd = sprintf(
					'mysql -u%1$s -p%2$s %3$s < %4$s',
					escapeshellarg(DB_USER),
					escapeshellarg(DB_PASSWORD),
					escapeshellarg(DB_NAME),
					$sql_file
				);
			}
			$mysql_cmd = apply_filters('muimex/import/sql_command', $mysql_cmd, $target_blog->blog_id );

			do_action('muimex/import/db', $target_blog->blog_id, $sql_file );

			passthru( $mysql_cmd, $result_code );
			if ( $result_code ) {
				WP_CLI::error( 'Error importing database' );
				return;
			}

			switch_to_blog( $target_blog->blog_id );

			// blog URL option
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->options} SET option_value = REPLACE(option_value, %s, %s ) WHERE option_name IN ('home','siteurl')",
					$source_blog->domain,
					$target_blog->domain
				)
			);

			// roles config
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->options} SET option_name = %s WHERE option_name = %s",
					$wpdb->get_blog_prefix( $target_blog->blog_id ) . 'user_roles',
					$wpdb->get_blog_prefix( $source_blog->blog_id ) . 'user_roles'
				)
			);

			// TODO: change domain in post content, post meta, options

			restore_current_blog();
		}

		if ( ! $assoc_args['skip_fs'] ) {
			$zip_file = "{$infile_prefix}-uploads.zip";
			if ( ! file_exists( $zip_file ) ) {
				WP_CLI::error( 'Uploads File does not exist' );
			}

			switch_to_blog( $target_blog->blog_id );
			$updir = wp_get_upload_dir();
			// TOOD: replace uploads, not additive
			$unzip_cmd = sprintf('rm -rf %1$s/* && mkdir -p %1$s && unzip -o %2$s -d %1$s', $updir['basedir'], $zip_file);

			passthru( $unzip_cmd, $result_code );
			if ( $result_code ) {
				WP_CLI::error( 'Error unzipping files' );
				return;
			}

			restore_current_blog();
		}

		if ( ! $assoc_args['skip_urls'] ) {

			switch_to_blog( $target_blog->blog_id );

			Helper\URLReplacer::replace( $source_blog->domain, $target_blog->domain );
			Helper\URLReplacer::replace_blog_id( $source_blog->blog_id, $target_blog->blog_id );

			restore_current_blog();
		}

		if ( ! $assoc_args['skip_authors'] ) {

			switch_to_blog( $target_blog->blog_id );
			$author_id = $assoc_args['author'];
			if ( ! $author_id ) {
				$author_id = $this->get_author_id( $target_blog->blog_id );
			}
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->posts} SET post_author = %s;",
					$author_id
				)
			);

			restore_current_blog();
		}

		do_action('muimex/after_import', $target_blog->blog_id );
	}

	/**
	 *	@param int $blog_id
	 */
	private function get_author_id( $blog_id = 0 ) {
		$try = [
			[ 'capability' => 'publish_pages', 'fields' => 'ID', 'number' => 1, 'blog_id' => $blog_id ],
			[ 'fields' => 'ID', 'number' => 1, 'blog_id' => $blog_id ],
		];
		foreach ( $try as $attempt ) {
			$users = get_users( $attempt );
			if ( count( $users ) ) {
				return (int) array_shift($users);
			}
		}
		$admins = get_super_admins();
		if ( count( $admins ) ) {
			$user = get_user_by( 'login', array_shift( $admins ) );
			return (int) $user->ID;
		}
		return 0;
	}
}
