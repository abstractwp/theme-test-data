<?php
/**
 * Theme unit test APIs.
 *
 * @package Theme Unit Test
 */

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'ttd/v1',
			'/import',
			array(
				'methods'  => 'GET',
				'callback' => 'ttd_import_api',
			)
		);
	}
);

/**
 * Function to import data.
 */
function ttd_import_api() {

	/** WordPress Import Administration API */
	require_once ABSPATH . 'wp-admin/includes/import.php';
	require_once ABSPATH . 'wp-admin/includes/post.php';
	require_once ABSPATH . 'wp-admin/includes/comment.php';
	require_once ABSPATH . 'wp-admin/includes/taxonomy.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';

	if ( ! class_exists( 'WP_Importer' ) ) {
		$class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';
		if ( file_exists( $class_wp_importer ) ) {
			require $class_wp_importer;
		}
	}

	if ( ! class_exists( 'WP_Import' ) ) {

		/** Functions missing in older WordPress versions. */
		require_once TTD_DIR . '/include/wordpress-importer/compat.php';

		/** WXR_Parser class */
		require_once TTD_DIR . '/include/wordpress-importer/parsers/class-wxr-parser.php';

		/** WXR_Parser_SimpleXML class */
		require_once TTD_DIR . '/include/wordpress-importer/parsers/class-wxr-parser-simplexml.php';

		/** WXR_Parser_XML class */
		require_once TTD_DIR . '/include/wordpress-importer/parsers/class-wxr-parser-xml.php';

		/** WXR_Parser_Regex class */
		require_once TTD_DIR . '/include/wordpress-importer/parsers/class-wxr-parser-regex.php';

		/** WP_Import class */
		require_once TTD_DIR . '/include/wordpress-importer/class-wp-import.php';

	}

	ob_start();
	$wp_import = new WP_Import();

	$wp_import->fetch_attachments = true;
	$wp_import->import( TTD_DIR . '/assets/xml/themeunittestdata.WordPress.xml' );
	$wp_import_msg = trim( ob_get_clean() );

	$options                    = get_option( 'ttd-options' );
	$options['imported_posts']  = $wp_import->processed_posts;
	$options['imported_terms']  = $wp_import->processed_terms;
	$options['ttd_import_demo'] = TTD_IMPORT;
	$options['date']            = time();

	update_option( 'ttd-options', $options );

	echo $wp_import_msg; // phpcs:ignore
	die();
}

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'ttd/v1',
			'/remove',
			array(
				'methods'  => 'GET',
				'callback' => 'ttd_remove_api',
			)
		);
	}
);

/**
 * Function to remove data.
 */
function ttd_remove_api() {
	ob_start();
	$options = get_option( 'ttd-options' );

	// Remove posts.
	foreach ( $options['imported_posts'] as $key => $value ) {
		if ( 'attachment' === get_post_type( $value ) ) {
			wp_delete_attachment( $value, true );
		} else {
			wp_delete_post( $value, true );
		}
		unset( $options['imported_posts'][ $key ] );
	}

	// Remove terms.
	foreach ( $options['imported_terms'] as $key => $value ) {
		$taxonomy = get_term( $value )->taxonomy;
		wp_delete_term( $value, $taxonomy );
		unset( $options['imported_terms'][ $key ] );
	}

	$options['ttd_import_demo'] = TTD_REMOVE;
	$options['date']            = time();

	update_option( 'ttd-options', $options );
	$ttd_remove_msg = trim( ob_get_clean() );

	if ( '' === $ttd_remove_msg ) {
		$ttd_remove_msg = esc_html__( 'Data removed', 'theme-unit-data' );
	}

	echo $ttd_remove_msg; // phpcs:ignore.
	die;
}
