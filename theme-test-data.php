<?php
/**
 * Plugin Name: Theme Unit Test
 * Plugin URI: https://github.com/abstractwp/theme-test-data
 * Description: The theme unit test data for demo.
 * Author: AbstractWP
 * Author URI: https://www.abstractwp.com/
 * Tags: theme test data, unit test, test
 * Version: 1.0.3
 * Text Domain: 'theme-test-data'
 * Domain Path: languages
 * Tested up to: 6.5
 *
 * You should have received a copy of the GNU General Public License
 * along with Theme Unit Test. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package   Theme Unit Test
 * @author    AbstractWP
 * @license   GPL-3.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'TTD_DIR', dirname( __FILE__ ) );
define( 'TTD_IMPORT', 'import' );
define( 'TTD_REMOVE', 'remove' );
define( 'TTD_IMPORT_BLOCKS', 'import_blocks' );
define( 'TTD_REMOVE_BLOCKS', 'remove_blocks' );

require_once 'include/api.php';

/**
 * Theme test data setting pages.
 */
class TTDSettings {
	/**
	 * Holds the values to be used in the fields callbacks.
	 *
	 * @var options
	 */
	private $options;

	/**
	 * Start up
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'page_init' ) );

		add_action( 'admin_notices', array( $this, 'ttd_admin_notice' ) );
		register_activation_hook( __FILE__, array( $this, 'ttd_activate' ) );

		// Filters.
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
	}

	/**
	 * Create transient to show notification at activate.
	 */
	public function ttd_activate() {
		set_transient( 'ttd_notification', true, 5 );
	}

	/**
	 * Show notification on activated.
	 */
	public function ttd_admin_notice() {
		/* Check transient, if available display notice */
		if ( get_transient( 'ttd_notification' ) ) {
			?>
			<div class="updated notice is-dismissible">
				<p><?php echo esc_html__( 'To import demo data you can go to', 'theme-unit-data' ); ?> <a href="<?php echo esc_url( site_url( '/wp-admin/tools.php?page=ttd-settings' ) ); ?>"><?php echo esc_html__( 'Theme Unit Test', 'theme-unit-data' ); ?></a></p>
			</div>
			<?php
			/* Delete transient, only display this notice once. */
			delete_transient( 'ttd_notification' );
		}
	}

	/**
	 * Add options page.
	 */
	public function add_plugin_page() {
		// This page will be under "Tools".
		add_management_page(
			'Theme Unit Test',
			'Theme Unit Test',
			'manage_options',
			'ttd-settings',
			array( $this, 'create_admin_page' )
		);
	}

	/**
	 * Options page callback.
	 */
	public function create_admin_page() {
		// Set class property.
		$this->options = get_option( 'ttd-options' );
		?>
		<div class="wrap">
			<h1>Theme Unit Test</h1>
			<form method="post" action="options.php" id="ttd-settings-form">
			<?php
				settings_fields( 'ttd-options' );
				do_settings_sections( 'ttd-settings' );
			if ( isset( $this->options['ttd_import_demo'] ) ) {
				$ttd_title = TTD_IMPORT === $this->options['ttd_import_demo'] ? 'Imported' : 'Removed';
				printf( '<strong>Data %s at %s </strong><br />', esc_html( $ttd_title ), esc_html( gmdate( 'm-d-Y H:i:s', $this->options['date'] ) ) );
			}

			if ( isset( $this->options['ttd_import_blocks_demo'] ) ) {
				$ttd_title = TTD_IMPORT_BLOCKS === $this->options['ttd_import_blocks_demo'] ? 'Blocks Imported' : ' Blocks Removed';
				printf( '<strong>Data %s at %s </strong><br />', esc_html( $ttd_title ), esc_html( gmdate( 'm-d-Y H:i:s', $this->options['date'] ) ) );
			}
				submit_button( esc_html__( 'Submit', 'theme-test-data' ) );
			?>
			</form>
			<div id="import-results" class="hidden">
				<img src="<?php echo esc_url( $this->get_plugin_url( __FILE__ ) . 'assets/images/loading.gif' ); ?>" width="40"/>
			</div>
		</div>
		<?php
		wp_enqueue_script( 'ttd-script', $this->get_plugin_url( __FILE__ ) . 'assets/js/index.js', array( 'jquery' ), '1.0.0', false );
		wp_localize_script( 'ttd-script', 'WPURLS', array( 'siteurl' => get_rest_url() ) );
	}

