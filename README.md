Multisite Import&#x2F;Export
============================

WPCLI plugin to import and export blogs in a multisite envirnonment.

Installation
------------

### Production
 - Head over to [releases](../../releases)
 - Download 'multisite-import-export.zip'
 - Upload and activate it like any other WordPress plugin
 - AutoUpdate will run as long as the plugin is active

### Development
 - cd into your plugin directory
 - $ `git clone git@github.com:mcguffin/multisite-import-export.git`
 - $ `wp plugin activate --network multisite-import-export`

### Using Composer
```
composer require mcguffin/multisite-import-export
```

Configuration Constants
-----------------------

The plugin uses `passthru`

### `MUIMEX_MYSQLDUMP_CLI`
The mysqldump commad to use for export.  
Default: `mysqldump -u <DB_USER> -p<DB_PASSWORD> -h <DB_HOST>`

#### Examples
```php
// wp-config.php
define( 'MUIMEX_MYSQLDUMP_CLI', 'mariadbdump -u eddi -psecr3t -h 127.0.0.1' );
```

### `MUIMEX_MYSQL_CLI`
The mysql commad to use for import.  
Default: `mysql -u <DB_USER> -p<DB_PASSWORD> -h <DB_HOST>`

#### Example
```php
// wp-config.php
define( 'MUIMEX_MYSQL_CLI', 'mysql -u eddi -psecr3t -h 127.0.0.1' );
```

WPCLI Commands
--------------

### Export a blog
```shell
wp mu export subdomain.somewhere.com ../exports/
```

Will place 3 files under `../exports/`:
 - a manifest file in json format
 - an sql dump
 - the blogs upload dir in a zip file


### Import a blog
```shell
wp mu import subdomain.somewhere-else.com ../exports/20270511-subdomain.somewhere.com.json
 ```

Will create or update the site unter the domain `subdomain.somewhere-else.com`.

The blog’s **upload directory** will be replaced entirely. To keep your previous uploads add the `--skip_fs` parameter

**Post authors** will be changed to the first user in database who is member of the blog and has the `publish_pages` capability.  
To explicitly choose an author use the `--author=123` parameter.
Skip this step with `--skip_authors`.

**URL Replacement:** During import the hostname of the old blog (the one you exported from) is being replaced with the new blog’s hostname.  
URLs that look like pointing to `wp-content/uploads/sites/<blog_id>/` are also adjusted.  
Skip this step with `--skip_urls`.

Hooks
-----

### Import
#### Action hook `muimex/before_import`
Before a blog is imported.
```php
/**
 * @param string $domain Domain being imported
 * @param array $cli_args
 */
add_action('muimex/before_import', function( $domain, $cli_args ) {
	// do some preparation for $domain
}, 10, 2 );
```

#### Action hook `muimex/import/update_blog`
Before a blog is being updated
```php
/**
 * @param int $blog_id Blog ID being imported
 */
add_action('muimex/import/created_blog', function( $blog_id ) {
	// do some praparation with $blog_id
} );
```

#### Action hook `muimex/import/created_blog`
After a blog has been newly created.
```php
/**
 * @param int $blog_id Blog ID being imported
 */
add_action('muimex/import/created_blog', function( $blog_id ) {
	// do some praparation with $blog_id
} );
```

#### Filter hook `muimex/import/sql_command`
Shell command to import sql.
```php
/**
 * @param string $sql_command Shell command to import db.
 * @param int $blog_id Blog ID being imported
 */
add_filter('muimex/import/sql_command', function( $sql_command, $blog_id ) {
	// change $sql_command
	return $sql_command;
}, 10, 2 );
```

#### Action hook `muimex/import/db`
Right before sql shell command file is run.
```php
/**
 * @param int $blog_id Blog ID being imported
 * @param string $sql_file Path to processed sql file
 */
add_action('muimex/import/db', function( $blog_id, $sql_file ) {
	// do something with $sql_file
}, 10, 2 );
```

#### Action hook `muimex/after_import`
After successfull import.
```php
/**
 * @param int $blog_id Blog ID being imported
 * @param string $sql_file Path to processed sql file
 */
add_action('muimex/after_import', function( $blog_id ) {
	// Post process $blog_id
} );
```

### Export
#### Action hook `muimex/before_export`
After export dir has been created and right before exporting starts.
```php
/**
 * @param int $blog_id Blog ID being exported
 * @param string $target_dir Path to export files
 */
add_action('muimex/after_export', function( $blog_id, $target_dir ) {
	// Pre process $blog_id
}, 10, 2 );
```

#### Action hook `muimex/after_export`
After successfull export.
```php
/**
 * @param int $blog_id Blog ID being exported
 * @param string $target_dir Path to export files
 */
add_action('muimex/after_export', function( $blog_id, $target_dir ) {
	// Post process $blog_id
}, 10, 2 );
```
