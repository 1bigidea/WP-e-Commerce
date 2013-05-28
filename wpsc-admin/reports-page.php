<?php
/**
 * WP e-Commerce Reports Page API.
 *
 * Third-party plugin / theme developers can add their own tabs to WPEC store reports page.
 *
 * Let's say you want to create a tab for your plugin called "My Sales Report", for example.
 * You first need to register the tab ID and title like this:
 *
 * <code>
 * function my_plugin_reports_tabs() {
 *  $reports_page = new WPEC_Reports_Page::get_instance();
 * 	$reports_page->register_tab( 'my_sales_report', 'My Sales Report' );
 *  $reports_page->register_request(); (used if your tab accepts filter/parameters)
 * }
 * add_action( 'wpsc_register_reports_tabs', 'my_plugin_reports_tabs');
 * </code>
 *
 * Note that you need to hook into 'wpsc_register_reports_tabs' to do this.
 *
 * The next step is to create a class for your tab which inherits from the base 'WPSC_Reports_Tab'.
 * The name of the class needs to follow this convention: all the words have to be capitalized and
 * separated with an underscore, and prefixed with 'WPSC_Reports_Tab_'.
 *
 * In our example, because we registered our tab ID as 'my_sales_report', the class name should
 * be 'WPSC_Reports_Tab_My_Sales_Report'.
 *
 * <code>
 * class WPSC_Reports_Tab_My_Sales_Report extends WPSC_Reports_Tab
 * {
 * 	public function display() {
 * 		echo '<h3>My Sales Reports</h3>';
 * 		// output your tab content here
 * 	}
 * }
 * </code>
 *
 * All tab has to implement a method `display()` which outputs the HTML content for the tab.
 * You don't need to output the <form> element because it will be done for you.
 *
 * When outputting your form fields for the tab, name the fields 'wpsc_options[$your_option_name]'
 * so that they will automatically get saved to the database when the user submits the form. E.g.:
 *
 * <code>
 * <input type="text" value="something" name="wpsc_options[some_option]" />
 * </code>
 *
 * If you need to handle the form submission yourself, create a method in your tab class called
 * 'callback_submit_request()'. Then process your submitted fields there.
 *
 * <code>
 * class WPSC_Reports_Tab_Recommendation_System extends WPSC_Reports_Tab {
 * 	// ...
 * 	public function callback_submit_request() {
 * 		if ( isset( $_POST['my_option'] ) )
 * 			update_option( 'my_option', $_POST['my_option'] );
 * 	}
 * 	// ...
 * }
 * </code>
 *
 * @package wp-e-commerce
 * @subpackage reports-api
 */

/**
 * Abstract class for setting tabs
 *
 * @abstract
 * @since 3.8.8
 * @package wp-e-commerce
 * @subpackage reports-api
 */
abstract class WPSC_Reports_Tab {
	/**
	 * Display the content of the tab. This function has to be overridden.
	 *
	 * @since 3.8.8
	 * @abstract
	 * @access public
	 */
	abstract public function display();

	/**
	 * Whether to display the update message when the options are submitted.
	 *
	 * @since 3.8.8.1
	 * @access private
	 */
	private $is_update_message_displayed = true;

	/**
	 * Whether to display the "Save Changes" button.
	 *
	 * @since 3.8.8.1
	 * @access private
	 */
	private $is_submit_button_displayed= true;

	/**
	 * Constructor
	 *
	 * @since 3.8.8
	 * @access public
	 */
	public function __construct() {}

	/**
	 * Make sure the update message will be displayed
	 *
	 * @since 3.8.8.1
	 * @access protected
	 */
	protected function display_update_message() {
		$this->is_update_message_displayed = true;
	}

	/**
	 * Make sure the update message will not be displayed
	 *
	 * @since 3.8.8.1
	 * @access protected
	 */
	protected function hide_update_message() {
		$this->is_update_message_displayed = false;
	}

	/**
	 * Query whether the update message is to be displayed or not.
	 *
	 * @since 3.8.8.1
	 * @access public
	 */
	public function is_update_message_displayed() {
		return $this->is_update_message_displayed;
	}

