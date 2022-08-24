<?php
/**
 * Plugin Name: Theme Unit Test
 * Plugin URI: https://github.com/abstractwp/theme-test-data
 * Description: The theme unit test data for demo.
 * Author: AbstractWP
 * Author URI: https://www.abstractwp.com/
 * Tags: theme test data, unit test, test
 * Version: 1.0.0
 * Text Domain: 'theme-unit-data'
 * Domain Path: languages
 * Tested up to: 6.0.1
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
			<form method="post" action="options.php">
			<?php
				settings_fields( 'ttd-options' );
				do_settings_sections( 'ttd-settings' );
			if ( isset( $this->options['ttd_import_demo'] ) ) {
				printf( '<strong>%s at %s </strong><br />', esc_html( ucfirst( $this->options['ttd_import_demo'] ) ), esc_html( gmdate( 'm-d-Y H:i:s', $this->options['date'] ) ) );
			}
				submit_button();
			?>
			</form>
		</div>
		<?php
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
		$ttd_import_disable = 'imported' === $this->options['ttd_import_demo'] ? 'disabled' : '';
		$ttd_remove_disable = 'removed' === $this->options['ttd_import_demo'] ? 'disabled' : '';
		printf(
			'<input type="radio" id="ttd_import_demo" name="ttd-options[ttd_import_demo]" value="imported" %s />
			<label for="ttd_import_demo">Import demo</label>&nbsp;&nbsp;&nbsp;&nbsp;
			<input type="radio" id="ttd_remove_demo" name="ttd-options[ttd_import_demo]" value="removed" %s />
			<label for="ttd_remove_demo">Remove demo</label>',
			esc_html( $ttd_import_disable ),
			esc_html( $ttd_remove_disable )
		);
	}
}

if ( is_admin() ) {
	$ttd_settings = new TTDSettings();
}
