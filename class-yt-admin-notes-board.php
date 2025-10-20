<?php
/**
 * Plugin Name: YT Admin Notes Board
 * Plugin URI: https://github.com/krasenslavov/yt-admin-notes-board
 * Description: A shared notes board for admin and editor collaboration. Add team notes, task lists, and important reminders right in your WordPress dashboard.
 * Version: 1.0.1
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: Krasen Slavov
 * Author URI: https://krasenslavov.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: yt-admin-notes-board
 * Domain Path: /languages
 *
 * @package YT_Admin_Notes_Board
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Current plugin version.
 */
define( 'YT_ANB_VERSION', '1.0.0' );

/**
 * Plugin base name.
 */
define( 'YT_ANB_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Plugin directory path.
 */
define( 'YT_ANB_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Plugin directory URL.
 */
define( 'YT_ANB_URL', plugin_dir_url( __FILE__ ) );

/**
 * Main plugin class for Admin Notes Board.
 *
 * @since 1.0.0
 */
class YT_Admin_Notes_Board {

	/**
	 * Single instance of the class.
	 *
	 * @var YT_Admin_Notes_Board|null
	 */
	private static $instance = null;

	/**
	 * Plugin options.
	 *
	 * @var array
	 */
	private $options = array();

	/**
	 * Get single instance of the class.
	 *
	 * @return YT_Admin_Notes_Board
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->options = get_option( 'yt_anb_options', $this->get_default_options() );
		$this->init_hooks();
	}

	/**
	 * Get default plugin options.
	 *
	 * @return array
	 */
	private function get_default_options() {
		return array(
			'notes_content'    => '',
			'editor_type'      => 'wysiwyg',
			'allowed_roles'    => array( 'administrator', 'editor' ),
			'widget_title'     => __( 'Team Notes', 'yt-admin-notes-board' ),
			'show_last_edited' => true,
			'last_edited_by'   => '',
			'last_edited_time' => '',
		);
	}

	/**
	 * Initialize WordPress hooks.
	 *
	 * @return void
	 */
	private function init_hooks() {
		// Load plugin text domain.
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

		// Admin hooks.
		if ( is_admin() ) {
			add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widget' ) );
			add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
			add_action( 'admin_init', array( $this, 'register_settings' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
			add_filter( 'plugin_action_links_' . YT_ANB_BASENAME, array( $this, 'add_action_links' ) );

			// AJAX handlers.
			add_action( 'wp_ajax_yt_anb_save_notes', array( $this, 'ajax_save_notes' ) );
			add_action( 'wp_ajax_yt_anb_clear_notes', array( $this, 'ajax_clear_notes' ) );
		}
	}

	/**
	 * Load plugin text domain for translations.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'yt-admin-notes-board',
			false,
			dirname( YT_ANB_BASENAME ) . '/languages'
		);
	}

	/**
	 * Add dashboard widget.
	 *
	 * @return void
	 */
	public function add_dashboard_widget() {
		// Check if user has permission.
		if ( ! $this->current_user_can_edit() ) {
			return;
		}

		wp_add_dashboard_widget(
			'yt_anb_dashboard_widget',
			$this->options['widget_title'],
			array( $this, 'render_dashboard_widget' ),
			null,
			null,
			'normal',
			'high'
		);
	}

	/**
	 * Render dashboard widget content.
	 *
	 * @return void
	 */
	public function render_dashboard_widget() {
		$notes_content = $this->options['notes_content'];
		$editor_type   = $this->options['editor_type'];
		$can_edit      = $this->current_user_can_edit();

		wp_nonce_field( 'yt_anb_save_notes', 'yt_anb_nonce' );
		?>
		<div class="yt-anb-widget">
			<?php if ( $can_edit ) : ?>
				<div class="yt-anb-editor">
					<?php if ( 'wysiwyg' === $editor_type ) : ?>
						<?php
						wp_editor(
							$notes_content,
							'yt_anb_notes_content',
							array(
								'textarea_name' => 'yt_anb_notes_content',
								'textarea_rows' => 10,
								'media_buttons' => false,
								'teeny'         => true,
								'quicktags'     => true,
							)
						);
						?>
					<?php else : ?>
						<textarea
							id="yt-anb-notes-content"
							name="yt_anb_notes_content"
							rows="10"
							class="widefat yt-anb-textarea"
						><?php echo esc_textarea( $notes_content ); ?></textarea>
					<?php endif; ?>
				</div>

				<div class="yt-anb-actions">
					<button type="button" id="yt-anb-save" class="button button-primary">
						<?php esc_html_e( 'Save Notes', 'yt-admin-notes-board' ); ?>
					</button>
					<button type="button" id="yt-anb-clear" class="button">
						<?php esc_html_e( 'Clear Notes', 'yt-admin-notes-board' ); ?>
					</button>
					<span id="yt-anb-message" class="yt-anb-message"></span>
				</div>

				<?php if ( $this->options['show_last_edited'] && ! empty( $this->options['last_edited_time'] ) ) : ?>
					<div class="yt-anb-meta">
						<?php
						printf(
							/* translators: 1: User display name, 2: Time ago */
							esc_html__( 'Last edited by %1$s %2$s', 'yt-admin-notes-board' ),
							'<strong>' . esc_html( $this->options['last_edited_by'] ) . '</strong>',
							'<span class="yt-anb-time">' . esc_html( human_time_diff( strtotime( $this->options['last_edited_time'] ), current_time( 'timestamp' ) ) ) . ' ' . esc_html__( 'ago', 'yt-admin-notes-board' ) . '</span>' // phpcs:ignore
						);
						?>
					</div>
				<?php endif; ?>
			<?php else : ?>
				<div class="yt-anb-view">
					<?php if ( ! empty( $notes_content ) ) : ?>
						<?php echo wp_kses_post( wpautop( $notes_content ) ); ?>
					<?php else : ?>
						<p class="yt-anb-empty">
							<?php esc_html_e( 'No notes yet. Add your first note to get started!', 'yt-admin-notes-board' ); ?>
						</p>
					<?php endif; ?>
				</div>

				<?php if ( $this->options['show_last_edited'] && ! empty( $this->options['last_edited_time'] ) ) : ?>
					<div class="yt-anb-meta">
						<?php
						printf(
							/* translators: 1: User display name, 2: Time ago */
							esc_html__( 'Last edited by %1$s %2$s', 'yt-admin-notes-board' ),
							'<strong>' . esc_html( $this->options['last_edited_by'] ) . '</strong>',
							'<span class="yt-anb-time">' . esc_html( human_time_diff( strtotime( $this->options['last_edited_time'] ), current_time( 'timestamp' ) ) ) . ' ' . esc_html__( 'ago', 'yt-admin-notes-board' ) . '</span>' // phpcs:ignore
						);
						?>
					</div>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Add plugin admin menu.
	 *
	 * @return void
	 */
	public function add_admin_menu() {
		add_options_page(
			__( 'Admin Notes Board Settings', 'yt-admin-notes-board' ),
			__( 'Notes Board', 'yt-admin-notes-board' ),
			'manage_options',
			'yt-admin-notes-board',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register plugin settings.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			'yt_anb_options_group',
			'yt_anb_options',
			array( $this, 'sanitize_options' )
		);

		add_settings_section(
			'yt_anb_main_section',
			__( 'Widget Settings', 'yt-admin-notes-board' ),
			array( $this, 'render_section_info' ),
			'yt-admin-notes-board'
		);

		add_settings_field(
			'widget_title',
			__( 'Widget Title', 'yt-admin-notes-board' ),
			array( $this, 'render_widget_title_field' ),
			'yt-admin-notes-board',
			'yt_anb_main_section'
		);

		add_settings_field(
			'editor_type',
			__( 'Editor Type', 'yt-admin-notes-board' ),
			array( $this, 'render_editor_type_field' ),
			'yt-admin-notes-board',
			'yt_anb_main_section'
		);

		add_settings_field(
			'allowed_roles',
			__( 'Who Can Edit', 'yt-admin-notes-board' ),
			array( $this, 'render_allowed_roles_field' ),
			'yt-admin-notes-board',
			'yt_anb_main_section'
		);

		add_settings_field(
			'show_last_edited',
			__( 'Show Last Edited Info', 'yt-admin-notes-board' ),
			array( $this, 'render_show_last_edited_field' ),
			'yt-admin-notes-board',
			'yt_anb_main_section'
		);
	}

	/**
	 * Sanitize plugin options.
	 *
	 * @param array $input Raw input data.
	 * @return array Sanitized data.
	 */
	public function sanitize_options( $input ) {
		$sanitized = $this->options;

		if ( isset( $input['widget_title'] ) ) {
			$sanitized['widget_title'] = sanitize_text_field( $input['widget_title'] );
		}

		if ( isset( $input['editor_type'] ) && in_array( $input['editor_type'], array( 'wysiwyg', 'plain' ), true ) ) {
			$sanitized['editor_type'] = $input['editor_type'];
		}

		if ( isset( $input['allowed_roles'] ) && is_array( $input['allowed_roles'] ) ) {
			$sanitized['allowed_roles'] = array_map( 'sanitize_key', $input['allowed_roles'] );
		} else {
			$sanitized['allowed_roles'] = array( 'administrator' );
		}

		$sanitized['show_last_edited'] = isset( $input['show_last_edited'] ) ? (bool) $input['show_last_edited'] : false;

		return $sanitized;
	}

	/**
	 * Render settings section information.
	 *
	 * @return void
	 */
	public function render_section_info() {
		echo '<p>' . esc_html__( 'Configure the admin notes board widget displayed on the dashboard.', 'yt-admin-notes-board' ) . '</p>';
	}

	/**
	 * Render widget title field.
	 *
	 * @return void
	 */
	public function render_widget_title_field() {
		$value = isset( $this->options['widget_title'] ) ? $this->options['widget_title'] : __( 'Team Notes', 'yt-admin-notes-board' );
		?>
		<input type="text"
			name="yt_anb_options[widget_title]"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text" />
		<p class="description">
			<?php esc_html_e( 'Title shown in the dashboard widget.', 'yt-admin-notes-board' ); ?>
		</p>
		<?php
	}

	/**
	 * Render editor type field.
	 *
	 * @return void
	 */
	public function render_editor_type_field() {
		$value = isset( $this->options['editor_type'] ) ? $this->options['editor_type'] : 'wysiwyg';
		?>
		<label style="display: block; margin-bottom: 10px;">
			<input type="radio"
				name="yt_anb_options[editor_type]"
				value="wysiwyg"
				<?php checked( $value, 'wysiwyg' ); ?> />
			<?php esc_html_e( 'WYSIWYG Editor (Rich text with formatting)', 'yt-admin-notes-board' ); ?>
		</label>
		<label style="display: block;">
			<input type="radio"
				name="yt_anb_options[editor_type]"
				value="plain"
				<?php checked( $value, 'plain' ); ?> />
			<?php esc_html_e( 'Plain Text (Simple textarea)', 'yt-admin-notes-board' ); ?>
		</label>
		<?php
	}

	/**
	 * Render allowed roles field.
	 *
	 * @return void
	 */
	public function render_allowed_roles_field() {
		$selected = isset( $this->options['allowed_roles'] ) ? $this->options['allowed_roles'] : array( 'administrator', 'editor' );
		$roles    = wp_roles()->roles;

		foreach ( $roles as $role_key => $role ) {
			$checked = in_array( $role_key, $selected, true );
			?>
			<label style="display: block; margin-bottom: 5px;">
				<input type="checkbox"
					name="yt_anb_options[allowed_roles][]"
					value="<?php echo esc_attr( $role_key ); ?>"
					<?php checked( $checked, true ); ?> />
				<?php echo esc_html( $role['name'] ); ?>
			</label>
			<?php
		}
		?>
		<p class="description">
			<?php esc_html_e( 'Select which user roles can edit the notes board.', 'yt-admin-notes-board' ); ?>
		</p>
		<?php
	}

	/**
	 * Render show last edited field.
	 *
	 * @return void
	 */
	public function render_show_last_edited_field() {
		$value = isset( $this->options['show_last_edited'] ) ? $this->options['show_last_edited'] : true;
		?>
		<label>
			<input type="checkbox"
				name="yt_anb_options[show_last_edited]"
				value="1"
				<?php checked( $value, true ); ?> />
			<?php esc_html_e( 'Show who last edited the notes and when', 'yt-admin-notes-board' ); ?>
		</label>
		<?php
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_admin_scripts( $hook ) {
		if ( 'index.php' !== $hook && 'settings_page_yt-admin-notes-board' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'yt-anb-admin',
			YT_ANB_URL . 'assets/css/yt-admin-notes-board.css',
			array(),
			YT_ANB_VERSION
		);

		if ( 'index.php' === $hook ) {
			wp_enqueue_script(
				'yt-anb-admin',
				YT_ANB_URL . 'assets/js/yt-admin-notes-board.js',
				array( 'jquery' ),
				YT_ANB_VERSION,
				true
			);

			wp_localize_script(
				'yt-anb-admin',
				'ytAnbData',
				array(
					'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
					'nonce'      => wp_create_nonce( 'yt_anb_nonce' ),
					'editorType' => $this->options['editor_type'],
					'strings'    => array(
						'saving'       => __( 'Saving...', 'yt-admin-notes-board' ),
						'saved'        => __( 'Notes saved!', 'yt-admin-notes-board' ),
						'error'        => __( 'Error saving notes.', 'yt-admin-notes-board' ),
						'confirmClear' => __( 'Are you sure you want to clear all notes? This cannot be undone.', 'yt-admin-notes-board' ),
						'cleared'      => __( 'Notes cleared!', 'yt-admin-notes-board' ),
					),
				)
			);
		}
	}

	/**
	 * Check if current user can edit notes.
	 *
	 * @return bool
	 */
	private function current_user_can_edit() {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		$user          = wp_get_current_user();
		$allowed_roles = $this->options['allowed_roles'];

		foreach ( $allowed_roles as $role ) {
			if ( in_array( $role, (array) $user->roles, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * AJAX handler to save notes.
	 *
	 * @return void
	 */
	public function ajax_save_notes() {
		// check_ajax_referer( 'yt_anb_nonce', 'nonce' );

		if ( ! $this->current_user_can_edit() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'yt-admin-notes-board' ) ) );
		}

		$notes_content = isset( $_POST['notes_content'] ) ? wp_kses_post( wp_unslash( $_POST['notes_content'] ) ) : '';

		// Update notes content.
		$this->options['notes_content'] = $notes_content;

		// Update last edited info.
		$current_user                      = wp_get_current_user();
		$this->options['last_edited_by']   = $current_user->display_name;
		$this->options['last_edited_time'] = current_time( 'mysql' );

		update_option( 'yt_anb_options', $this->options );

		wp_send_json_success(
			array(
				'message'     => __( 'Notes saved successfully!', 'yt-admin-notes-board' ),
				'last_edited' => sprintf(
					/* translators: 1: User display name, 2: Time ago */
					__( 'Last edited by %1$s %2$s', 'yt-admin-notes-board' ),
					'<strong>' . esc_html( $current_user->display_name ) . '</strong>',
					'<span class="yt-anb-time">' . esc_html__( 'just now', 'yt-admin-notes-board' ) . '</span>'
				),
			)
		);
	}

	/**
	 * AJAX handler to clear notes.
	 *
	 * @return void
	 */
	public function ajax_clear_notes() {
		// check_ajax_referer( 'yt_anb_nonce', 'nonce' );

		if ( ! $this->current_user_can_edit() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'yt-admin-notes-board' ) ) );
		}

		// Clear notes content.
		$this->options['notes_content']    = '';
		$this->options['last_edited_by']   = '';
		$this->options['last_edited_time'] = '';

		update_option( 'yt_anb_options', $this->options );

		wp_send_json_success( array( 'message' => __( 'Notes cleared successfully!', 'yt-admin-notes-board' ) ) );
	}

	/**
	 * Render settings page.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'yt-admin-notes-board' ) );
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'yt_anb_options_group' );
				do_settings_sections( 'yt-admin-notes-board' );
				submit_button();
				?>
			</form>

			<div class="yt-anb-info-box">
				<h2><?php esc_html_e( 'About Admin Notes Board', 'yt-admin-notes-board' ); ?></h2>
				<p><?php esc_html_e( 'The Admin Notes Board provides a shared space for team collaboration right in your WordPress dashboard. Perfect for:', 'yt-admin-notes-board' ); ?></p>
				<ul>
					<li><?php esc_html_e( 'Task lists and to-dos', 'yt-admin-notes-board' ); ?></li>
					<li><?php esc_html_e( 'Important reminders and deadlines', 'yt-admin-notes-board' ); ?></li>
					<li><?php esc_html_e( 'Team announcements', 'yt-admin-notes-board' ); ?></li>
					<li><?php esc_html_e( 'Quick notes and ideas', 'yt-admin-notes-board' ); ?></li>
					<li><?php esc_html_e( 'Contact information', 'yt-admin-notes-board' ); ?></li>
				</ul>

				<h3><?php esc_html_e( 'Usage Tips', 'yt-admin-notes-board' ); ?></h3>
				<ul>
					<li><?php esc_html_e( 'Use the WYSIWYG editor for formatted notes with lists, bold, and links', 'yt-admin-notes-board' ); ?></li>
					<li><?php esc_html_e( 'Switch to plain text for simple, distraction-free notes', 'yt-admin-notes-board' ); ?></li>
					<li><?php esc_html_e( 'Control who can edit notes by selecting user roles', 'yt-admin-notes-board' ); ?></li>
					<li><?php esc_html_e( 'Track changes with the "last edited" info', 'yt-admin-notes-board' ); ?></li>
					<li><?php esc_html_e( 'Notes are shared across all admins/editors - everyone sees the same content', 'yt-admin-notes-board' ); ?></li>
				</ul>
			</div>

			<style>
				.yt-anb-info-box {
					background: #fff;
					border: 1px solid #ccd0d4;
					padding: 20px;
					margin-top: 20px;
					border-radius: 4px;
					box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
				}
				.yt-anb-info-box h2 {
					margin-top: 0;
					font-size: 18px;
					border-bottom: 2px solid #2271b1;
					padding-bottom: 10px;
				}
				.yt-anb-info-box h3 {
					margin-top: 20px;
					font-size: 15px;
					color: #2271b1;
				}
				.yt-anb-info-box ul {
					margin-left: 20px;
					line-height: 1.8;
				}
				.yt-anb-info-box li {
					margin-bottom: 5px;
				}
			</style>
		</div>
		<?php
	}

	/**
	 * Add plugin action links.
	 *
	 * @param array $links Existing action links.
	 * @return array Modified action links.
	 */
	public function add_action_links( $links ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'options-general.php?page=yt-admin-notes-board' ) ),
			esc_html__( 'Settings', 'yt-admin-notes-board' )
		);

		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Plugin activation hook.
	 *
	 * @return void
	 */
	public static function activate() {
		$default_options = array(
			'notes_content'    => '',
			'editor_type'      => 'wysiwyg',
			'allowed_roles'    => array( 'administrator', 'editor' ),
			'widget_title'     => __( 'Team Notes', 'yt-admin-notes-board' ),
			'show_last_edited' => true,
			'last_edited_by'   => '',
			'last_edited_time' => '',
		);

		if ( ! get_option( 'yt_anb_options' ) ) {
			add_option( 'yt_anb_options', $default_options );
		}
	}

	/**
	 * Plugin deactivation hook.
	 *
	 * @return void
	 */
	public static function deactivate() {
		// Cleanup if needed.
	}
}

/**
 * Plugin uninstall hook.
 *
 * @return void
 */
function yt_anb_uninstall() {
	delete_option( 'yt_anb_options' );
	wp_cache_flush();
}

// Register activation hook.
register_activation_hook( __FILE__, array( 'YT_Admin_Notes_Board', 'activate' ) );

// Register deactivation hook.
register_deactivation_hook( __FILE__, array( 'YT_Admin_Notes_Board', 'deactivate' ) );

// Register uninstall hook.
register_uninstall_hook( __FILE__, 'yt_anb_uninstall' );

// Initialize the plugin.
add_action( 'plugins_loaded', array( 'YT_Admin_Notes_Board', 'get_instance' ) );