	/**
	 * Hide the default "Save Changes" button
	 *
	 * @since  3.8.8.1
	 * @access protected
	 */
	protected function hide_submit_button() {
		$this->is_submit_button_displayed = false;
	}

	/**
	 * Show the default "Save Changes" button
	 *
	 * @since 3.8.8.1
	 * @access protected
	 */
	protected function display_submit_button() {
		$this->is_submit_button_displayed = true;
	}

	/**
	 * Return whether the default "Save Changes" button is to be displayed.
	 *
	 * @since 3.8.8.1
	 * @access public
	 */
	public function is_submit_button_displayed() {
		return $this->is_submit_button_displayed;
	}
}

/**
 * Reports Page class. Singleton pattern.
 *
 * @since 3.8.8
 * @package wp-e-commerce
 * @subpackage reports-api
 * @final
 */
final class WPSC_Reports_Page {
	/**
	 * @staticvar object The active object instance
	 * @since 3.8.8
	 * @access private
	 */
	private static $instance;

	/**
	 * @staticvar array An array of default tabs containing pairs of id => title
	 * @since 3.8.8
	 * @access private
	 */
	private static $default_tabs;

	/**
	 * Initialize default tabs and add necessary action hooks.
	 *
	 * @since 3.8.8
	 *
	 * @uses add_action() Attaches to wpsc_register_reports_tabs hook
	 * @uses add_action() Attaches to wpsc_load_reports_tab_class hook
	 *
	 * @see wpsc_load_reports_page()
	 *
	 * @access public
	 * @static
	 */
	public static function init() {
		self::$default_tabs = array(
			'purchase_log' => _x( 'Sales Log', 'List of all Sales Transactions', 'wpsc' ),
			'purchase_log_export' => _x('Export', 'CSV Export of Sales Log')
		);

		add_action( 'wpsc_register_reports_tabs' , array( 'WPSC_Reports_Page', 'register_default_tabs'  ), 1 );
		add_action( 'wpsc_load_reports_tab_class', array( 'WPSC_Reports_Page', 'load_default_tab_class' ), 1 );
	}

	/**
	 * Get active object instance
	 *
	 * @since 3.8.8
	 *
	 * @access public
	 * @static
	 * @return object
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new WPSC_Reports_Page();
		}

		return self::$instance;
	}

	/**
	 * Automatically load tab classes inside wpsc-admin/includes/reports-tabs.
	 *
	 * @since 3.8.8
	 *
	 * @see WPSC_Reports_Page::init()
	 *
	 * @uses WPSC_Reports_Page::get_current_tab_id() Gets current tab ID
	 *
	 * @access public
	 * @param  object $page_instance The WPSC_Reports_Page instance
	 * @static
	 */
	public static function load_default_tab_class( $page_instance ) {
		$current_tab_id = $page_instance->get_current_tab_id();
		if ( array_key_exists( $current_tab_id, self::$default_tabs ) ) {
			require_once( 'includes/reports-tabs/' . $current_tab_id . '.php' );
		}
	}

	/**
	 * Register the default tabs' ids and titles.
	 *
	 * @since 3.8.8
	 *
	 * @see WPSC_Reports_Page::init()
	 *
	 * @uses WPSC_Reports_Page::register_tab() Registers default tabs' idds and titles.
	 *
	 * @access public
	 * @param  object $page_instance The WPSC_Reports_Page instance
	 * @static
	 */
	public static function register_default_tabs( $page_instance ) {
		foreach ( self::$default_tabs as $id => $title ) {
			$page_instance->register_tab( $id, $title );
		}
	}

	/**
	 * Current tab ID
	 * @since 3.8.8
	 * @access private
	 * @var string
	 */
	private $current_tab_id;

	/**
	 * Current tab object
	 * @since 3.8.8
	 * @access private
	 * @var object
	 */
	private $current_tab;

	/**
	 * An array containing registered tabs
	 * @since 3.8.8
	 * @access private
	 * @var array
	 */
	private $tabs;