	/**
	 * Register and add settings.
	 */
	public function page_init() {
		register_setting(
			'ttd-options',
			'ttd-options',
			array( $this, 'sanitize' )
		);

		add_settings_section(
			'import_section',
			'Theme unit data settings',
			array( $this, 'print_section_info' ),
			'ttd-settings'
		);

		add_settings_field(
			'ttd_import_demo',
			'What do you want to do?',
			array( $this, 'ttd_demo_callback' ),
			'ttd-settings',
			'import_section'
		);

		add_settings_field(
			'ttd_import_blocks_demo',
			'What do you want to do?',
			array( $this, 'ttd_demo_blocks_callback' ),
			'ttd-settings',
			'import_section'
		);
	}

	/**
	 * Sanitize each setting field as needed.
	 *
	 * @param array $input Contains all settings fields as array keys.
	 */
	public function sanitize( $input ) {
		$new_input = array();
		if ( isset( $input['ttd_import_demo'] ) ) {
			$new_input['ttd_import_demo'] = sanitize_text_field( $input['ttd_import_demo'] );
			$new_input['date']            = time();
		}

		return $new_input;
	}

	/**
	 * Print the Section text.
	 */
	public function print_section_info() {
		print 'Import or remove demo data';
	}

	/**
	 * Get the settings option array and print one of its values.
	 */
	public function ttd_demo_callback() {
		$ttd_import_disable = '';
		$ttd_remove_disable = '';
		// if ( isset( $this->options['ttd_import_demo'] ) ) {
		// 	$ttd_import_disable = TTD_IMPORT === $this->options['ttd_import_demo'] ? 'disabled' : '';
		// 	$ttd_remove_disable = TTD_REMOVE === $this->options['ttd_import_demo'] ? 'disabled' : '';
		// }

		printf(
			'<input type="radio" id="ttd_import_demo" name="ttd-options[ttd_import_demo]" value="' . esc_html( TTD_IMPORT ) . '" %s />
			<label for="ttd_import_demo">Import demo</label>&nbsp;&nbsp;&nbsp;&nbsp;
			<input type="radio" id="ttd_remove_demo" name="ttd-options[ttd_import_demo]" value="' . esc_html( TTD_REMOVE ) . '" %s />
			<label for="ttd_remove_demo">Remove demo</label>',
			esc_html( $ttd_import_disable ),
			esc_html( $ttd_remove_disable )
		);
	}


	/**
	 * Get the settings option array and print one of its values.
	 */
	public function ttd_demo_blocks_callback() {
		$ttd_import_blocks_disable = '';
		$ttd_remove_blocks_disable = '';
		// if ( isset( $this->options['ttd_import_demo_blocks'] ) ) {
		// 	$ttd_import_blocks_disable = TTD_IMPORT_BLOCKS === $this->options['ttd_import_demo_blocks'] ? 'disabled' : '';
		// 	$ttd_remove_blocks_disable = TTD_REMOVE_BLOCKS === $this->options['ttd_import_demo_blocks'] ? 'disabled' : '';
		// }

		printf(
			'<input type="radio" id="ttd_import_demo_blocks" name="ttd-options[ttd_import_demo]" value="' . esc_html( TTD_IMPORT_BLOCKS ) . '" %s />
			<label for="ttd_import_demo_blocks">Import blocks</label>&nbsp;&nbsp;&nbsp;&nbsp;
			<input type="radio" id="ttd_remove_demo_blocks" name="ttd-options[ttd_import_demo]" value="' . esc_html( TTD_REMOVE_BLOCKS ) . '" %s />
			<label for="ttd_remove_demo_blocks">Remove blocks</label>',
			esc_html( $ttd_import_blocks_disable ),
			esc_html( $ttd_remove_blocks_disable )
		);
	}

	/**
	 * Get the publicly accessible URL for the module based on the filename.
	 *
	 * @param string $file File path for the module.
	 * @return string $module_url Publicly accessible URL for the module.
	 */
	private function get_plugin_url( $file ) {
		$module_url = plugins_url( '/', $file );
		return trailingslashit( $module_url );
	}

	/**
	 * Plugin row meta links
	 *
	 * @param array|array   $input already defined meta links.
	 * @param string|string $file plugin file path and name being processed.
	 * @return array $input
	 */
	public function plugin_row_meta( $input, $file ) {

		if ( 'theme-test-data/theme-test-data.php' !== $file ) {
			return $input;
		}

		$url = site_url( '/wp-admin/tools.php?page=ttd-settings' );

		$links = array(
			'<a href="' . esc_url( $url ) . '">' . esc_html__( 'Import Data', 'theme-test-data' ) . '</a>',
		);

		$input = array_merge( $input, $links );

		return $input;
	}
}

if ( is_admin() ) {
	$ttd_settings = new TTDSettings();
}
