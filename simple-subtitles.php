<?php
/*
Plugin Name: Simple Subtitles
Plugin URI: http://wordpress.org/extend/plugins/simple-subtitles
Description: Easily add a subtitle to your post, pages and custom post types
Version: 3.0.0
Text Domain: simple-subtitle
Domain Path: /languages
Author: Linchpin
Author URI: http://linchpin.agency/?utm_source=simple-subtitles&utm_medium=plugin-admin-page&utm_campaign=wp-plugin
License: GPLv2
*/
define( 'SIMPLE_SUBTITLES_VERSION', '2.1.1' );
$GLOBALS['simple_subtitles_database_version'] = get_option( 'simple_subtitles_version', '0.0' );

include( 'upgrades.php' );

if ( ! class_exists( 'Simple_Subtitles_For_WordPress' ) ) {
	/**
	 * Simple_Subtitles_For_WordPress class.
	 */
	class Simple_Subtitles_For_WordPress {

		/**
		 * Add our action hooks.
		 *
		 * @access public
		 * @return void
		 */
		function __construct() {
			register_activation_hook( __FILE__, array( $this, 'activation' ) );

			add_action( 'admin_init', array( $this, 'admin_init' ) );
			add_action( 'edit_form_after_title', array( $this, 'edit_form_after_title' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
			add_action( 'save_post', 			 array( $this, 'save_post' ), 10, 2 );
			add_action( 'init', array( $this, 'init' ) );
			add_action( 'init', array( $this, 'register_post_meta' ) );

			add_filter( 'the_simple_subtitle', 'sanitize_simple_subtitle', 10, 2 );
			add_filter( 'the_simple_subtitle', 'wptexturize' );
			add_filter( 'the_simple_subtitle', 'convert_chars' );
			add_filter( 'the_simple_subtitle', 'trim' );
		}

		/**
		 * By default, add the ability to disable simple subtitles on on all registered post types
		 *
		 * @since 1.2
		 * @access public
		 */
		function activation() {
			if ( $settings = get_option( 'simple_subtitle_settings' ) ) {
				return;
			}

			$post_types = get_post_types();

			if ( empty( $post_types ) ) {
				return;
			}

			$default_post_types = array();

			foreach ( $post_types as $post_type ) {
				$post_type_object = get_post_type_object( $post_type );

				if ( in_array( $post_type, array( 'revision', 'nav_menu_item', 'attachment' ) ) || ! $post_type_object->public ) {
					continue;
				}

				$default_post_types[] = $post_type;
			}

			if ( ! empty( $default_post_types ) ) {
				add_option( 'simple_subtitle_settings', $default_post_types );
			}
		}

		/**
		 * Add our settings fields to the writing page
		 *
		 * @since 1.2
		 * @access public
		 * @return void
		 */
		function admin_init() {
			register_setting( 'writing', 'simple_subtitle_settings', array( $this, 'sanitize_settings' ) );

			//add a section for the plugin's settings on the writing page
			add_settings_section( 'simple_subtitle_settings_section', 'Simple Subtitle', array( $this, 'settings_section_text' ), 'writing' );

			//For each post type add a settings field, excluding revisions and nav menu items
			if ( $post_types = get_post_types() ) {
				foreach ( $post_types as $post_type ) {
					$post_type_object = get_post_type_object( $post_type );

					if ( in_array( $post_type, array( 'revision', 'nav_menu_item', 'attachment' ) ) || ! $post_type_object->public ) {
						continue;
					}

					add_settings_field( 'simple_subtitle_post_types' . $post_type, $post_type_object->labels->name, array( $this,'toggle_simple_subtitle_field' ), 'writing', 'simple_subtitle_settings_section', array( 'slug' => $post_type_object->name, 'name' => $post_type_object->labels->name ) );
				}
			}
		}

		/**
		 * Display our settings section
		 *
		 * @since 1.2
		 * @access public
		 * @return void
		 */
		function settings_section_text() {
			?>
			<p><?php esc_html_e( 'Select which post types to enable the simple subtitle field.', 'simple-subtitle' ); ?></p>
			<?php
		}

		/**
		 * Display the actual settings field
		 *
		 * @since 1.2
		 * @access public
		 * @param mixed $args
		 * @return void
		 */
		function toggle_simple_subtitle_field( $args ) {
			$settings = get_option( 'simple_subtitle_settings', array() );

			if ( $post_types = get_post_types() ) { ?>
				<input type="checkbox" name="simple_subtitle_post_types[]" id="simple_subtitle_post_types_<?php esc_attr_e( $args['slug'] ); ?>" value="<?php esc_attr_e( $args['slug'] ); ?>" <?php in_array( $args['slug'], $settings ) ? checked( true ) : checked( false ); ?>/>
				<?php
			}
		}

		/**
		 * Sanitize our settings fields
		 *
		 * @since 1.2
		 * @access public
		 * @param mixed $input
		 * @return void
		 */
		function sanitize_settings( $input ) {
			$input = wp_parse_args( $_POST['simple_subtitle_post_types'], array() );

			$new_input = array();

			foreach ( $input as $pt ) {
				if ( post_type_exists( sanitize_text_field( $pt ) ) ) {
					$new_input[] = sanitize_text_field( $pt );
				}
			}

			return $new_input;
		}


		/**
		 * Register our admin scripts.
		 *
		 * @access public
		 * @return void
		 */
		function admin_enqueue_scripts() {
			global $pagenow;

			$supported_post_types = get_option( 'simple_subtitle_settings', array() );

			wp_enqueue_script( 'simple-subtitles-admin', plugins_url( 'admin.js', __FILE__ ), array( 'jquery' ), SIMPLE_SUBTITLES_VERSION );
			wp_enqueue_style( 'simple-subtitles-admin', plugins_url( 'admin.css', __FILE__ ), false, SIMPLE_SUBTITLES_VERSION );

			// Only enqueue if the post type is supported.
			if ( 'post.php' === $pagenow && isset($_GET['post']) && in_array( get_post_type( $_GET['post'] ), $supported_post_types ) ) {
				wp_enqueue_script( 'simple-subtitles-block-editor', plugins_url( 'build/index.js', __FILE__ ), [ 'wp-edit-post' ], SIMPLE_SUBTITLES_VERSION );
			}
		}

		/**
		 * Save our simple subtitles.
		 *
		 * @access public
		 * @param mixed $post_id
		 * @return void
		 */
		function save_post( $post_id, $post ) {
			//Skip revisions and autosaves
			if ( wp_is_post_revision( $post_id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
				return;
			}

			//User allowed to do this?
			if ( 'page' == $post->post_type && ! current_user_can( 'edit_page', $post_id ) ) {
				return;
			} elseif ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}

			// Secondly we need to check if the user intended to change this value.
			if ( isset( $_POST['lp_subtitle_noncename'] ) && wp_verify_nonce( $_POST['lp_subtitle_noncename'], plugin_basename( __FILE__ ) ) ) {
				if ( isset( $_POST['simple_subtitle'] ) && ! empty( $_POST['simple_subtitle'] ) ) {
					update_post_meta( $post_id, '_simple_subtitle', sanitize_simple_subtitle( $_POST['simple_subtitle'] ) );
				} else {
					delete_post_meta( $post_id, '_simple_subtitle' );
				}
			}
		}

		/**
		 * Show simple subtitle fields.
		 *
		 * @since 1.2
		 *
		 */
		function edit_form_after_title() {
			global $post;

			// Skip if using the block editor
			if ( function_exists( 'is_gutenberg_page' ) && is_gutenberg_page() ) {
				return;
			}

			// Another way to check for the block editor
			$current_screen = get_current_screen();
			if ( method_exists( $current_screen, 'is_block_editor' ) && $current_screen->is_block_editor() ) {
				return;
			}

			$settings = get_option( 'simple_subtitle_settings' );
			$default_label = __( apply_filters( 'simple_subtitle_label', 'Enter subtitle here' ), 'simple-subtitle' );

			if ( in_array( $post->post_type, $settings ) ) : ?>
				<div id="subtitlediv">
					<div id="subtitlewrap">
						<?php wp_nonce_field( plugin_basename( __FILE__ ), 'lp_subtitle_noncename' ); ?>
						<label id="subtitle-prompt-text" class="screen-reader-text" for="simple_subtitle"><?php esc_html_e( $default_label ); ?></label>
						<input type="text" name="simple_subtitle" id="simple_subtitle" size="30" value="<?php esc_attr_e( get_post_meta( $post->ID, '_simple_subtitle', true ) ); ?>" autocomplete="off">
					</div>
				</div>
			<?php endif;
		}

		/**
		 * Automatically add subtitles to the content, unless disabled by filter.
		 */
		function init() {
			if ( apply_filters( 'simple_subtitle_auto', true ) ) {
				add_filter( 'the_content', array( $this, 'the_content' ) );
			}
		}

		/**
		 * Display a post's simple subtitle, if appropriate.
		 *
		 * @param $content
		 */
		function the_content( $content ) {
			$supported_post_types = get_option( 'simple_subtitle_settings', array() );

			if ( ! in_array( get_post_type(), $supported_post_types ) ) {
				return $content;
			}

			if ( $subtitle = get_the_simple_subtitle() ) {
				$content = '<h3 class="simple-subtitle">' . sanitize_simple_subtitle( $subtitle ) . '</h3>' . $content;
			}

			return $content;
		}

		/**
		 * Registers the subtitle meta and makes it available to rest.
		 */
		function register_post_meta() {
			$supported_post_types = get_option( 'simple_subtitle_settings', array() );

			foreach( $supported_post_types as $post_type ) {
				register_meta(
					$post_type,
					'_simple_subtitle',
					array(
						'show_in_rest' => true,
						'single' => true,
						'type' => 'string',
						'auth_callback' => '__return_true',
					)
				);
			}
		}
	}
}
$simple_subtitles_for_wordpress = new Simple_Subtitles_For_WordPress();

/**
 * Sanitize the simple subtitle.
 * Allow span tags with classes and styles by default, but allow modifications through simple_subtitle_allowed_html filter.
 *
 * @access public
 * @param mixed $simple_subtitle
 * @param mixed $post_id
 * @return void
 */
function sanitize_simple_subtitle( $simple_subtitle, $post_id = 0 ) {
	$allowed_tags = wp_kses_allowed_html();
	$allowed_tags['span'] = array(
		'class' => true,
		'style' => true,
	);

	$allowed_tags = apply_filters( 'simple_subtitle_allowed_html', $allowed_tags, $post_id );

	return wp_kses( $simple_subtitle, $allowed_tags );
}

if ( ! function_exists( 'get_the_simple_subtitle' ) ) {
	/**
	 * Retrieve the post simple subtitle.
	 *
	 * @access public
	 * @param int $post_id (default: 0)
	 * @return void
	 */
	function get_the_simple_subtitle( $post_id = 0 ) {
		if ( empty( $post_id ) ) {
			$post_id = get_the_ID();
		}

		if ( ! $simple_subtitle = get_post_meta( $post_id, '_simple_subtitle', true ) ) {
			return;
		} else {
			return apply_filters( 'the_simple_subtitle', $simple_subtitle, $post_id );
		}
	}
}

if ( ! function_exists( 'the_simple_subtitle' ) ) {
	/**
	 * Prints the current post's simple subtitle.
	 *
	 * Must be used in the loop.
	 *
	 * @access public
	 * @param int $post_id (default: 0)
	 * @return void
	 */
	function the_simple_subtitle( $before = '', $after = '', $echo = true ) {
		$simple_subtitle = get_the_simple_subtitle();

		if ( empty( $simple_subtitle ) ) {
			return;
		}

		$simple_subtitle = $before . $simple_subtitle . $after;

		if ( $echo ) {
			echo $simple_subtitle;
		} else {
			return $simple_subtitle;
		}
	}
}

if ( ! function_exists( 'the_simple_subtitle_attribute' ) ) {
	/**
	 * Sanitize the current simple subtitle when retrieving or displaying.
	 *
	 * @access public
	 * @param string $args (default: '')
	 * @return void
	 */
	function the_simple_subtitle_attribute( $args = '' ) {
		$simple_subtitle = get_the_simple_subtitle();

		if ( empty( $simple_subtitle ) ) {
			return;
		}

		$defaults = array( 'before' => '', 'after' =>  '', 'echo' => true );
		$args = wp_parse_args( $args, $defaults );

		$simple_subtitle = $args['before'] . $simple_subtitle . $args['after'];
		$simple_subtitle = esc_attr( strip_tags( $simple_subtitle ) );

		if ( $args['echo'] ) {
			echo $simple_subtitle;
		} else {
			return $simple_subtitle;
		}
	}
}