	/**
	 * Constructor
	 *
	 * @since 3.8.8
	 *
	 * @uses do_action()   Calls wpsc_register_reports_tabs hook.
	 * @uses apply_filters Calls wpsc_reports_tabs hook.
	 * @uses WPSC_Reports_Page::set_current_tab() Set current tab to the specified ID
	 *
	 * @access public
	 * @param string $tab_id Optional. If specified then the current tab will be set to this ID.
	 */
	public function __construct( $tab_id = null ) {
		do_action( 'wpsc_register_reports_tabs', $this );
		$this->tabs = apply_filters( 'wpsc_reports_tabs', $this->tabs );
		$this->set_current_tab( $tab_id );
	}

	/**
	 * Returns the current tab object
	 *
	 * @since 3.8.8
	 *
	 * @uses do_action()         Calls wpsc_load_reports_tab_class hook.
	 * @uses WPSC_Reports_Tab() constructing a new reports tab object
	 *
	 * @access public
	 * @return object WPSC_Reports_Tab object
	 */
	public function get_current_tab() {
		if ( ! $this->current_tab ) {
			do_action( 'wpsc_load_reports_tab_class', $this );
			$class_name = ucwords( str_replace( array( '-', '_' ), ' ', $this->current_tab_id ) );
			$class_name = str_replace( ' ', '_', $class_name );
			$class_name = 'WPSC_Reports_Tab_' . $class_name;
			if ( class_exists( $class_name ) ) {
				$reflection = new ReflectionClass( $class_name );
				$this->current_tab = $reflection->newInstance();
			}
		}

		return $this->current_tab;
	}

	/**
	 * Get current tab ID
	 * @since  3.8.8
	 * @access public
	 * @return string
	 */
	public function get_current_tab_id() {
		return $this->current_tab_id;
	}

	/**
	 * Set current tab to the specified tab ID.
	 *
	 * @since 3.8.8
	 *
	 * @uses check_admin_referer() Prevent CSRF
	 * @uses WPSC_Reports_Page::get_current_tab()        Initializes the current tab object.
	 * @uses WPSC_Reports_Page::save_options()           Saves the submitted options to the database.
	 * @uses WPSC_Reports_Tab::callback_submit_request() If this method exists in the tab object, it will be called after WPSC_Reports_Page::save_options().
	 *
	 * @access public
	 * @param string $tab_id Optional. The Tab ID. If this is not specified, the $_GET['tab'] variable will be used. If that variable also does not exists, the first tab will be used.
	 */
	public function set_current_tab( $tab_id = null ) {
		if ( ! $tab_id ) {
			if ( isset( $_GET['tab'] ) && array_key_exists( $_GET['tab'], $this->tabs ) )
				$this->current_tab_id = $_GET['tab'];
			else
				$this->current_tab_id = array_shift( array_keys( $this->tabs ) );
		} else {
			$this->current_tab_id = $tab_id;
		}

		$this->current_tab = $this->get_current_tab();

		if ( isset( $_REQUEST['wpsc_admin_action'] ) && ( $_REQUEST['wpsc_admin_action'] == 'submit_request' ) ) {
			check_admin_referer( 'update-options', 'wpsc-update-options' );

			$this->save_options();
			do_action( 'wpsc_save_' . $this->current_tab_id . '_reports', $this->current_tab );

			$query_args = array();
			if ( is_callable( array( $this->current_tab, 'callback_submit_request' ) ) ) {
				$additional_query_args = $this->current_tab->callback_submit_request();
				if ( ! empty( $additional_query_args ) )
					$query_args += $additional_query_args;
			}
			if ( $this->current_tab->is_update_message_displayed() ) {
				if ( ! count( get_reports_errors() ) )
					add_reports_error( 'wpsc-reports', 'reports_updated', __( 'Reports saved.' ), 'updated' );
				set_transient( 'reports_errors', get_reports_errors(), 30 );
				$query_args['reports-updated'] = true;
			}
			wp_redirect( add_query_arg( $query_args ) );
			exit;
		}
	}

	/**
	 * Register a tab's ID and title
	 *
	 * @since 3.8.8
	 *
	 * @access public
	 * @param  string $id    Tab ID.
	 * @param  string $title Tab title.
	 */
	public function register_tab( $id, $title ) {
		$this->tabs[$id] = $title;
	}

