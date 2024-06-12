<?php

namespace MultisiteImportExport\Helper;

class URLReplacer {

	/**
	 *	@param string $hostname
	 *	@return assoc [ 'options' => [ ... ], 'posts' => [...], 'terms' => [...], 'postmeta' => [...], 'termmeta' => [...] ]
	 */
	public static function find( $hostname ) {

		return [
			'options'  => self::find_options( $hostname ),
			'posts'    => self::find_posts( $hostname ),
			'terms'    => self::find_terms( $hostname ),
			'postmeta' => self::find_postmeta( $hostname ),
			'termmeta' => self::find_termmeta( $hostname ),
		];
	}

	/**
	 *	@param string $hostname
	 *	@return array [ [ 'option_id' => '...', 'option_name' => '...', 'option_value' => '...' ], ... ]
	 */
	public static function find_options( $hostname ) {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT option_id, option_name, option_value FROM {$wpdb->options} WHERE option_value LIKE %s",
				'%' . $wpdb->esc_like(  "://{$hostname}" ) . '%'
			)
		);
	}

	/**
	 *	@param string $hostname
	 *	@return array [ [ 'ID' => '...', 'post_content' => '...' ], ... ]
	 */
	public static function find_posts( $hostname ) {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID, post_name, post_content FROM {$wpdb->posts} WHERE post_content LIKE %s",
				'%' . $wpdb->esc_like( "://{$hostname}" ) . '%'
			)
		);
	}

	/**
	 *	@param string $hostname
	 *	@return array [ [ 'term_taxonomy_id' => '...', 'description' => '...' ], ... ]
	 */
	public static function find_terms( $hostname ) {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT term_taxonomy_id, description FROM {$wpdb->term_taxonomy} WHERE description LIKE %s",
				'%' . $wpdb->esc_like( "://{$hostname}" ) . '%'
			)
		);
	}

	/**
	 *	@param string $hostname
	 *	@return array [ [ 'meta_id' => '...', 'meta_value' => '...' ], ... ]
	 */
	public static function find_postmeta( $hostname ) {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT meta_id, post_id, meta_key, meta_value FROM {$wpdb->postmeta} WHERE meta_value LIKE %s",
				'%' . $wpdb->esc_like("://{$hostname}") . '%'
			)
		);
	}

	/**
	 *	@param string $hostname
	 *	@return array [ [ 'meta_id' => '...', 'meta_value' => '...' ], ... ]
	 */
	public static function find_termmeta( $hostname ) {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT meta_id, meta_key, meta_value FROM {$wpdb->termmeta} WHERE meta_value LIKE %s",
				'%' . $wpdb->esc_like( "://{$hostname}" ) . '%'
			)
		);
	}

	/**
	 *	@param string $hostname
	 *	@return assoc [ 'options' => [ ... ], 'posts' => [...], 'terms' => [...], 'postmeta' => [...], 'termmeta' => [...] ]
	 */
	public static function replace( $hostname, $new_hostname ) {
		if ( $hostname === $new_hostname ) {
			return false;
		}
		$result = self::find( $hostname );
		self::replace_options(  $result['options'],  "://{$hostname}", "://{$new_hostname}" );
		self::replace_posts(    $result['posts'],    "://{$hostname}", "://{$new_hostname}" );
		self::replace_terms(    $result['terms'],    "://{$hostname}", "://{$new_hostname}" );
		self::replace_postmeta( $result['postmeta'], "://{$hostname}", "://{$new_hostname}" );
		self::replace_termmeta( $result['termmeta'], "://{$hostname}", "://{$new_hostname}" );
		return self::find( $hostname );
	}

	/**
	 *	@param string $hostname
	 *	@param string $new_hostname
	 */
	public static function replace_options( $rows, $search, $replace ) {

		global $wpdb;

		foreach ( $rows as $row ) {
			$wpdb->query( $wpdb->prepare(
				"UPDATE {$wpdb->options} SET option_value = %s WHERE option_id = %d",
				self::replace_value( $row->option_value, $search, $replace ),
				$row->option_id
			) ); //
		}
	}

	/**
	 *	@param string $hostname
	 *	@param string $new_hostname
	 */
	public static function replace_posts( $rows, $search, $replace ) {

		global $wpdb;

		foreach ( $rows as $row ) {
			$wpdb->query( $wpdb->prepare(
				"UPDATE {$wpdb->posts} SET post_content = %s WHERE ID = %d",
				self::replace_value( $row->post_content, $search, $replace ),
				$row->ID
			) );
		}
	}

	/**
	 *	@param string $hostname
	 *	@param string $new_hostname
	 */
	public static function replace_terms( $rows, $search, $replace ) {

		global $wpdb;

		foreach ( $rows as $row ) {
			$wpdb->query( $wpdb->prepare(
				"UPDATE {$wpdb->term_taxonomy} SET description = %s WHERE term_taxonomy_id = %d",
				self::replace_value( $row->description, $search, $replace ),
				$row->term_taxonomy_id
			) );
		}
	}

	/**
	 *	@param string $hostname
	 *	@param string $new_hostname
	 */
	public static function replace_postmeta( $rows, $search, $replace ) {

		global $wpdb;

		foreach ( $rows as $row ) {
			$wpdb->query( $wpdb->prepare(
				"UPDATE {$wpdb->postmeta} SET meta_value = %s WHERE meta_id = %d",
				self::replace_value( $row->meta_value, $search, $replace ),
				$row->meta_id
			) );
		}
	}

	/**
	 *	@param string $hostname
	 *	@param string $new_hostname
	 */
	public static function replace_termmeta( $rows, $search, $replace ) {

		global $wpdb;

		foreach ( $rows as $row ) {
			$wpdb->query( $wpdb->prepare(
				"UPDATE {$wpdb->termmeta} SET meta_value = %s WHERE meta_id = %d",
				self::replace_value( $row->meta_value, $search, $replace ),
				$row->meta_id
			) );
		}
	}


	/**
	 *	@param string $hostname
	 *	@return assoc [ 'options' => [ ... ], 'posts' => [...], 'terms' => [...], 'postmeta' => [...], 'termmeta' => [...] ]
	 */
	public static function find_blog_id( $blog_id ) {
		return [
			'options'  => self::find_blog_id_options( (int) $blog_id ),
			'posts'    => self::find_blog_id_posts( (int) $blog_id ),
			'terms'    => self::find_blog_id_terms( (int) $blog_id ),
			'postmeta' => self::find_blog_id_postmeta( (int) $blog_id ),
			'termmeta' => self::find_blog_id_termmeta( (int) $blog_id ),
		];
	}

	/**
	 *	@param int $blog_id
	 *	@return array [ [ 'option_id' => '...', 'option_name' => '...', 'option_value' => '...' ], ... ]
	 */
	public static function find_blog_id_options( $blog_id ) {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT option_id, option_name, option_value FROM {$wpdb->options} WHERE option_value LIKE %s",
				'%' . $wpdb->esc_like(  "/wp-content/uploads/sites/{$blog_id}/" ) . '%'
			)
		);
	}

	/**
	 *	@param int $blog_id
	 *	@return array [ [ 'ID' => '...', 'post_content' => '...' ], ... ]
	 */
	public static function find_blog_id_posts( $blog_id ) {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID, post_name, post_content FROM {$wpdb->posts} WHERE post_content LIKE %s",
				'%' . $wpdb->esc_like(  "/wp-content/uploads/sites/{$blog_id}/" ) . '%'
			)
		);
	}

	/**
	 *	@param int $blog_id
	 *	@return array [ [ 'term_taxonomy_id' => '...', 'description' => '...' ], ... ]
	 */
	public static function find_blog_id_terms( $blog_id ) {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT term_taxonomy_id, description FROM {$wpdb->term_taxonomy} WHERE description LIKE %s",
				'%' . $wpdb->esc_like(  "/wp-content/uploads/sites/{$blog_id}/" ) . '%'
			)
		);
	}

	/**
	 *	@param int $blog_id
	 *	@return array [ [ 'meta_id' => '...', 'meta_value' => '...' ], ... ]
	 */
	public static function find_blog_id_postmeta( $blog_id ) {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT meta_id, post_id, meta_key, meta_value FROM {$wpdb->postmeta} WHERE meta_value LIKE %s",
				'%' . $wpdb->esc_like(  "/wp-content/uploads/sites/{$blog_id}/" ) . '%'
			)
		);
	}

	/**
	 *	@param int $blog_id
	 *	@return array [ [ 'meta_id' => '...', 'meta_value' => '...' ], ... ]
	 */
	public static function find_blog_id_termmeta( $blog_id ) {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT meta_id, meta_key, meta_value FROM {$wpdb->termmeta} WHERE meta_value LIKE %s",
				'%' . $wpdb->esc_like(  "/wp-content/uploads/sites/{$blog_id}/" ) . '%'
			)
		);
	}


	/**
	 *	@param int $blog_id
	 *	@param int $new_blog_id
	 *	@return assoc [ 'options' => [ ... ], 'posts' => [...], 'terms' => [...], 'postmeta' => [...], 'termmeta' => [...] ]
	 */
	public static function replace_blog_id( $blog_id, $new_blog_id ) {
		if ( $blog_id === $new_blog_id ) {
			return false;
		}
		$result = self::find_blog_id( $blog_id );
		self::replace_options(  $result['options'],  "/wp-content/uploads/sites/{$blog_id}/", "/wp-content/uploads/sites/{$new_blog_id}/" );
		self::replace_posts(    $result['posts'],    "/wp-content/uploads/sites/{$blog_id}/", "/wp-content/uploads/sites/{$new_blog_id}/" );
		self::replace_terms(    $result['terms'],    "/wp-content/uploads/sites/{$blog_id}/", "/wp-content/uploads/sites/{$new_blog_id}/" );
		self::replace_postmeta( $result['postmeta'], "/wp-content/uploads/sites/{$blog_id}/", "/wp-content/uploads/sites/{$new_blog_id}/" );
		self::replace_termmeta( $result['termmeta'], "/wp-content/uploads/sites/{$blog_id}/", "/wp-content/uploads/sites/{$new_blog_id}/" );
		return self::find_blog_id( $blog_id );
	}

	/**
	 *	@param mixed $value
	 *	@param string $search
	 *	@param string $replace
	 */
	private static function replace_value( &$value, $search, $replace ) {
		$serialized = false;
		if ( is_string( $value ) ) {
			$unserialized_value = maybe_unserialize( $value );
			if ( $unserialized_value !== $value ) {
				$value = $unserialized_value;
				$serialized = true;
			}
		}
		if ( is_array( $value ) ) {
			foreach ( $value as $k => &$v ) {
				$v = self::replace_value( $v, $search, $replace );
			}
		} else if ( is_object( $value ) && ( $value instanceof stdClass ) ) {
			foreach ( array_keys( get_object_vars( $value ) ) as $k ) {
				$value->$k = self::replace_value( $value->$k, $search, $replace );
			}
		} else if ( is_string( $value ) ) {
			$value = str_replace( $search, $replace, $value );
		}
		if ( $serialized ) {
			$value = serialize( $value );
		}
		return $value;
	}

}
