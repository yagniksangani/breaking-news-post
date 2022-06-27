<?php
/**
 * BNP_BREAKING_NEWS_POST class for post meta box & other code functions.
 *
 * @package BNP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BNP_BREAKING_NEWS_POST class for post meta box & other code functions.
 */
class BNP_BREAKING_NEWS_POST {

	/**
	 * Holds the values to be used in the fields callbacks.
	 *
	 * @var options.
	 */
	private $options;

	/**
	 * BNP_BREAKING_NEWS_POST - instance.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}


	/**
	 * BNP_BREAKING_NEWS_POST - constructor.
	 */
	public function __construct() {
		$this->hooks();     // register hooks to make the custom post type do things.
	}


	/**
	 * Add all the hook inside the this private method.
	 */
	private function hooks() {
		// Add metaboxes.
		add_action( 'add_meta_boxes', array( $this, 'bnp_post_news_metaboxes' ) );

		// Save post.
		add_action( 'save_post', array( $this, 'bnp_breaking_news_post_save_meta_fields' ) );

		// Enqueue scripts - backend.
		add_action( 'admin_enqueue_scripts', array( $this, 'bnp_bnews_enqueue_scripts_backend' ) );

		// Enqueue scripts - frontend.
		add_action( 'wp_enqueue_scripts', array( $this, 'bnp_bnews_enqueue_scripts_frontend' ) );

		// Register a settings for a plugin.
		add_action( 'admin_init', array( $this, 'bnp_settings_page_init' ) );

		// Creating an options page.
		add_action( 'admin_menu', array( $this, 'bnp_add_plugin_page' ) );

		// Add breaking news section on frontside.
		add_action( 'wp_head', array( $this, 'bnp_add_breaking_news_section_top_header' ), 1 );

		// Create a custom schedule for 1 minute.
		add_filter( 'cron_schedules', array( $this, 'bnp_custom_cron_schedule' ) );

		// Action hook to expire the breaking news on time.
		add_action( 'bnp_expire_breaking_news_cron', array( $this, 'bnp_expire_breaking_news_cron_actions' ) );

		// Schedule cron every minute to check expiration of news.
		if ( ! wp_next_scheduled( 'bnp_expire_breaking_news_cron' ) ) {
			wp_schedule_event( time(), 'every_minute', 'bnp_expire_breaking_news_cron' );
		}
	}


	/**
	 * Add metaboxes function call.
	 */
	public function bnp_post_news_metaboxes() {
		add_meta_box( 'bnp-news-metabox-id', 'Breaking News Fields', array( $this, 'bnp_breaking_news_post_meta_box' ), 'post', 'normal', 'high' );
	}


	/**
	 * Function for html content for meta fields.
	 *
	 * @param object $post post object.
	 */
	public function bnp_breaking_news_post_meta_box( $post ) {
		$html = '';

		$bnp_enable          = get_post_meta( $post->ID, 'bnp_breaking_news_enable', true );
		$bnp_custom_title    = get_post_meta( $post->ID, 'bnp_breaking_news_custom_title', true );
		$bnp_expiration_time = get_post_meta( $post->ID, 'bnp_breaking_news_expiration_time', true );

		ob_start();
		require_once BNP_PATH . 'includes/templates/bnp_post_metabox.php';
		$html = ob_get_contents();
		ob_end_clean();

		echo $html;
	}


	/**
	 * Save meta fields for post posttype.
	 *
	 * @param int $post_id post id.
	 */
	public function bnp_breaking_news_post_save_meta_fields( $post_id ) {
		// return if autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check the user's permissions.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Store custom fields values.
		update_post_meta( $post_id, 'bnp_breaking_news_enable', sanitize_text_field( $_POST['bnp_breaking_news_enable'] ) );

		$bnews_post_id = get_option( 'bnp_enabled_breaking_news_post_id' );

		if ( $_POST['bnp_breaking_news_enable'] == 'on' ) {
			// Set new breaking news post id in option value.
			update_option( 'bnp_enabled_breaking_news_post_id', $post_id );

			if ( $post_id != $bnews_post_id ) {
				// disable the previously enabled breaking news post.
				update_post_meta( $bnews_post_id, 'bnp_breaking_news_enable', '' );
			}
		}

		update_post_meta( $post_id, 'bnp_breaking_news_custom_title', sanitize_text_field( $_POST['bnp_breaking_news_custom_title'] ) );

		update_post_meta( $post_id, 'bnp_breaking_news_expiration_time', sanitize_text_field( $_POST['bnp_breaking_news_expiration_time'] ) );

	}