	/**
	 * Get an array containing tabs' IDs and titles
	 *
	 * @since 3.8.8
	 *
	 * @access public
	 * @return array
	 */
	public function get_tabs() {
		return $this->tabs;
	}

	/**
	 * Get the HTML class of a tab.
	 * @since 3.8.8
	 * @param  string $id Tab ID
	 * @return string
	 */
	private function tab_class( $id ) {
		$class = 'nav-tab';
		if ( $id == $this->current_tab_id )
			$class .= ' nav-tab-active';
		return $class;
	}

	/**
	 * Get the form's submit (action) url.
	 * @since 3.8.8
	 * @access private
	 * @return string
	 */
	private function submit_url() {
		$location = add_query_arg( 'tab', $this->current_tab_id );
		return $location;
	}

	/**
	 * Output HTML of tab navigation.
	 * @since 3.8.8
	 * @access public
	 * @uses esc_html Prevents xss
	 */
	public function output_tabs() {
		?>
			<h2 class="nav-tab-wrapper">
				<?php foreach ( $this->tabs as $id => $title ): ?>
					<a data-tab-id="<?php echo esc_attr( $id ); ?>" class="<?php echo $this->tab_class( $id ); ?>" href="<?php echo esc_attr( '?page=wpsc-store-reports&tab=' . $id ); ?>"><?php echo esc_html( $this->tabs[$id] ); ?></a>
				<?php endforeach ?>
			</h2>
		<?php
	}

	/**
	 * Display the current tab.
	 * @since 3.8.8
	 * @uses do_action() Calls wpsc_{$current_tab_id}_reports_page hook.
	 * @uses WPSC_Reports_Tab::display() Displays the tab.
	 * @access public
	 */
	public function display_current_tab() {
		?>
			<div id="options_<?php echo esc_attr( $this->current_tab_id ); ?>" class="tab-content">
				<?php
					if ( is_callable( array( $this->current_tab, 'display' ) ) ) {
						$this->current_tab->display();
					}
				?>

				<?php do_action( 'wpsc_' . $this->current_tab_id . '_reports_page' ); ?>
				<div class="submit">
					<input type='hidden' name='wpsc_admin_action' value='submit_request' />
					<?php wp_nonce_field( 'wpec-reports', 'wpsc-request-reports' ); ?>
					<?php if ( $this->current_tab->is_submit_button_displayed() ): ?>
						<?php submit_button( __( 'Save Changes' ) ); ?>
					<?php endif ?>
				</div>
			</div>
		<?php
	}

	/**
	 * Display the reports page.
	 * @since 3.8.8
	 * @uses esc_html_e()     Sanitize HTML
	 * @uses esc_attr()       Sanitize HTML attributes
	 * @uses wp_nonce_field() Prevent CSRF
	 * @uses WPSC_Reports_Page::output_tabs()         Display tab navigation.
	 * @uses WPSC_Reports_Page::display_current_tab() Display current tab.
	 * @access public
	 */
	public function display() {
?>
			<div id="wpsc_reports" class="wrap">
				<div id="icon_card" class="icon32"></div>
				<h2 id="wpsc-reports-page-title">
					<?php esc_html_e( 'Store Reports', 'wpsc' ); ?>
					<img src="<?php echo esc_url( admin_url( 'images/wpspin_light.gif' ) ); ?>" class="ajax-feedback" title="" alt="" />
				</h2>
				<?php $this->output_tabs(); ?>
				<div id='wpsc_options_page'>
					<form method='post' action='<?php echo esc_url( $this->submit_url() ); ?>' enctype='multipart/form-data' id='wpsc-reports-form'>
						<?php $this->display_current_tab(); ?>
					</form>
				</div>
			</div>
<?php
	}

	/**
	 * Save submitted options to the database.
	 * @since 3.8.8
	 * @uses check_admin_referer() Prevents CSRF.
	 * @uses update_option() Saves options to the database.
	 * @uses wpdb::query() Queries the database.
	 * @uses wpdb::get_col() Queries the database.
	 * @access public
	 */
	private function save_options( $selected = '' ) {
		global $wpdb, $wpsc_gateways;
		$updated = 0;

	}
}

WPSC_Reports_Page::init();
