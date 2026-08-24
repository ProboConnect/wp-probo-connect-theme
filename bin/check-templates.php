<?php
/**
 * Compare the theme's WooCommerce template overrides against the installed plugin.
 *
 * Reports the same drift WooCommerce → Status → Templates warns about, but from
 * the command line, so it can run before a release rather than after a shop
 * owner sees the notice.
 *
 * Usage:
 *   npm run check:templates
 *   php bin/check-templates.php [path/to/plugins/woocommerce]
 *
 * @package Probo_Connect
 */

$theme_dir     = dirname( __DIR__ );
$template_root = $theme_dir . '/woocommerce';

/**
 * Read the @version header from a template.
 *
 * @param string $file Absolute path.
 * @return string Version, or '' when the file carries no header.
 */
function probo_template_version( $file ) {
	$handle = fopen( $file, 'r' );

	if ( ! $handle ) {
		return '';
	}

	$version = '';

	// The header is in the docblock, so only the opening lines are read.
	for ( $line = 0; $line < 40 && ! feof( $handle ); $line++ ) {
		if ( preg_match( '/@version\s+([0-9.]+)/', (string) fgets( $handle ), $match ) ) {
			$version = $match[1];
			break;
		}
	}

	fclose( $handle );

	return $version;
}

/**
 * Locate the WooCommerce plugin directory.
 *
 * @param string $theme_dir Theme root.
 * @return string Absolute path, or '' when not found.
 */
function probo_find_woocommerce( $theme_dir ) {
	global $argv;

	if ( ! empty( $argv[1] ) ) {
		return rtrim( $argv[1], '/' );
	}

	// The theme is often symlinked into the site, so both its real path and the
	// symlinked one are walked upwards looking for a wp-content next to us.
	$roots = array_unique( array( $theme_dir, (string) realpath( $theme_dir ) ) );

	foreach ( $roots as $root ) {
		$dir = $root;

		for ( $depth = 0; $depth < 6; $depth++ ) {
			$dir = dirname( $dir );

			$candidates = array( $dir . '/plugins/woocommerce', $dir . '/wp-content/plugins/woocommerce' );

			// A theme checked out next to its site — the usual symlinked setup —
			// only finds WooCommerce by looking into sibling directories. The site
			// that actually has this theme installed comes first, so a machine
			// hosting several WordPress installs still reports the right versions.
			if ( $depth < 2 ) {
				$siblings = glob( $dir . '/*/wp-content/plugins/woocommerce' ) ?: array();

				usort(
					$siblings,
					static function ( $a, $b ) use ( $theme_dir ) {
						$installed = static function ( $plugin_dir ) use ( $theme_dir ) {
							$themes = dirname( $plugin_dir, 2 ) . '/themes/' . basename( $theme_dir );

							return realpath( $themes ) === realpath( $theme_dir ) ? 0 : 1;
						};

						return $installed( $a ) <=> $installed( $b );
					}
				);

				$candidates = array_merge( $candidates, $siblings );
			}

			foreach ( $candidates as $candidate ) {
				if ( is_dir( $candidate . '/templates' ) ) {
					return $candidate;
				}
			}
		}
	}

	return '';
}

$woocommerce = probo_find_woocommerce( $theme_dir );

if ( ! $woocommerce ) {
	fwrite( STDERR, "WooCommerce niet gevonden. Geef het pad mee:\n  php bin/check-templates.php ../wordpress/wp-content/plugins/woocommerce\n" );
	exit( 2 );
}

$plugin_version = '?';

if ( preg_match( '/^\s*\*\s*Version:\s*(.+)$/m', (string) file_get_contents( $woocommerce . '/woocommerce.php' ), $match ) ) {
	$plugin_version = trim( $match[1] );
}

printf( "WooCommerce %s — %s\n\n", $plugin_version, $woocommerce );

$files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $template_root, FilesystemIterator::SKIP_DOTS ) );
$rows  = array();
$stale = 0;

foreach ( $files as $file ) {
	if ( $file->isDir() || 'php' !== $file->getExtension() ) {
		continue;
	}

	$relative = substr( $file->getPathname(), strlen( $template_root ) + 1 );
	$original = $woocommerce . '/templates/' . $relative;

	if ( ! file_exists( $original ) ) {
		$stale++;
		$rows[] = array( 'ONBEKEND', $relative, '—', 'geen template met deze naam in WooCommerce' );
		continue;
	}

	$ours    = probo_template_version( $file->getPathname() );
	$theirs  = probo_template_version( $original );
	$drifted = $ours && $theirs && version_compare( $theirs, $ours, '>' );

	if ( $drifted ) {
		$stale++;
	}

	$rows[] = array(
		$drifted ? 'VEROUDERD' : 'OK',
		$relative,
		$ours ? $ours : '—',
		$theirs ? $theirs : '—',
	);
}

usort(
	$rows,
	static function ( $a, $b ) {
		return array( $a[0], $a[1] ) <=> array( $b[0], $b[1] );
	}
);

foreach ( $rows as $row ) {
	printf( "%-10s %-34s thema %-8s wc %s\n", $row[0], $row[1], $row[2], $row[3] );
}

printf( "\n%d van %d templates verouderd of onbekend.\n", $stale, count( $rows ) );

exit( $stale > 0 ? 1 : 0 );
