<?php
/**
 * Theme unit test APIs.
 *
 * @package Theme Unit Test
 */

add_action(
	'rest_api_init',
	function () {
		$GLOBALS['ttd_user_id'] = get_current_user_id();
		register_rest_route(
			'ttd/v1',
			'import/',
			array(
				'methods'  => 'GET',
				'callback' => 'ttd_import_api',
			)
		);
	}
);


add_action(
	'rest_api_init',
	function () {
		$GLOBALS['ttd_user_id'] = get_current_user_id();
		register_rest_route(
			'ttd/v1',
			'import_blocks/',
			array(
				'methods'  => 'GET',
				'callback' => 'ttd_import_blocks',
			)
		);
	}
);

/**
 * Function to import data.
 */
function ttd_import_xml( $xml_file, $option_prefix ) {

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

	if ( '/%year%/%monthnum%/%day%/%postname%/' === get_option( 'permalink_structure' ) ) {
		global $wp_rewrite;
		$wp_rewrite->set_permalink_structure( '/%postname%/' );
		update_option( 'permalink_structure', '/%postname%/' );
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
	$wp_import->import( $xml_file );
	$wp_import_msg = trim( ob_get_clean() );

	// Update author.
	foreach ( $wp_import->processed_posts as $post_id ) {
		$arg = array(
			'ID'          => $post_id,
			'post_author' => $GLOBALS['ttd_user_id'],
		);

		$post = get_post( $post_id );

		// Update navigation-link urls.
		if ( 'wp_navigation' === $post->post_type ) {

			$post_content = str_replace( $wp_import->base_url, site_url(), $post->post_content );

			$arg['post_content'] = $post_content;
		}

		// Update GUIDs.
		if ( isset( $post->guid ) ) {

			$guid = str_replace( $wp_import->base_url, site_url(), $post->guid );
			$guid = str_replace( 'http://wpthemetestdata.wordpress.com', site_url(), $post->guid );

			$arg['guid'] = $guid;
		}

		wp_update_post( $arg );
	}

	$options                    = get_option( 'ttd-options' );
	$options[ 'imported_posts' .$option_prefix  ]   = $wp_import->processed_posts;
	$options[ 'imported_terms' . $option_prefix ]   = $wp_import->processed_terms;
	$options[ 'ttd_import_demo' . $option_prefix  ] = TTD_IMPORT;

	if ( '_blocks' === $option_prefix ) {
		$options[ 'ttd_import_demo' . $option_prefix  ] = TTD_IMPORT_BLOCKS;
	}

	$options[ 'date' .$option_prefix  ]             = time();

	update_option( 'ttd-options', $options );

	echo $wp_import_msg; // phpcs:ignore
	die();
}

/**
 * Function to import data.
 */
function ttd_import_api() {
	ttd_import_xml( TTD_DIR . '/assets/xml/wptest.xml', '' );
}

/**
 * Function to import blocks post data.
 */
function ttd_import_blocks() {
	ttd_import_xml( TTD_DIR . '/assets/xml/64-block-test-data.xml', '_blocks' );
}

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'ttd/v1',
			'remove/',
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

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'ttd/v1',
			'remove_blocks/',
			array(
				'methods'  => 'GET',
				'callback' => 'ttd_remove_blocks_api',
			)
		);
	}
);

/**
 * Function to remove data.
 */
function ttd_remove_blocks_api() {
	ob_start();
	$options = get_option( 'ttd-options' );

	// Remove posts.
	foreach ( $options['imported_posts_blocks'] as $key => $value ) {
		if ( 'attachment' === get_post_type( $value ) ) {
			wp_delete_attachment( $value, true );
		} else {
			wp_delete_post( $value, true );
		}
		unset( $options['imported_posts_blocks'][ $key ] );
	}

	// Remove terms.
	foreach ( $options['imported_terms_blocks'] as $key => $value ) {
		$taxonomy = get_term( $value )->taxonomy;
		wp_delete_term( $value, $taxonomy );
		unset( $options['imported_terms_blocks'][ $key ] );
	}

	$options['ttd_import_blocks_demo'] = TTD_REMOVE_BLOCKS;
	$options['date']            = time();

	update_option( 'ttd-options', $options );
	$ttd_remove_msg = trim( ob_get_clean() );

	if ( '' === $ttd_remove_msg ) {
		$ttd_remove_msg = esc_html__( 'Blocks posts removed', 'theme-unit-data' );
	}

	echo $ttd_remove_msg; // phpcs:ignore.
	die;
}