	/**
	 * Enqueue the scripts for backend.
	 */
	public function bnp_bnews_enqueue_scripts_backend() {
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'bnp-admin-script', BNP_URL . 'assets/js/bnp-admin-script.js', array( 'wp-color-picker' ), false, true );
		wp_enqueue_style( 'bnp-admin-style', BNP_URL . 'assets/css/bnp-admin-style.css', array(), '1.0' );
	}

	/**
	 * Enqueue the scripts for frontend.
	 */
	public function bnp_bnews_enqueue_scripts_frontend() {
		wp_enqueue_style( 'bnp-admin-style', BNP_URL . 'assets/css/bnp-admin-style.css', array(), '1.0' );
	}


	/**
	 * Add options page.
	 */
	public function bnp_add_plugin_page() {
		// This page will be under "Settings".
		add_options_page(
			'Breaking News',
			'Breaking News Settings',
			'manage_options',
			'bnp-breaking-news',
			array( $this, 'bnp_create_admin_page' )
		);
	}


	/**
	 * Options page callback.
	 */
	public function bnp_create_admin_page() {
		// Set class property.
		$this->options = get_option( 'bnp_bnews_option' );
		?>
		<div class="wrap">
			<h1>Breaking News Settings</h1>
			<form method="post" action="options.php">
			<?php
				// This prints out all hidden setting fields.
				settings_fields( 'bnp_option_group' );
				do_settings_sections( 'bnp-breaking-news' );
				submit_button();
			?>
			</form>
		</div>
		<?php
	}


	/**
	 * Register and add settings.
	 */
	public function bnp_settings_page_init() {
		register_setting(
			'bnp_option_group', // Option group.
			'bnp_bnews_option', // Option name.
			array( $this, 'sanitize_field' ) // Sanitization function.
		);

		add_settings_section(
			'setting_section_fields', // ID.
			'', // Title.
			array( $this, 'bnp_print_section_info' ), // Callback.
			'bnp-breaking-news' // Page.
		);

		add_settings_field(
			'bnp_section_title', // ID.
			'Section Title', // Title.
			array( $this, 'bnp_section_title_callback' ), // Callback.
			'bnp-breaking-news', // Page.
			'setting_section_fields' // Section.
		);

		add_settings_field(
			'bnp_background_color',
			'Background Color',
			array( $this, 'bnp_background_color_callback' ),
			'bnp-breaking-news',
			'setting_section_fields'
		);

		add_settings_field(
			'bnp_text_color',
			'Text Color',
			array( $this, 'bnp_text_color_callback' ),
			'bnp-breaking-news',
			'setting_section_fields'
		);

		add_settings_section(
			'setting_section_preview',
			'Preview',
			array( $this, 'bnp_print_section_preview' ),
			'bnp-breaking-news'
		);
	}


	/**
	 * Print the Section text.
	 */
	public function bnp_print_section_info() {
		echo '<h2>Section Settings</h2>';
	}


	/**
	 * Get the settings option array and print one of its values.
	 */
	public function bnp_section_title_callback() {
		printf(
			'<input type="text" id="bnp_section_title" name="bnp_bnews_option[bnp_section_title]" value="%s" />',
			isset( $this->options['bnp_section_title'] ) ? esc_attr( $this->options['bnp_section_title'] ) : ''
		);
	}


	/**
	 * Get the settings option array and print one of its values.
	 */
	public function bnp_background_color_callback() {
		printf(
			'<input type="text" class="bnp-bg-color-field" id="bnp_background_color" name="bnp_bnews_option[bnp_background_color]" value="%s" />',
			isset( $this->options['bnp_background_color'] ) ? esc_attr( $this->options['bnp_background_color'] ) : ''
		);
	}


	/**
	 * Get the settings option array and print one of its values.
	 */
	public function bnp_text_color_callback() {
		printf(
			'<input type="text" class="bnp-text-color-field" id="bnp_text_color" name="bnp_bnews_option[bnp_text_color]" value="%s" />',
			isset( $this->options['bnp_text_color'] ) ? esc_attr( $this->options['bnp_text_color'] ) : ''
		);
	}


	/**
	 * Preview the news section.
	 */
	public function bnp_print_section_preview() {
		$bnp_area_title       = isset( $this->options['bnp_section_title'] ) ? esc_attr( $this->options['bnp_section_title'] ) : '';
		$bnp_background_color = isset( $this->options['bnp_background_color'] ) ? esc_attr( $this->options['bnp_background_color'] ) : '';
		$bnp_text_color       = isset( $this->options['bnp_text_color'] ) ? esc_attr( $this->options['bnp_text_color'] ) : '';
		$bnp_post_title       = 'Your breaking news post title here';

		$post_args = array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'orderby'        => 'post_date',
			'order'          => 'DESC',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'     => 'bnp_breaking_news_enable',
					'value'   => 'on',
					'compare' => '=',
				),
			),
		);
		$posts     = get_posts( $post_args );

		if ( ! empty( $posts ) ) {
			foreach ( $posts as $key => $value ) {
				$custom_title = get_post_meta( $value, 'bnp_breaking_news_custom_title', true );
				if ( empty( $custom_title ) ) {
					$bnp_post_title = get_the_title( $value );
				} else {
					$bnp_post_title = $custom_title;
				}
				$bnp_post_title = '<a title="Edit News" href="' . get_edit_post_link( $value ) . '">' . $bnp_post_title . '</a>';
			}
		}

		if ( empty( $bnp_background_color ) ) {
			$bnp_background_color = '#000000'; }

		if ( empty( $bnp_text_color ) ) {
			$bnp_text_color = '#ffffff'; }

		echo '<style type="text/css">
    		.bnp_news_preview_section {
				background: ' . $bnp_background_color . ';
			}
			.bnp_news_preview_section span, .bnp_news_preview_section a{
				color: ' . $bnp_text_color . ';
			}
			</style>';

		echo '<div class="bnp_news_preview_section"><span class="bnp_area_title">' . $bnp_area_title . '</span> <span class="bnp_post_title">' . $bnp_post_title . '</span></div>';

	}


	/**
	 * Display the breaking news section on the frontside pages.
	 */
	public function bnp_add_breaking_news_section_top_header() {
		$post_args = array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'orderby'        => 'post_date',
			'order'          => 'DESC',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'     => 'bnp_breaking_news_enable',
					'value'   => 'on',
					'compare' => '=',
				),
			),
		);
		$posts     = get_posts( $post_args );

		if ( ! empty( $posts ) ) {
			foreach ( $posts as $key => $value ) {
				$custom_title = get_post_meta( $value, 'bnp_breaking_news_custom_title', true );
				if ( empty( $custom_title ) ) {
					$bnp_post_title = get_the_title( $value );
				} else {
					$bnp_post_title = $custom_title;
				}
			}

			$bnp_options = get_option( 'bnp_bnews_option' );

			$bnp_area_title       = $bnp_options['bnp_section_title'];
			$bnp_background_color = $bnp_options['bnp_background_color'];
			$bnp_text_color       = $bnp_options['bnp_text_color'];

			if ( empty( $bnp_background_color ) ) {
				$bnp_background_color = '#000000'; }

			if ( empty( $bnp_text_color ) ) {
				$bnp_text_color = '#ffffff'; }

			echo '<style type="text/css">
	    		.bnp_news_preview_section {
					background: ' . $bnp_background_color . ';
				}
				.bnp_news_preview_section span, .bnp_news_preview_section a{
					color: ' . $bnp_text_color . ';
				}
				</style>';

			echo '<div class="bnp_news_preview_section"><a title="Read News" href="' . get_permalink( $value ) . '"><span class="bnp_area_title">' . $bnp_area_title . '</span> <span class="bnp_post_title">' . $bnp_post_title . '</span></a></div>';
		}
	}


	/**
	 * Create cron timers for use by other bnp functions.
	 * timer - 1 min.
	 *
	 * @param Array $schedules schedules.
	 */
	public function bnp_custom_cron_schedule( $schedules ) {
		// if this schedule is not already defined by someone else...
		if ( ! isset( $schedules['every_minute'] ) ) {
			$schedules['every_minute'] = array(
				'interval' => 60,
				'display'  => __( 'Every minute', 'bnp' ),
			);
		}
		return $schedules;
	}


	/**
	 * Cron function code for scan breaking news post which is expired.
	 *
	 * Action Hook: bnp_expire_breaking_news_cron
	 */
	public function bnp_expire_breaking_news_cron_actions() {
		// Get post.
		$post_args = array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'orderby'        => 'post_date',
			'order'          => 'DESC',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'     => 'bnp_breaking_news_enable',
					'value'   => 'on',
					'compare' => '=',
				),
			),
		);
		$posts     = get_posts( $post_args );

		if ( ! empty( $posts ) ) {
			foreach ( $posts as $key => $value ) {
				$expiration_time = get_post_meta( $value, 'bnp_breaking_news_expiration_time', true );
				if ( ! empty( $expiration_time ) ) {
					$current_time = gmdate( 'Y-m-d H:i:s' );

					$expiration_time = strtotime( $expiration_time );
					$current_time    = strtotime( $current_time );

					if ( $current_time >= $expiration_time ) {
						// Disable the expired breaking news.
						update_post_meta( $value, 'bnp_breaking_news_enable', '' );
					}
				}
			}
		}
	}

}


/**
 * Get BNP_BREAKING_NEWS_POST running.
 */
$bnp_breaking_news_post = new BNP_BREAKING_NEWS_POST();
