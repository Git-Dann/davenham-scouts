<?php
/**
 * Plugin Name: Davenham Events & Fundraising
 * Plugin URI:  https://davenhamscouts.org.uk
 * Description: Event pages, WooCommerce ticket reporting, event profit tracking, media grouping, and a site-wide fundraising progress banner.
 * Version:     1.1.3
 * Author:      Davenham Scout Group
 * Text Domain: davenham-events-fundraising
 * Requires at least: 6.0
 * Requires PHP: 7.0
 */

defined( 'ABSPATH' ) || exit;

define( 'DEF_VERSION', '1.1.3' );
define( 'DEF_FILE', __FILE__ );
define( 'DEF_DIR', plugin_dir_path( __FILE__ ) );
define( 'DEF_URL', plugin_dir_url( __FILE__ ) );

final class Davenham_Events_Fundraising {
	const OPTION_NAME = 'davenham_events_fundraising_settings';
	const POST_TYPE   = 'event';
	const NONCE_NAME  = 'davenham_event_details_nonce';
	const NONCE_ACTION = 'davenham_event_details';
	const MEDIA_GROUP_TAXONOMY = 'davenham_media_group';

	private static $product_sales_cache = array();
	private static $woo_sales_total_cache = null;

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_event_post_type' ) );
		add_action( 'init', array( __CLASS__, 'register_media_group_taxonomy' ) );
		add_action( 'init', array( __CLASS__, 'ensure_default_media_groups' ), 20 );
		add_action( 'init', array( __CLASS__, 'register_shortcodes' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'register_event_meta_boxes' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'save_event_meta' ), 10, 2 );
		add_action( 'admin_menu', array( __CLASS__, 'register_admin_menu' ) );
		add_action( 'admin_post_davenham_events_save_settings', array( __CLASS__, 'handle_save_settings' ) );
		add_action( 'admin_post_davenham_events_seed_products', array( __CLASS__, 'handle_seed_products' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_public_assets' ) );
		add_action( 'wp_body_open', array( __CLASS__, 'maybe_render_auto_banner' ), 8 );
		add_filter( 'template_include', array( __CLASS__, 'template_include' ) );
		add_filter( 'use_block_editor_for_post_type', array( __CLASS__, 'disable_block_editor_for_events' ), 10, 2 );
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( __CLASS__, 'event_columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'event_column_content' ), 10, 2 );
		add_filter( 'manage_edit-' . self::POST_TYPE . '_sortable_columns', array( __CLASS__, 'event_sortable_columns' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'sort_events_admin_query' ) );
		add_filter( 'attachment_fields_to_edit', array( __CLASS__, 'attachment_fields_to_edit' ), 10, 2 );
		add_filter( 'attachment_fields_to_save', array( __CLASS__, 'attachment_fields_to_save' ), 10, 2 );
		add_action( 'restrict_manage_posts', array( __CLASS__, 'render_media_group_filter' ) );
		add_action( 'all_admin_notices', array( __CLASS__, 'render_media_group_quick_filters' ) );
		add_action( 'parse_query', array( __CLASS__, 'filter_media_by_group' ) );
		add_filter( 'big_image_size_threshold', array( __CLASS__, 'big_image_size_threshold' ) );
		add_filter( 'wp_editor_set_quality', array( __CLASS__, 'image_quality' ), 10, 2 );

		add_action( 'woocommerce_new_order', array( __CLASS__, 'clear_sales_caches' ) );
		add_action( 'woocommerce_order_status_changed', array( __CLASS__, 'clear_sales_caches' ) );
		add_action( 'woocommerce_refund_created', array( __CLASS__, 'clear_sales_caches' ) );
	}

	public static function activate() {
		self::register_event_post_type();
		self::register_media_group_taxonomy();
		self::ensure_default_media_groups();
		flush_rewrite_rules();
	}

	public static function deactivate() {
		flush_rewrite_rules();
	}

	public static function register_event_post_type() {
		if ( ! post_type_exists( self::POST_TYPE ) ) {
			register_post_type(
				self::POST_TYPE,
				array(
					'labels' => array(
						'name'               => __( 'Events', 'davenham-events-fundraising' ),
						'singular_name'      => __( 'Event', 'davenham-events-fundraising' ),
						'add_new_item'       => __( 'Add New Event', 'davenham-events-fundraising' ),
						'edit_item'          => __( 'Edit Event', 'davenham-events-fundraising' ),
						'new_item'           => __( 'New Event', 'davenham-events-fundraising' ),
						'view_item'          => __( 'View Event', 'davenham-events-fundraising' ),
						'search_items'       => __( 'Search Events', 'davenham-events-fundraising' ),
						'not_found'          => __( 'No events found.', 'davenham-events-fundraising' ),
						'not_found_in_trash' => __( 'No events found in Trash.', 'davenham-events-fundraising' ),
					),
					'public'       => true,
					'has_archive'  => 'events',
					'rewrite'      => array( 'slug' => 'events' ),
					'menu_icon'    => 'dashicons-tickets-alt',
					'show_in_menu' => false,
					'show_in_rest' => false,
					'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail' ),
					'capability_type' => 'post',
				)
			);
		}

		$string_meta = array(
			'type'              => 'string',
			'single'            => true,
			'sanitize_callback' => 'sanitize_text_field',
			'show_in_rest'      => true,
			'auth_callback'     => array( __CLASS__, 'can_edit_posts' ),
		);

		register_post_meta( self::POST_TYPE, 'event_date', $string_meta );
		register_post_meta( self::POST_TYPE, 'event_time', $string_meta );
		register_post_meta( self::POST_TYPE, 'event_end_time', $string_meta );
		register_post_meta( self::POST_TYPE, 'event_location', $string_meta );
		register_post_meta(
			self::POST_TYPE,
			'_davenham_event_checklist',
			array(
				'type'              => 'array',
				'single'            => true,
				'sanitize_callback' => array( __CLASS__, 'sanitize_checklist_rows' ),
				'show_in_rest'      => false,
				'auth_callback'     => array( __CLASS__, 'can_edit_posts' ),
			)
		);
	}

	public static function register_media_group_taxonomy() {
		register_taxonomy(
			self::MEDIA_GROUP_TAXONOMY,
			'attachment',
			array(
				'labels' => array(
					'name'          => __( 'Media Groups', 'davenham-events-fundraising' ),
					'singular_name' => __( 'Media Group', 'davenham-events-fundraising' ),
					'search_items'  => __( 'Search Media Groups', 'davenham-events-fundraising' ),
					'all_items'     => __( 'All Media Groups', 'davenham-events-fundraising' ),
					'edit_item'     => __( 'Edit Media Group', 'davenham-events-fundraising' ),
					'update_item'   => __( 'Update Media Group', 'davenham-events-fundraising' ),
					'add_new_item'  => __( 'Add New Media Group', 'davenham-events-fundraising' ),
					'menu_name'     => __( 'Media Groups', 'davenham-events-fundraising' ),
				),
				'public'            => false,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'hierarchical'      => false,
				'rewrite'           => false,
				'capabilities'      => array(
					'manage_terms' => 'upload_files',
					'edit_terms'   => 'upload_files',
					'delete_terms' => 'upload_files',
					'assign_terms' => 'upload_files',
				),
			)
		);
	}

	public static function ensure_default_media_groups() {
		foreach ( self::default_media_group_names() as $group_name ) {
			if ( ! term_exists( $group_name, self::MEDIA_GROUP_TAXONOMY ) ) {
				wp_insert_term( $group_name, self::MEDIA_GROUP_TAXONOMY );
			}
		}
	}

	private static function default_media_group_names() {
		return array(
			'Monday Beavers',
			'Monday Scouts',
			'Tuesday Cubs',
			'Friday Cubs',
			'Events',
		);
	}

	public static function disable_block_editor_for_events( $use_block_editor, $post_type ) {
		return self::POST_TYPE === $post_type ? false : $use_block_editor;
	}

	public static function attachment_fields_to_edit( $form_fields, $post ) {
		$terms = get_terms(
			array(
				'taxonomy'   => self::MEDIA_GROUP_TAXONOMY,
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return $form_fields;
		}

		$selected = wp_get_object_terms( $post->ID, self::MEDIA_GROUP_TAXONOMY, array( 'fields' => 'ids' ) );
		$selected_id = ! is_wp_error( $selected ) && ! empty( $selected ) ? (int) $selected[0] : 0;
		$html = '<select name="attachments[' . esc_attr( (string) $post->ID ) . '][davenham_media_group]" class="widefat">';
		$html .= '<option value="0">' . esc_html__( 'Choose a group', 'davenham-events-fundraising' ) . '</option>';
		foreach ( $terms as $term ) {
			$html .= sprintf(
				'<option value="%1$d" %2$s>%3$s</option>',
				(int) $term->term_id,
				selected( $selected_id, (int) $term->term_id, false ),
				esc_html( $term->name )
			);
		}
		$html .= '</select>';

		$form_fields['davenham_media_group'] = array(
			'label' => __( 'Scout group', 'davenham-events-fundraising' ),
			'input' => 'html',
			'html'  => $html,
			'helps' => __( 'Use this to keep photo uploads organised by meeting night or Events.', 'davenham-events-fundraising' ),
		);

		return $form_fields;
	}

	public static function attachment_fields_to_save( $post, $attachment ) {
		if ( isset( $attachment['davenham_media_group'] ) ) {
			$term_id = absint( $attachment['davenham_media_group'] );
			if ( $term_id ) {
				wp_set_object_terms( (int) $post['ID'], array( $term_id ), self::MEDIA_GROUP_TAXONOMY, false );
			} else {
				wp_set_object_terms( (int) $post['ID'], array(), self::MEDIA_GROUP_TAXONOMY, false );
			}
		}

		return $post;
	}

	public static function render_media_group_filter() {
		global $typenow;

		if ( 'attachment' !== $typenow ) {
			return;
		}

		$selected = isset( $_GET[ self::MEDIA_GROUP_TAXONOMY ] ) ? sanitize_text_field( wp_unslash( $_GET[ self::MEDIA_GROUP_TAXONOMY ] ) ) : '';
		wp_dropdown_categories(
			array(
				'show_option_all' => __( 'All media groups', 'davenham-events-fundraising' ),
				'taxonomy'        => self::MEDIA_GROUP_TAXONOMY,
				'name'            => self::MEDIA_GROUP_TAXONOMY,
				'orderby'         => 'name',
				'selected'        => $selected,
				'hierarchical'    => false,
				'depth'           => 1,
				'show_count'      => false,
				'hide_empty'      => false,
				'value_field'     => 'slug',
			)
		);
	}

	public static function render_media_group_quick_filters() {
		global $pagenow;

		if ( 'upload.php' !== $pagenow || ! current_user_can( 'upload_files' ) ) {
			return;
		}

		$terms = get_terms(
			array(
				'taxonomy'   => self::MEDIA_GROUP_TAXONOMY,
				'hide_empty' => false,
				'orderby'    => 'name',
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return;
		}

		$selected = isset( $_GET[ self::MEDIA_GROUP_TAXONOMY ] ) ? sanitize_text_field( wp_unslash( $_GET[ self::MEDIA_GROUP_TAXONOMY ] ) ) : '';
		$all_url  = remove_query_arg( self::MEDIA_GROUP_TAXONOMY, admin_url( 'upload.php' ) );
		?>
		<div class="def-media-groups-panel">
			<div class="def-media-groups-panel__header">
				<div>
					<h2><?php esc_html_e( 'Media groups', 'davenham-events-fundraising' ); ?></h2>
					<p><?php esc_html_e( 'Filter uploads by section or events before using the normal Media Library below.', 'davenham-events-fundraising' ); ?></p>
				</div>
				<a class="button button-secondary" href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=' . self::MEDIA_GROUP_TAXONOMY . '&post_type=attachment' ) ); ?>"><?php esc_html_e( 'Manage groups', 'davenham-events-fundraising' ); ?></a>
			</div>
			<div class="def-media-group-grid">
				<a class="def-media-group-card <?php echo '' === $selected ? 'is-active' : ''; ?>" href="<?php echo esc_url( $all_url ); ?>">
					<strong><?php esc_html_e( 'All media', 'davenham-events-fundraising' ); ?></strong>
					<span><?php esc_html_e( 'Everything', 'davenham-events-fundraising' ); ?></span>
				</a>
				<?php foreach ( $terms as $term ) : ?>
					<?php
					$url = add_query_arg( self::MEDIA_GROUP_TAXONOMY, $term->slug, admin_url( 'upload.php' ) );
					?>
					<a class="def-media-group-card <?php echo $selected === $term->slug ? 'is-active' : ''; ?>" href="<?php echo esc_url( $url ); ?>">
						<strong><?php echo esc_html( $term->name ); ?></strong>
						<span><?php echo esc_html( sprintf( _n( '%s item', '%s items', (int) $term->count, 'davenham-events-fundraising' ), number_format_i18n( (int) $term->count ) ) ); ?></span>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	public static function filter_media_by_group( $query ) {
		global $pagenow;

		if ( ! is_admin() || 'upload.php' !== $pagenow || empty( $_GET[ self::MEDIA_GROUP_TAXONOMY ] ) ) {
			return;
		}

		$term_slug = sanitize_text_field( wp_unslash( $_GET[ self::MEDIA_GROUP_TAXONOMY ] ) );
		if ( '' === $term_slug || '0' === $term_slug ) {
			return;
		}

		$query->set(
			'tax_query',
			array(
				array(
					'taxonomy' => self::MEDIA_GROUP_TAXONOMY,
					'field'    => 'slug',
					'terms'    => $term_slug,
				),
			)
		);
	}

	public static function big_image_size_threshold( $threshold = 2560 ) {
		return 1920;
	}

	public static function image_quality( $quality, $mime_type ) {
		if ( in_array( $mime_type, array( 'image/jpeg', 'image/webp' ), true ) ) {
			return 82;
		}

		return $quality;
	}

	public static function can_edit_posts( $allowed = null, $meta_key = null, $post_id = 0, $user_id = 0, $cap = null, $caps = null ) {
		return current_user_can( 'edit_posts' );
	}

	public static function register_shortcodes() {
		add_shortcode( 'davenham_fundraising_banner', array( __CLASS__, 'fundraising_banner_shortcode' ) );
		add_shortcode( 'davenham_event_products', array( __CLASS__, 'event_products_shortcode' ) );
	}

	public static function default_settings() {
		return array(
			'enabled'             => '0',
			'auto_banner'         => '1',
			'placement_mode'      => 'all_pages',
			'banner_page_ids'     => array(),
			'title'               => '',
			'message'             => '',
			'target_amount'       => 0,
			'additional_amount'   => 0,
			'currency_symbol'     => "\xC2\xA3",
			'include_woocommerce' => '1',
			'include_events'      => '1',
			'event_count_mode'    => 'all',
			'banner_event_ids'    => array(),
			'order_statuses'      => array( 'wc-processing', 'wc-completed' ),
			'button_text'         => '',
			'button_url'          => '',
		);
	}

	public static function settings() {
		$saved = get_option( self::OPTION_NAME, array() );
		$saved = is_array( $saved ) ? $saved : array();
		$settings = wp_parse_args( $saved, self::default_settings() );

		if ( empty( $settings['order_statuses'] ) || ! is_array( $settings['order_statuses'] ) ) {
			$settings['order_statuses'] = array( 'wc-processing', 'wc-completed' );
		}

		if ( ! isset( $saved['event_count_mode'] ) && isset( $saved['include_events'] ) && '0' === (string) $saved['include_events'] ) {
			$settings['event_count_mode'] = 'none';
		}

		if ( ! in_array( $settings['event_count_mode'], array( 'all', 'selected', 'none' ), true ) ) {
			$settings['event_count_mode'] = 'all';
		}

		if ( ! in_array( $settings['placement_mode'], array( 'all_pages', 'selected_pages' ), true ) ) {
			$settings['placement_mode'] = 'all_pages';
		}

		$settings['banner_event_ids'] = self::sanitize_id_list( $settings['banner_event_ids'] ?? array() );
		$settings['banner_page_ids'] = self::sanitize_id_list( $settings['banner_page_ids'] ?? array() );

		return $settings;
	}

	public static function sanitize_settings( $input ) {
		$current = self::settings();
		$input = is_array( $input ) ? $input : array();

		$statuses = array();
		foreach ( (array) ( $input['order_statuses'] ?? $current['order_statuses'] ) as $status ) {
			$status = sanitize_key( $status );
			if ( 0 !== strpos( $status, 'wc-' ) ) {
				$status = 'wc-' . $status;
			}
			if ( in_array( $status, array( 'wc-pending', 'wc-processing', 'wc-on-hold', 'wc-completed' ), true ) ) {
				$statuses[] = $status;
			}
		}

		if ( empty( $statuses ) ) {
			$statuses = array( 'wc-processing', 'wc-completed' );
		}

		$event_count_mode = sanitize_key( $input['event_count_mode'] ?? $current['event_count_mode'] );
		if ( ! in_array( $event_count_mode, array( 'all', 'selected', 'none' ), true ) ) {
			$event_count_mode = 'all';
		}

		$placement_mode = sanitize_key( $input['placement_mode'] ?? $current['placement_mode'] );
		if ( ! in_array( $placement_mode, array( 'all_pages', 'selected_pages' ), true ) ) {
			$placement_mode = 'all_pages';
		}

		return array(
			'enabled'             => ! empty( $input['enabled'] ) ? '1' : '0',
			'auto_banner'         => '1',
			'placement_mode'      => $placement_mode,
			'banner_page_ids'     => self::sanitize_id_list( $input['banner_page_ids'] ?? array() ),
			'title'               => sanitize_text_field( $input['title'] ?? $current['title'] ),
			'message'             => sanitize_textarea_field( $input['message'] ?? $current['message'] ),
			'target_amount'       => max( 0, self::parse_amount( $input['target_amount'] ?? $current['target_amount'] ) ),
			'additional_amount'   => self::parse_amount( $input['additional_amount'] ?? $current['additional_amount'] ),
			'currency_symbol'     => sanitize_text_field( $input['currency_symbol'] ?? $current['currency_symbol'] ),
			'include_woocommerce' => ! empty( $input['include_woocommerce'] ) ? '1' : '0',
			'include_events'      => 'none' === $event_count_mode ? '0' : '1',
			'event_count_mode'    => $event_count_mode,
			'banner_event_ids'    => self::sanitize_id_list( $input['banner_event_ids'] ?? array() ),
			'order_statuses'      => array_values( array_unique( $statuses ) ),
			'button_text'         => sanitize_text_field( $input['button_text'] ?? $current['button_text'] ),
			'button_url'          => esc_url_raw( $input['button_url'] ?? $current['button_url'] ),
		);
	}

	public static function enqueue_admin_assets() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen ) {
			return;
		}

		$is_event_screen = self::POST_TYPE === $screen->post_type;
		$is_plugin_page  = false !== strpos( (string) $screen->id, 'davenham-events' );
		$is_media_page   = 'upload' === (string) $screen->id || 'attachment' === (string) $screen->post_type;

		if ( ! $is_event_screen && ! $is_plugin_page && ! $is_media_page ) {
			return;
		}

		wp_enqueue_style( 'davenham-events-fundraising', DEF_URL . 'assets/events-fundraising.css', array(), DEF_VERSION );
		wp_enqueue_script( 'davenham-events-fundraising', DEF_URL . 'assets/events-fundraising.js', array(), DEF_VERSION, true );
		wp_enqueue_style( 'davenham-events-admin', DEF_URL . 'assets/events-admin.css', array(), DEF_VERSION );
		wp_enqueue_script( 'davenham-events-admin', DEF_URL . 'assets/events-admin.js', array(), DEF_VERSION, true );
	}

	public static function enqueue_public_assets() {
		wp_enqueue_style( 'davenham-events-fundraising', DEF_URL . 'assets/events-fundraising.css', array(), DEF_VERSION );
		wp_enqueue_script( 'davenham-events-fundraising', DEF_URL . 'assets/events-fundraising.js', array(), DEF_VERSION, true );
	}

	public static function clear_sales_caches() {
		self::$product_sales_cache = array();
		self::$woo_sales_total_cache = null;
		delete_transient( 'davenham_events_woo_sales_total' );
	}

	public static function register_admin_menu() {
		add_menu_page(
			__( 'Events & Funds', 'davenham-events-fundraising' ),
			__( 'Events & Funds', 'davenham-events-fundraising' ),
			'edit_posts',
			'davenham-events-funds',
			array( __CLASS__, 'render_dashboard_page' ),
			'dashicons-tickets-alt',
			24
		);

		add_submenu_page(
			'davenham-events-funds',
			__( 'Dashboard', 'davenham-events-fundraising' ),
			__( 'Dashboard', 'davenham-events-fundraising' ),
			'edit_posts',
			'davenham-events-funds',
			array( __CLASS__, 'render_dashboard_page' )
		);

		add_submenu_page(
			'davenham-events-funds',
			__( 'Fundraising Banner', 'davenham-events-fundraising' ),
			__( 'Fundraising Banner', 'davenham-events-fundraising' ),
			'manage_options',
			'davenham-events-fundraising-settings',
			array( __CLASS__, 'render_settings_page' )
		);

		add_submenu_page(
			'davenham-events-funds',
			__( 'All Events', 'davenham-events-fundraising' ),
			__( 'All Events', 'davenham-events-fundraising' ),
			'edit_posts',
			'edit.php?post_type=' . self::POST_TYPE
		);

		add_submenu_page(
			'davenham-events-funds',
			__( 'Add New Event', 'davenham-events-fundraising' ),
			__( 'Add New Event', 'davenham-events-fundraising' ),
			'edit_posts',
			'post-new.php?post_type=' . self::POST_TYPE
		);
	}

	public static function register_event_meta_boxes() {
		add_meta_box(
			'davenham_event_details',
			__( 'Event setup and money tracking', 'davenham-events-fundraising' ),
			array( __CLASS__, 'render_event_meta_box' ),
			self::POST_TYPE,
			'normal',
			'high'
		);

		add_meta_box(
			'davenham_event_qr',
			__( 'Event QR code', 'davenham-events-fundraising' ),
			array( __CLASS__, 'render_event_qr_meta_box' ),
			self::POST_TYPE,
			'side',
			'default'
		);
	}

	public static function save_event_meta( $post_id, $post ) {
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! $post || self::POST_TYPE !== $post->post_type || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$fields = array(
			'event_date',
			'event_time',
			'event_end_time',
			'event_location',
		);

		foreach ( $fields as $field ) {
			$value = isset( $_POST[ $field ] ) ? sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) : '';
			update_post_meta( $post_id, $field, $value );
		}

		update_post_meta( $post_id, '_davenham_event_ticket_product_ids', self::sanitize_id_list( $_POST['davenham_event_ticket_product_ids'] ?? array() ) );
		update_post_meta( $post_id, '_davenham_event_donation_product_ids', self::sanitize_id_list( $_POST['davenham_event_donation_product_ids'] ?? array() ) );
		update_post_meta( $post_id, '_davenham_event_addon_product_ids', self::sanitize_id_list( $_POST['davenham_event_addon_product_ids'] ?? array() ) );
		update_post_meta( $post_id, '_davenham_event_manual_income', self::sanitize_manual_income_rows( $_POST['davenham_event_manual_income'] ?? array() ) );
		update_post_meta( $post_id, '_davenham_event_inventory', self::sanitize_inventory_rows( $_POST['davenham_event_inventory'] ?? array() ) );
		update_post_meta( $post_id, '_davenham_event_checklist', self::sanitize_checklist_rows( $_POST['davenham_event_checklist'] ?? array() ) );

		self::clear_sales_caches();
	}

	public static function render_event_meta_box( $post ) {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		$meta       = self::event_meta( $post->ID );
		$financials = self::event_financials( $post->ID );
		$products   = self::products_for_select( $meta );
		?>
		<div class="def-event-admin" data-def-tabs>
			<div class="def-event-profit-card">
				<div>
					<span class="def-eyebrow"><?php esc_html_e( 'Profit after costs', 'davenham-events-fundraising' ); ?></span>
					<strong><?php echo esc_html( self::money_plain( $financials['profit'] ) ); ?></strong>
					<p><?php echo esc_html( self::money_plain( $financials['gross_revenue'] ) ); ?> income minus <?php echo esc_html( self::money_plain( $financials['expenses'] ) ); ?> shopping and inventory costs.</p>
				</div>
				<div class="def-event-profit-card__meta">
					<span><?php echo esc_html( (string) $financials['tickets_sold'] ); ?> tickets/items sold</span>
					<span><?php echo esc_html( self::money_plain( $financials['woocommerce_revenue'] ) ); ?> from WooCommerce</span>
					<span><?php echo esc_html( self::money_plain( $financials['manual_income'] ) ); ?> manually entered</span>
				</div>
			</div>

			<div class="nav-tab-wrapper def-tabs__nav">
				<button type="button" class="nav-tab nav-tab-active" data-def-tab="sales"><?php esc_html_e( '1. Money in', 'davenham-events-fundraising' ); ?></button>
				<button type="button" class="nav-tab" data-def-tab="shopping"><?php esc_html_e( '2. Shopping costs', 'davenham-events-fundraising' ); ?></button>
				<button type="button" class="nav-tab" data-def-tab="checklist"><?php esc_html_e( '3. Checklist', 'davenham-events-fundraising' ); ?></button>
			</div>

			<div class="def-tab-panel is-active" data-def-panel="sales">
				<div class="def-admin-grid">
					<section class="def-admin-card">
						<h3><?php esc_html_e( 'Event details', 'davenham-events-fundraising' ); ?></h3>
						<div class="def-field-grid">
							<label>
								<span><?php esc_html_e( 'Date', 'davenham-events-fundraising' ); ?></span>
								<input type="date" name="event_date" value="<?php echo esc_attr( $meta['date'] ); ?>">
							</label>
							<label>
								<span><?php esc_html_e( 'Start time', 'davenham-events-fundraising' ); ?></span>
								<input type="time" name="event_time" value="<?php echo esc_attr( $meta['time'] ); ?>">
							</label>
							<label>
								<span><?php esc_html_e( 'End time', 'davenham-events-fundraising' ); ?></span>
								<input type="time" name="event_end_time" value="<?php echo esc_attr( $meta['end_time'] ); ?>">
							</label>
							<label>
								<span><?php esc_html_e( 'Location', 'davenham-events-fundraising' ); ?></span>
								<input type="text" name="event_location" value="<?php echo esc_attr( $meta['location'] ); ?>" placeholder="<?php esc_attr_e( 'Scout hut, field, venue...', 'davenham-events-fundraising' ); ?>">
							</label>
						</div>
					</section>

					<section class="def-admin-card">
						<h3><?php esc_html_e( 'Products shown on this event page', 'davenham-events-fundraising' ); ?></h3>
						<?php if ( ! self::woocommerce_available() ) : ?>
							<p class="description"><?php esc_html_e( 'WooCommerce is not active, so product sales will stay at zero until it is available.', 'davenham-events-fundraising' ); ?></p>
						<?php endif; ?>
						<p class="description"><?php esc_html_e( 'Choose the ticket, donation, and extra item products people can buy from the public event page.', 'davenham-events-fundraising' ); ?></p>
						<?php self::render_product_multiselect( 'Tickets people can buy', 'davenham_event_ticket_product_ids[]', $meta['ticket_product_ids'], $products ); ?>
						<?php self::render_product_multiselect( 'Donation buttons', 'davenham_event_donation_product_ids[]', $meta['donation_product_ids'], $products ); ?>
						<?php self::render_product_multiselect( 'Extra items for this event', 'davenham_event_addon_product_ids[]', $meta['addon_product_ids'], $products ); ?>
					</section>
				</div>

				<section class="def-admin-card">
					<h3><?php esc_html_e( 'Sales summary', 'davenham-events-fundraising' ); ?></h3>
					<?php self::render_event_sales_table( $financials ); ?>
				</section>

				<section class="def-admin-card">
					<h3><?php esc_html_e( 'Other money received', 'davenham-events-fundraising' ); ?></h3>
					<p class="description"><?php esc_html_e( 'Use this for tickets on the door, food, cash payments, card-reader totals, or anything not yet connected to WooCommerce.', 'davenham-events-fundraising' ); ?></p>
					<div class="def-repeatable" data-def-repeatable data-next-index="<?php echo esc_attr( (string) max( 1, count( $meta['manual_income'] ) ) ); ?>">
						<div class="def-repeatable__rows">
							<?php
							if ( empty( $meta['manual_income'] ) ) {
								self::render_manual_income_row( 0, array() );
							} else {
								foreach ( $meta['manual_income'] as $index => $row ) {
									self::render_manual_income_row( $index, $row );
								}
							}
							?>
						</div>
						<button type="button" class="button button-secondary" data-def-add-row="def-manual-income-template"><?php esc_html_e( 'Add manual income', 'davenham-events-fundraising' ); ?></button>
					</div>
					<template id="def-manual-income-template">
						<?php self::render_manual_income_row( '__INDEX__', array() ); ?>
					</template>
				</section>
			</div>

			<div class="def-tab-panel" data-def-panel="shopping" hidden>
				<section class="def-admin-card">
					<h3><?php esc_html_e( 'Shopping list and event costs', 'davenham-events-fundraising' ); ?></h3>
					<div class="def-section-heading">
						<p class="description"><?php esc_html_e( 'Anything bought for the event is deducted from profit.', 'davenham-events-fundraising' ); ?></p>
						<button type="button" class="button button-secondary" data-def-add-row="def-inventory-template"><?php esc_html_e( 'Add item', 'davenham-events-fundraising' ); ?></button>
					</div>
					<div class="def-compact-table def-compact-table--shopping">
						<div class="def-compact-head" aria-hidden="true">
							<span><?php esc_html_e( 'Item', 'davenham-events-fundraising' ); ?></span>
							<span><?php esc_html_e( 'Qty', 'davenham-events-fundraising' ); ?></span>
							<span><?php esc_html_e( 'Unit', 'davenham-events-fundraising' ); ?></span>
							<span><?php esc_html_e( 'Status', 'davenham-events-fundraising' ); ?></span>
							<span><?php esc_html_e( 'Supplier', 'davenham-events-fundraising' ); ?></span>
							<span><?php esc_html_e( 'Total', 'davenham-events-fundraising' ); ?></span>
							<span></span>
						</div>
					<div class="def-repeatable" data-def-repeatable data-next-index="<?php echo esc_attr( (string) max( 1, count( $meta['inventory'] ) ) ); ?>">
						<div class="def-repeatable__rows">
							<?php
							if ( empty( $meta['inventory'] ) ) {
								self::render_inventory_row( 0, array() );
							} else {
								foreach ( $meta['inventory'] as $index => $row ) {
									self::render_inventory_row( $index, $row );
								}
							}
							?>
						</div>
					</div>
					</div>
					<template id="def-inventory-template">
						<?php self::render_inventory_row( '__INDEX__', array() ); ?>
					</template>
				</section>
			</div>

			<div class="def-tab-panel" data-def-panel="checklist" hidden>
				<section class="def-admin-card">
					<h3><?php esc_html_e( 'Event checklist', 'davenham-events-fundraising' ); ?></h3>
					<div class="def-section-heading">
						<p class="description"><?php esc_html_e( 'A tight jobs list for quick updates on the day.', 'davenham-events-fundraising' ); ?></p>
						<button type="button" class="button button-secondary" data-def-add-row="def-checklist-template"><?php esc_html_e( 'Add task', 'davenham-events-fundraising' ); ?></button>
					</div>
					<div class="def-compact-table def-compact-table--checklist">
						<div class="def-compact-head" aria-hidden="true">
							<span></span>
							<span><?php esc_html_e( 'Task', 'davenham-events-fundraising' ); ?></span>
							<span><?php esc_html_e( 'Owner', 'davenham-events-fundraising' ); ?></span>
							<span><?php esc_html_e( 'Due', 'davenham-events-fundraising' ); ?></span>
							<span><?php esc_html_e( 'Status', 'davenham-events-fundraising' ); ?></span>
							<span><?php esc_html_e( 'Note', 'davenham-events-fundraising' ); ?></span>
							<span></span>
						</div>
					<div class="def-repeatable" data-def-repeatable data-next-index="<?php echo esc_attr( (string) max( 1, count( $meta['checklist'] ) ) ); ?>">
						<div class="def-repeatable__rows">
							<?php
							if ( empty( $meta['checklist'] ) ) {
								self::render_checklist_row( 0, array() );
							} else {
								foreach ( $meta['checklist'] as $index => $row ) {
									self::render_checklist_row( $index, $row );
								}
							}
							?>
						</div>
					</div>
					</div>
					<template id="def-checklist-template">
						<?php self::render_checklist_row( '__INDEX__', array() ); ?>
					</template>
				</section>
			</div>
		</div>
		<?php
	}

	public static function render_event_qr_meta_box( $post ) {
		if ( 'publish' !== $post->post_status ) {
			echo '<p>' . esc_html__( 'Publish the event to generate a QR code for its public page.', 'davenham-events-fundraising' ) . '</p>';
			return;
		}

		$permalink = get_permalink( $post );
		?>
		<div class="def-qr-admin">
			<img src="<?php echo esc_url( self::event_qr_code_url( $post->ID, 220 ) ); ?>" alt="<?php esc_attr_e( 'QR code for this event page', 'davenham-events-fundraising' ); ?>" width="220" height="220">
			<p><?php esc_html_e( 'This QR code is unique to this event and opens the public event page.', 'davenham-events-fundraising' ); ?></p>
			<p><a href="<?php echo esc_url( self::event_qr_code_url( $post->ID, 640 ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Open printable QR code', 'davenham-events-fundraising' ); ?></a></p>
			<p><code><?php echo esc_html( $permalink ); ?></code></p>
		</div>
		<?php
	}

	public static function render_dashboard_page() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'davenham-events-fundraising' ) );
		}

		$fundraising = self::fundraising_totals();
		$rollup      = self::events_rollup();
		$events      = self::event_posts();
		$settings    = self::settings();
		?>
		<div class="wrap def-dashboard">
			<h1><?php esc_html_e( 'Events & Funds', 'davenham-events-fundraising' ); ?></h1>
			<p><?php esc_html_e( 'Track event tickets, on-the-door payments, shopping costs, event profit, and the public fundraising banner from one place.', 'davenham-events-fundraising' ); ?></p>
			<?php self::render_admin_notices(); ?>

			<div class="def-dashboard-hero">
				<div>
					<span class="def-eyebrow"><?php esc_html_e( 'Total event profit', 'davenham-events-fundraising' ); ?></span>
					<strong><?php echo esc_html( self::money_plain( $rollup['profit'] ) ); ?></strong>
					<p><?php echo esc_html( self::money_plain( $rollup['gross_revenue'] ) ); ?> event income minus <?php echo esc_html( self::money_plain( $rollup['expenses'] ) ); ?> event costs.</p>
				</div>
				<div class="def-dashboard-hero__actions">
					<a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . self::POST_TYPE ) ); ?>"><?php esc_html_e( 'Add event', 'davenham-events-fundraising' ); ?></a>
					<a class="button button-secondary" href="<?php echo esc_url( admin_url( 'admin.php?page=davenham-events-fundraising-settings' ) ); ?>"><?php esc_html_e( 'Banner settings', 'davenham-events-fundraising' ); ?></a>
					<?php if ( current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' ) ) : ?>
						<a class="button button-secondary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=davenham_events_seed_products' ), 'davenham_events_seed_products' ) ); ?>"><?php esc_html_e( 'Create test products', 'davenham-events-fundraising' ); ?></a>
					<?php endif; ?>
				</div>
			</div>

			<div class="def-dashboard-grid">
				<div class="def-card">
					<span class="def-eyebrow"><?php esc_html_e( 'Fundraising banner total', 'davenham-events-fundraising' ); ?></span>
					<strong><?php echo esc_html( self::money_plain( $fundraising['raised'] ) ); ?></strong>
					<p><?php echo esc_html( number_format_i18n( $fundraising['percent'], 1 ) ); ?>% of <?php echo esc_html( self::money_plain( $fundraising['target'] ) ); ?></p>
				</div>
				<div class="def-card">
					<span class="def-eyebrow"><?php esc_html_e( 'WooCommerce sales counted', 'davenham-events-fundraising' ); ?></span>
					<strong><?php echo esc_html( self::money_plain( $fundraising['woocommerce_total'] ) ); ?></strong>
					<p><?php esc_html_e( 'Uses the order statuses selected in banner settings.', 'davenham-events-fundraising' ); ?></p>
				</div>
				<div class="def-card">
					<span class="def-eyebrow"><?php esc_html_e( 'Banner event contribution', 'davenham-events-fundraising' ); ?></span>
					<strong><?php echo esc_html( self::money_plain( $fundraising['event_adjustment'] ) ); ?></strong>
					<p><?php echo esc_html( self::banner_event_scope_label( $settings ) ); ?></p>
				</div>
				<div class="def-card">
					<span class="def-eyebrow"><?php esc_html_e( 'Additional amount', 'davenham-events-fundraising' ); ?></span>
					<strong><?php echo esc_html( self::money_plain( $fundraising['additional_amount'] ) ); ?></strong>
					<p><?php esc_html_e( 'Manual currency amount added directly to the banner total.', 'davenham-events-fundraising' ); ?></p>
				</div>
			</div>

			<div class="def-dashboard-grid def-dashboard-grid--info">
				<div class="def-admin-card">
					<h2><?php esc_html_e( 'Setup checklist', 'davenham-events-fundraising' ); ?></h2>
					<ul class="def-check-list">
						<li><?php esc_html_e( 'Create each event, then link its WooCommerce ticket, donation, and add-on products.', 'davenham-events-fundraising' ); ?></li>
						<li><?php esc_html_e( 'Add manual income for door payments, food sales, card-reader totals, or cash.', 'davenham-events-fundraising' ); ?></li>
						<li><?php esc_html_e( 'Add shopping and inventory costs so event profit stays accurate.', 'davenham-events-fundraising' ); ?></li>
						<li><?php esc_html_e( 'Choose where the banner appears and which events, if any, contribute to it.', 'davenham-events-fundraising' ); ?></li>
					</ul>
				</div>
				<div class="def-admin-card">
					<h2><?php esc_html_e( 'How banner totals work', 'davenham-events-fundraising' ); ?></h2>
					<p><?php esc_html_e( 'The banner can count all WooCommerce sales, no event figures, all event figures, or only selected events. If all WooCommerce sales are included, selected event WooCommerce sales are not added again; only manual income minus event costs is added from those events.', 'davenham-events-fundraising' ); ?></p>
					<p><strong><?php esc_html_e( 'Current event scope:', 'davenham-events-fundraising' ); ?></strong> <?php echo esc_html( self::banner_event_scope_label( $settings ) ); ?></p>
				</div>
			</div>

			<div class="def-admin-card">
				<h2><?php esc_html_e( 'Fundraising banner preview', 'davenham-events-fundraising' ); ?></h2>
				<p class="description"><?php echo esc_html( self::banner_event_scope_label( $settings ) ); ?></p>
				<?php echo self::render_banner_preview_or_notice(); ?>
			</div>

			<div class="def-admin-card">
				<h2><?php esc_html_e( 'Event performance', 'davenham-events-fundraising' ); ?></h2>
				<?php self::render_dashboard_events_table( $events, $settings ); ?>
			</div>
		</div>
		<?php
	}

	public static function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'davenham-events-fundraising' ) );
		}

		$settings = self::settings();
		$fundraising = self::fundraising_totals();
		?>
		<div class="wrap def-dashboard">
			<h1><?php esc_html_e( 'Fundraising Banner', 'davenham-events-fundraising' ); ?></h1>
			<p><?php esc_html_e( 'Set the public target, banner text, and which income sources should count towards the total.', 'davenham-events-fundraising' ); ?></p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="def-settings-form">
				<?php wp_nonce_field( 'davenham_events_save_settings' ); ?>
				<input type="hidden" name="action" value="davenham_events_save_settings">

				<div class="def-admin-grid">
					<section class="def-admin-card">
						<h2><?php esc_html_e( 'Banner display', 'davenham-events-fundraising' ); ?></h2>
						<table class="form-table" role="presentation">
							<tbody>
								<tr>
									<th scope="row"><?php esc_html_e( 'Banner enabled', 'davenham-events-fundraising' ); ?></th>
									<td>
										<label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[enabled]" value="1" <?php checked( $settings['enabled'], '1' ); ?>> <?php esc_html_e( 'Show the banner when the target total is greater than zero.', 'davenham-events-fundraising' ); ?></label>
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="def_placement_mode"><?php esc_html_e( 'Display location', 'davenham-events-fundraising' ); ?></label></th>
									<td>
										<select id="def_placement_mode" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[placement_mode]">
											<option value="all_pages" <?php selected( $settings['placement_mode'], 'all_pages' ); ?>><?php esc_html_e( 'All pages', 'davenham-events-fundraising' ); ?></option>
											<option value="selected_pages" <?php selected( $settings['placement_mode'], 'selected_pages' ); ?>><?php esc_html_e( 'Only selected pages', 'davenham-events-fundraising' ); ?></option>
										</select>
										<p class="description"><?php esc_html_e( 'Use Banner enabled as your quick on/off switch. Choose selected pages when the banner should only appear in specific places.', 'davenham-events-fundraising' ); ?></p>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php esc_html_e( 'Selected pages', 'davenham-events-fundraising' ); ?></th>
									<td>
										<?php self::render_banner_page_multiselect( $settings['banner_page_ids'] ); ?>
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="def_banner_title"><?php esc_html_e( 'Title', 'davenham-events-fundraising' ); ?></label></th>
									<td><input type="text" class="regular-text" id="def_banner_title" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[title]" value="<?php echo esc_attr( $settings['title'] ); ?>" placeholder="<?php esc_attr_e( 'Fundraising target', 'davenham-events-fundraising' ); ?>"></td>
								</tr>
								<tr>
									<th scope="row"><label for="def_banner_message"><?php esc_html_e( 'Banner text', 'davenham-events-fundraising' ); ?></label></th>
									<td><textarea class="large-text" rows="3" id="def_banner_message" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[message]" placeholder="<?php esc_attr_e( 'What are you raising money for?', 'davenham-events-fundraising' ); ?>"><?php echo esc_textarea( $settings['message'] ); ?></textarea></td>
								</tr>
								<tr>
									<th scope="row"><label for="def_button_text"><?php esc_html_e( 'Button', 'davenham-events-fundraising' ); ?></label></th>
									<td>
										<input type="text" id="def_button_text" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[button_text]" value="<?php echo esc_attr( $settings['button_text'] ); ?>" placeholder="<?php esc_attr_e( 'Button text', 'davenham-events-fundraising' ); ?>">
										<input type="url" class="regular-text" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[button_url]" value="<?php echo esc_attr( $settings['button_url'] ); ?>" placeholder="<?php esc_attr_e( 'Button URL', 'davenham-events-fundraising' ); ?>">
									</td>
								</tr>
							</tbody>
						</table>
					</section>

					<section class="def-admin-card">
						<h2><?php esc_html_e( 'Amounts counted', 'davenham-events-fundraising' ); ?></h2>
						<table class="form-table" role="presentation">
							<tbody>
								<tr>
									<th scope="row"><label for="def_target_amount"><?php esc_html_e( 'Target total', 'davenham-events-fundraising' ); ?></label></th>
									<td><input type="number" min="0" step="0.01" id="def_target_amount" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[target_amount]" value="<?php echo esc_attr( (string) $settings['target_amount'] ); ?>"></td>
								</tr>
								<tr>
									<th scope="row"><label for="def_additional_amount"><?php esc_html_e( 'Additional currency field', 'davenham-events-fundraising' ); ?></label></th>
									<td>
										<input type="number" step="0.01" id="def_additional_amount" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[additional_amount]" value="<?php echo esc_attr( (string) $settings['additional_amount'] ); ?>">
										<p class="description"><?php esc_html_e( 'Use this for offline donations, starting funds, bank transfers, or corrections.', 'davenham-events-fundraising' ); ?></p>
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="def_currency_symbol"><?php esc_html_e( 'Currency symbol', 'davenham-events-fundraising' ); ?></label></th>
									<td><input type="text" id="def_currency_symbol" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[currency_symbol]" value="<?php echo esc_attr( $settings['currency_symbol'] ); ?>" class="small-text"></td>
								</tr>
								<tr>
									<th scope="row"><?php esc_html_e( 'WooCommerce source', 'davenham-events-fundraising' ); ?></th>
									<td>
										<p><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[include_woocommerce]" value="1" <?php checked( $settings['include_woocommerce'], '1' ); ?>> <?php esc_html_e( 'All WooCommerce sales', 'davenham-events-fundraising' ); ?></label></p>
										<p class="description"><?php esc_html_e( 'Useful for general shop income, donations, and ticket/product sales across the store. Untick this when the banner should only count selected event sales, selected event manual income/costs, and the additional amount.', 'davenham-events-fundraising' ); ?></p>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php esc_html_e( 'Event contribution', 'davenham-events-fundraising' ); ?></th>
									<td>
										<p><label><input type="radio" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[event_count_mode]" value="all" <?php checked( $settings['event_count_mode'], 'all' ); ?>> <?php esc_html_e( 'Count all events', 'davenham-events-fundraising' ); ?></label></p>
										<p><label><input type="radio" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[event_count_mode]" value="selected" <?php checked( $settings['event_count_mode'], 'selected' ); ?>> <?php esc_html_e( 'Only count selected events', 'davenham-events-fundraising' ); ?></label></p>
										<p><label><input type="radio" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[event_count_mode]" value="none" <?php checked( $settings['event_count_mode'], 'none' ); ?>> <?php esc_html_e( 'Do not count event figures', 'davenham-events-fundraising' ); ?></label></p>
										<p class="description"><?php esc_html_e( 'Events can exist and sell tickets without being attributed to the banner.', 'davenham-events-fundraising' ); ?></p>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php esc_html_e( 'Selected banner events', 'davenham-events-fundraising' ); ?></th>
									<td>
										<?php self::render_banner_event_multiselect( $settings['banner_event_ids'] ); ?>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php esc_html_e( 'WooCommerce order statuses', 'davenham-events-fundraising' ); ?></th>
									<td>
										<?php foreach ( self::available_order_statuses() as $status => $label ) : ?>
											<p><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[order_statuses][]" value="<?php echo esc_attr( $status ); ?>" <?php checked( in_array( $status, $settings['order_statuses'], true ) ); ?>> <?php echo esc_html( $label ); ?></label></p>
										<?php endforeach; ?>
									</td>
								</tr>
							</tbody>
						</table>
					</section>
				</div>

				<?php submit_button( __( 'Save Banner Settings', 'davenham-events-fundraising' ) ); ?>
			</form>

			<section class="def-admin-card">
				<h2><?php esc_html_e( 'Current total preview', 'davenham-events-fundraising' ); ?></h2>
				<p><?php echo esc_html( self::money_plain( $fundraising['raised'] ) ); ?> of <?php echo esc_html( self::money_plain( $fundraising['target'] ) ); ?>. <?php echo esc_html( self::banner_event_scope_label( $settings ) ); ?></p>
				<?php echo self::render_banner_preview_or_notice(); ?>
			</section>
		</div>
		<?php
	}

	public static function handle_save_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'davenham-events-fundraising' ) );
		}

		check_admin_referer( 'davenham_events_save_settings' );

		$incoming = isset( $_POST[ self::OPTION_NAME ] ) ? wp_unslash( $_POST[ self::OPTION_NAME ] ) : array();
		$clean = self::sanitize_settings( is_array( $incoming ) ? $incoming : array() );
		update_option( self::OPTION_NAME, $clean, false );
		self::clear_sales_caches();

		wp_safe_redirect( add_query_arg( 'updated', '1', admin_url( 'admin.php?page=davenham-events-fundraising-settings' ) ) );
		exit;
	}

	public static function handle_seed_products() {
		if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'davenham-events-fundraising' ) );
		}

		check_admin_referer( 'davenham_events_seed_products' );

		$result = self::seed_test_products();
		wp_safe_redirect(
			add_query_arg(
				array(
					'def_seeded' => $result['created'],
					'def_existing' => $result['existing'],
					'def_seed_status' => $result['status'],
				),
				admin_url( 'admin.php?page=davenham-events-funds' )
			)
		);
		exit;
	}

	private static function render_admin_notices() {
		if ( empty( $_GET['def_seed_status'] ) ) {
			return;
		}

		$status = sanitize_key( wp_unslash( $_GET['def_seed_status'] ) );
		$created = absint( $_GET['def_seeded'] ?? 0 );
		$existing = absint( $_GET['def_existing'] ?? 0 );

		if ( 'missing_woocommerce' === $status ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'WooCommerce is not active, so test products could not be created.', 'davenham-events-fundraising' ) . '</p></div>';
			return;
		}

		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( sprintf( __( 'Test products ready. Created: %1$d. Already existed: %2$d.', 'davenham-events-fundraising' ), $created, $existing ) ) . '</p></div>';
	}

	public static function event_columns( $columns ) {
		$new = array();
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['event_date'] = __( 'Date', 'davenham-events-fundraising' );
				$new['event_sales'] = __( 'Sales', 'davenham-events-fundraising' );
				$new['event_profit'] = __( 'Profit', 'davenham-events-fundraising' );
			}
		}
		return $new;
	}

	public static function event_column_content( $column, $post_id ) {
		if ( 'event_date' === $column ) {
			echo esc_html( self::event_date_label( $post_id ) );
			return;
		}

		if ( 'event_sales' === $column ) {
			$financials = self::event_financials( $post_id );
			echo esc_html( (string) $financials['tickets_sold'] ) . ' / ' . esc_html( self::money_plain( $financials['gross_revenue'] ) );
			return;
		}

		if ( 'event_profit' === $column ) {
			$financials = self::event_financials( $post_id );
			echo esc_html( self::money_plain( $financials['profit'] ) );
		}
	}

	public static function event_sortable_columns( $columns ) {
		$columns['event_date'] = 'event_date';
		return $columns;
	}

	public static function sort_events_admin_query( $query ) {
		$post_type = $query->get( 'post_type' );
		$is_event_query = self::POST_TYPE === $post_type || ( is_array( $post_type ) && in_array( self::POST_TYPE, $post_type, true ) );

		if ( ! $query->is_main_query() || ( ! $is_event_query && ! $query->is_post_type_archive( self::POST_TYPE ) ) ) {
			return;
		}

		if ( is_admin() && 'event_date' === $query->get( 'orderby' ) ) {
			$query->set( 'meta_key', 'event_date' );
			$query->set( 'orderby', 'meta_value' );
			return;
		}

		if ( ! is_admin() && $query->is_post_type_archive( self::POST_TYPE ) ) {
			$query->set(
				'meta_query',
				array(
					'relation' => 'OR',
					'event_date_clause' => array(
						'key'     => 'event_date',
						'compare' => 'EXISTS',
					),
					'event_date_missing' => array(
						'key'     => 'event_date',
						'compare' => 'NOT EXISTS',
					),
				)
			);
			$query->set(
				'orderby',
				array(
					'event_date_clause' => 'ASC',
					'title'             => 'ASC',
				)
			);
			$query->set( 'order', 'ASC' );
		}
	}

	public static function maybe_render_auto_banner() {
		$settings = self::settings();
		if ( is_admin() || '1' !== $settings['enabled'] || (float) $settings['target_amount'] <= 0 ) {
			return;
		}

		if ( ! self::banner_should_show_on_current_page( $settings ) ) {
			return;
		}

		echo self::render_fundraising_banner( false );
	}

	private static function banner_should_show_on_current_page( $settings ) {
		if ( 'all_pages' === $settings['placement_mode'] ) {
			return true;
		}

		$page_ids = self::sanitize_id_list( $settings['banner_page_ids'] ?? array() );
		if ( empty( $page_ids ) ) {
			return false;
		}

		$queried_id = (int) get_queried_object_id();
		return $queried_id > 0 && in_array( $queried_id, $page_ids, true );
	}

	public static function fundraising_banner_shortcode() {
		return self::render_fundraising_banner( false );
	}

	public static function event_products_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'      => get_the_ID(),
				'section' => 'tickets',
			),
			$atts,
			'davenham_event_products'
		);

		$event_id = absint( $atts['id'] );
		if ( ! $event_id ) {
			return '';
		}

		$meta = self::event_meta( $event_id );
		$section = sanitize_key( $atts['section'] );

		if ( 'donations' === $section ) {
			return self::render_public_product_grid( __( 'Make a donation', 'davenham-events-fundraising' ), $meta['donation_product_ids'], __( 'Online donations are not available for this event yet.', 'davenham-events-fundraising' ), false );
		}

		if ( 'items' === $section || 'addons' === $section ) {
			return self::render_public_product_grid( __( 'Additional items', 'davenham-events-fundraising' ), $meta['addon_product_ids'], __( 'There are no additional items for this event yet.', 'davenham-events-fundraising' ), false );
		}

		return self::render_public_product_grid( __( 'Book tickets', 'davenham-events-fundraising' ), $meta['ticket_product_ids'], __( 'Online tickets are not available for this event yet.', 'davenham-events-fundraising' ), false );
	}

	public static function render_fundraising_banner( $echo = true ) {
		$settings = self::settings();
		if ( '1' !== $settings['enabled'] || (float) $settings['target_amount'] <= 0 ) {
			return '';
		}

		$totals = self::fundraising_totals();
		$raised = max( 0, (float) $totals['raised'] );
		$target = max( 0, (float) $totals['target'] );
		$percent = $target > 0 ? min( 100, ( $raised / $target ) * 100 ) : 0;
		$complete_class = $target > 0 && $raised >= $target ? ' def-fundraising-banner--complete' : '';
		$button_url = trim( (string) $settings['button_url'] );
		$button_text = trim( (string) $settings['button_text'] );

		ob_start();
		?>
		<section class="def-fundraising-banner<?php echo esc_attr( $complete_class ); ?>" data-def-countup data-raised="<?php echo esc_attr( (string) $raised ); ?>" data-target="<?php echo esc_attr( (string) $target ); ?>" data-symbol="<?php echo esc_attr( self::currency_symbol() ); ?>" style="--def-progress: <?php echo esc_attr( number_format( $percent, 2, '.', '' ) ); ?>%;">
			<div class="def-fundraising-banner__fill" aria-hidden="true"></div>
			<div class="wrapper def-fundraising-banner__inner">
				<div class="def-fundraising-banner__copy">
					<?php if ( ! empty( $settings['title'] ) ) : ?>
						<strong><?php echo esc_html( $settings['title'] ); ?></strong>
					<?php endif; ?>
					<?php if ( ! empty( $settings['message'] ) ) : ?>
						<span><?php echo esc_html( $settings['message'] ); ?></span>
					<?php endif; ?>
				</div>
				<div class="def-fundraising-banner__numbers">
					<span class="def-fundraising-banner__raised" data-def-raised-label><?php echo esc_html( self::money_plain( $raised ) ); ?></span>
					<span class="def-fundraising-banner__target"><?php echo esc_html( sprintf( __( 'of %s', 'davenham-events-fundraising' ), self::money_plain( $target ) ) ); ?></span>
				</div>
				<?php if ( $button_url && $button_text ) : ?>
					<a class="def-fundraising-banner__button" href="<?php echo esc_url( $button_url ); ?>"><?php echo esc_html( $button_text ); ?></a>
				<?php endif; ?>
			</div>
		</section>
		<?php
		$output = ob_get_clean();

		if ( $echo ) {
			echo $output;
		}

		return $output;
	}

	public static function template_include( $template ) {
		// Let the active theme override either template by placing a file of
		// the same name in the theme directory — gives editors an escape hatch
		// if they ever need a per-site variation.
		if ( is_singular( self::POST_TYPE ) ) {
			$theme_override = locate_template( 'single-event.php' );
			if ( $theme_override ) {
				return $theme_override;
			}
			$plugin_template = DEF_DIR . 'templates/single-event.php';
			return file_exists( $plugin_template ) ? $plugin_template : $template;
		}

		if ( is_post_type_archive( self::POST_TYPE ) ) {
			$theme_override = locate_template( 'archive-event.php' );
			if ( $theme_override ) {
				return $theme_override;
			}
			$plugin_template = DEF_DIR . 'templates/archive-event.php';
			return file_exists( $plugin_template ) ? $plugin_template : $template;
		}

		return $template;
	}

	public static function render_public_product_grid( $heading, $product_ids, $empty_message = '', $echo = true ) {
		$product_ids = self::sanitize_id_list( $product_ids );

		ob_start();
		?>
		<section class="def-event-products">
			<h2><?php echo esc_html( $heading ); ?></h2>
			<?php if ( empty( $product_ids ) || ! self::woocommerce_available() ) : ?>
				<p class="def-event-products__empty"><?php echo esc_html( $empty_message ); ?></p>
			<?php else : ?>
				<div class="def-event-product-grid">
					<?php foreach ( $product_ids as $product_id ) : ?>
						<?php self::render_public_product_card( $product_id ); ?>
					<?php endforeach; ?>
				</div>
				<?php if ( function_exists( 'wc_get_cart_url' ) && function_exists( 'wc_get_checkout_url' ) ) : ?>
					<p class="def-event-products__checkout">
						<a class="def-link-button" href="<?php echo esc_url( wc_get_cart_url() ); ?>"><?php esc_html_e( 'View basket', 'davenham-events-fundraising' ); ?></a>
						<a class="def-link-button def-link-button--primary" href="<?php echo esc_url( wc_get_checkout_url() ); ?>"><?php esc_html_e( 'Checkout', 'davenham-events-fundraising' ); ?></a>
					</p>
				<?php endif; ?>
			<?php endif; ?>
		</section>
		<?php
		$output = ob_get_clean();
		if ( $echo ) {
			echo $output;
		}
		return $output;
	}

	public static function event_meta( $event_id ) {
		return array(
			'date'                 => (string) get_post_meta( $event_id, 'event_date', true ),
			'time'                 => (string) get_post_meta( $event_id, 'event_time', true ),
			'end_time'             => (string) get_post_meta( $event_id, 'event_end_time', true ),
			'location'             => (string) get_post_meta( $event_id, 'event_location', true ),
			'ticket_product_ids'   => self::sanitize_id_list( get_post_meta( $event_id, '_davenham_event_ticket_product_ids', true ) ),
			'donation_product_ids' => self::sanitize_id_list( get_post_meta( $event_id, '_davenham_event_donation_product_ids', true ) ),
			'addon_product_ids'    => self::sanitize_id_list( get_post_meta( $event_id, '_davenham_event_addon_product_ids', true ) ),
			'manual_income'        => self::sanitize_manual_income_rows( get_post_meta( $event_id, '_davenham_event_manual_income', true ) ),
			'inventory'            => self::sanitize_inventory_rows( get_post_meta( $event_id, '_davenham_event_inventory', true ) ),
			'checklist'            => self::sanitize_checklist_rows( get_post_meta( $event_id, '_davenham_event_checklist', true ) ),
		);
	}

	public static function event_financials( $event_id ) {
		$meta = self::event_meta( $event_id );
		$product_ids = array_values(
			array_unique(
				array_merge(
					$meta['ticket_product_ids'],
					$meta['donation_product_ids'],
					$meta['addon_product_ids']
				)
			)
		);

		$product_sales = self::woocommerce_product_sales( $product_ids );
		$ticket_sales = self::sum_product_sales( $meta['ticket_product_ids'], $product_sales );
		$donation_sales = self::sum_product_sales( $meta['donation_product_ids'], $product_sales );
		$addon_sales = self::sum_product_sales( $meta['addon_product_ids'], $product_sales );

		$manual_income = 0;
		foreach ( $meta['manual_income'] as $row ) {
			$manual_income += (float) $row['amount'];
		}

		$expenses = 0;
		foreach ( $meta['inventory'] as $row ) {
			$expenses += self::inventory_row_cost( $row );
		}

		$woocommerce_revenue = $ticket_sales['revenue'] + $donation_sales['revenue'] + $addon_sales['revenue'];
		$gross_revenue = $woocommerce_revenue + $manual_income;
		$profit = $gross_revenue - $expenses;

		return apply_filters(
			'davenham_event_financials',
			array(
				'tickets_sold'        => $ticket_sales['quantity'],
				'ticket_revenue'      => $ticket_sales['revenue'],
				'donation_revenue'    => $donation_sales['revenue'],
				'addon_revenue'       => $addon_sales['revenue'],
				'woocommerce_revenue' => $woocommerce_revenue,
				'manual_income'       => $manual_income,
				'expenses'            => $expenses,
				'gross_revenue'       => $gross_revenue,
				'profit'              => $profit,
				'product_sales'       => $product_sales,
			),
			$event_id,
			$meta
		);
	}

	public static function fundraising_totals() {
		$settings = self::settings();
		$all_events_rollup = self::events_rollup();
		$banner_event_ids = self::banner_event_ids_for_settings( $settings );
		$rollup = 'none' === $settings['event_count_mode'] ? self::empty_events_rollup() : self::events_rollup( $banner_event_ids );

		$woo_total = '1' === $settings['include_woocommerce'] ? self::woocommerce_sales_total() : 0;
		$event_adjustment = 0;

		if ( 'none' !== $settings['event_count_mode'] ) {
			if ( '1' === $settings['include_woocommerce'] ) {
				$event_adjustment = $rollup['manual_income'] - $rollup['expenses'];
			} else {
				$event_adjustment = $rollup['profit'];
			}
		}

		$additional = (float) $settings['additional_amount'];
		$raised = $woo_total + $event_adjustment + $additional;
		$target = (float) $settings['target_amount'];
		$percent = $target > 0 ? min( 100, max( 0, ( $raised / $target ) * 100 ) ) : 0;

		return array(
			'raised'             => $raised,
			'target'             => $target,
			'percent'            => $percent,
			'woocommerce_total'  => $woo_total,
			'event_adjustment'   => $event_adjustment,
			'additional_amount'  => $additional,
			'events_rollup'      => $rollup,
			'all_events_rollup'  => $all_events_rollup,
			'banner_event_ids'   => $banner_event_ids,
			'event_count_mode'   => $settings['event_count_mode'],
		);
	}

	public static function events_rollup( $event_ids = null ) {
		$events = self::event_posts();
		$event_ids = null === $event_ids ? null : self::sanitize_id_list( $event_ids );
		$rollup = array(
			'tickets_sold'        => 0,
			'ticket_revenue'      => 0,
			'donation_revenue'    => 0,
			'addon_revenue'       => 0,
			'woocommerce_revenue' => 0,
			'manual_income'       => 0,
			'expenses'            => 0,
			'gross_revenue'       => 0,
			'profit'              => 0,
			'events_counted'      => 0,
		);

		foreach ( $events as $event ) {
			if ( null !== $event_ids && ! in_array( (int) $event->ID, $event_ids, true ) ) {
				continue;
			}

			$financials = self::event_financials( $event->ID );
			$rollup['events_counted']++;
			foreach ( $rollup as $key => $value ) {
				if ( 'events_counted' === $key ) {
					continue;
				}
				$rollup[ $key ] += (float) ( $financials[ $key ] ?? 0 );
			}
		}

		return $rollup;
	}

	private static function empty_events_rollup() {
		return array(
			'tickets_sold'        => 0,
			'ticket_revenue'      => 0,
			'donation_revenue'    => 0,
			'addon_revenue'       => 0,
			'woocommerce_revenue' => 0,
			'manual_income'       => 0,
			'expenses'            => 0,
			'gross_revenue'       => 0,
			'profit'              => 0,
			'events_counted'      => 0,
		);
	}

	private static function banner_event_ids_for_settings( $settings ) {
		if ( 'selected' === $settings['event_count_mode'] ) {
			return self::sanitize_id_list( $settings['banner_event_ids'] ?? array() );
		}

		if ( 'all' === $settings['event_count_mode'] ) {
			return wp_list_pluck( self::event_posts(), 'ID' );
		}

		return array();
	}

	public static function event_posts() {
		$events = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => array( 'publish', 'future', 'draft', 'private' ),
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		usort(
			$events,
			function ( $a, $b ) {
				$a_date = (string) get_post_meta( $a->ID, 'event_date', true );
				$b_date = (string) get_post_meta( $b->ID, 'event_date', true );

				if ( $a_date && $b_date && $a_date !== $b_date ) {
					return strcmp( $a_date, $b_date );
				}

				if ( $a_date && ! $b_date ) {
					return -1;
				}

				if ( ! $a_date && $b_date ) {
					return 1;
				}

				return strcasecmp( get_the_title( $a ), get_the_title( $b ) );
			}
		);

		return $events;
	}

	public static function event_date_label( $event_id ) {
		$meta = self::event_meta( $event_id );
		if ( empty( $meta['date'] ) ) {
			return __( 'Date TBC', 'davenham-events-fundraising' );
		}

		$timestamp = strtotime( $meta['date'] );
		if ( ! $timestamp ) {
			return $meta['date'];
		}

		return date_i18n( 'j F Y', $timestamp );
	}

	public static function event_time_label( $event_id ) {
		$meta = self::event_meta( $event_id );
		$start = self::format_time( $meta['time'] );
		$end = self::format_time( $meta['end_time'] );

		if ( $start && $end ) {
			return $start . ' - ' . $end;
		}

		return $start ?: '';
	}

	public static function event_qr_code_url( $event_id, $size = 240 ) {
		$size = max( 120, min( 1000, absint( $size ) ) );
		$url = get_permalink( $event_id );

		return add_query_arg(
			array(
				'size'   => $size . 'x' . $size,
				'margin' => 16,
				'data'   => $url,
			),
			'https://api.qrserver.com/v1/create-qr-code/'
		);
	}

	public static function banner_event_scope_label( $settings = null ) {
		$settings = $settings ? $settings : self::settings();
		$selected_count = count( self::sanitize_id_list( $settings['banner_event_ids'] ?? array() ) );

		if ( 'none' === $settings['event_count_mode'] ) {
			return __( 'No event figures are attributed to the banner.', 'davenham-events-fundraising' );
		}

		if ( 'selected' === $settings['event_count_mode'] ) {
			return sprintf(
				/* translators: %d: number of selected events. */
				_n( '%d selected event counts towards the banner.', '%d selected events count towards the banner.', $selected_count, 'davenham-events-fundraising' ),
				$selected_count
			);
		}

		return __( 'All events count towards the banner.', 'davenham-events-fundraising' );
	}

	public static function currency_symbol() {
		if ( function_exists( 'get_woocommerce_currency_symbol' ) ) {
			$symbol = get_woocommerce_currency_symbol();
			if ( $symbol ) {
				return $symbol;
			}
		}

		$settings = self::settings();
		return $settings['currency_symbol'];
	}

	public static function money_plain( $amount ) {
		return self::currency_symbol() . number_format_i18n( (float) $amount, 2 );
	}

	private static function available_order_statuses() {
		if ( function_exists( 'wc_get_order_statuses' ) ) {
			$statuses = wc_get_order_statuses();
			return array_intersect_key(
				$statuses,
				array(
					'wc-pending'    => true,
					'wc-processing' => true,
					'wc-on-hold'    => true,
					'wc-completed'  => true,
				)
			);
		}

		return array(
			'wc-processing' => __( 'Processing', 'davenham-events-fundraising' ),
			'wc-completed'  => __( 'Completed', 'davenham-events-fundraising' ),
			'wc-on-hold'    => __( 'On hold', 'davenham-events-fundraising' ),
			'wc-pending'    => __( 'Pending payment', 'davenham-events-fundraising' ),
		);
	}

	private static function order_statuses() {
		$settings = self::settings();
		$statuses = array();

		foreach ( (array) $settings['order_statuses'] as $status ) {
			$status = sanitize_key( $status );
			if ( 0 !== strpos( $status, 'wc-' ) ) {
				$status = 'wc-' . $status;
			}
			$statuses[] = $status;
		}

		return array_values( array_unique( $statuses ) );
	}

	private static function woocommerce_available() {
		return function_exists( 'wc_get_orders' ) && function_exists( 'wc_get_product' );
	}

	/**
	 * Cap order-history queries to the current Scout fundraising year.
	 *
	 * Scout fundraising runs on the school year (1 September → 31 August),
	 * so anything older than the most recent 1 September is irrelevant to
	 * the running fundraising total. Stops unbounded wc_get_orders() calls
	 * from getting slower as the order history grows.
	 */
	private static function order_query_date_floor() {
		$now   = current_time( 'timestamp' );
		$year  = (int) date( 'Y', $now );
		$month = (int) date( 'n', $now );
		// If we're before September, the current year started last September.
		if ( $month < 9 ) {
			$year--;
		}
		return $year . '-09-01';
	}

	private static function seed_test_products() {
		if ( ! class_exists( 'WC_Product_Simple' ) || ! function_exists( 'wc_get_product_id_by_sku' ) ) {
			return array(
				'status'   => 'missing_woocommerce',
				'created'  => 0,
				'existing' => 0,
			);
		}

		$products = array(
			array(
				'sku'         => 'DEF-TEST-TICKET-STANDARD',
				'name'        => 'Test Event Ticket - Standard',
				'price'       => '5',
				'description' => 'Reusable test ticket product for Davenham event setup.',
			),
			array(
				'sku'         => 'DEF-TEST-TICKET-CHILD',
				'name'        => 'Test Event Ticket - Child',
				'price'       => '3',
				'description' => 'Reusable child ticket product for Davenham event setup.',
			),
			array(
				'sku'         => 'DEF-TEST-TICKET-FAMILY',
				'name'        => 'Test Event Ticket - Family',
				'price'       => '15',
				'description' => 'Reusable family ticket product for Davenham event setup.',
			),
			array(
				'sku'         => 'DEF-DONATION-5',
				'name'        => 'Charity Donation - £5',
				'price'       => '5',
				'description' => 'Simple £5 charity donation for Davenham Scouts.',
			),
			array(
				'sku'         => 'DEF-DONATION-10',
				'name'        => 'Charity Donation - £10',
				'price'       => '10',
				'description' => 'Simple £10 charity donation for Davenham Scouts.',
			),
			array(
				'sku'         => 'DEF-DONATION-15',
				'name'        => 'Charity Donation - £15',
				'price'       => '15',
				'description' => 'Simple £15 charity donation for Davenham Scouts.',
			),
			array(
				'sku'         => 'DEF-DONATION-OTHER',
				'name'        => 'Charity Donation - Other Amount (£1 increments)',
				'price'       => '1',
				'description' => 'Use quantity to choose a custom donation amount in £1 increments.',
			),
		);

		$created = 0;
		$existing = 0;
		$category_id = self::ensure_product_category( 'Davenham Events & Donations' );

		foreach ( $products as $product_data ) {
			$existing_id = wc_get_product_id_by_sku( $product_data['sku'] );
			if ( $existing_id ) {
				$existing++;
				continue;
			}

			$product = new WC_Product_Simple();
			$product->set_name( $product_data['name'] );
			$product->set_sku( $product_data['sku'] );
			$product->set_regular_price( $product_data['price'] );
			$product->set_price( $product_data['price'] );
			$product->set_virtual( true );
			$product->set_catalog_visibility( 'visible' );
			$product->set_status( 'publish' );
			$product->set_description( $product_data['description'] );
			$product->set_short_description( $product_data['description'] );
			if ( $category_id ) {
				$product->set_category_ids( array( $category_id ) );
			}
			$product->save();
			$created++;
		}

		return array(
			'status'   => 'ok',
			'created'  => $created,
			'existing' => $existing,
		);
	}

	private static function ensure_product_category( $name ) {
		$term = term_exists( $name, 'product_cat' );
		if ( is_array( $term ) && ! empty( $term['term_id'] ) ) {
			return (int) $term['term_id'];
		}

		if ( $term ) {
			return (int) $term;
		}

		$created = wp_insert_term( $name, 'product_cat' );
		if ( is_wp_error( $created ) || empty( $created['term_id'] ) ) {
			return 0;
		}

		return (int) $created['term_id'];
	}

	private static function woocommerce_sales_total() {
		if ( null !== self::$woo_sales_total_cache ) {
			return self::$woo_sales_total_cache;
		}

		$cached = get_transient( 'davenham_events_woo_sales_total' );
		if ( false !== $cached ) {
			self::$woo_sales_total_cache = (float) $cached;
			return self::$woo_sales_total_cache;
		}

		if ( ! self::woocommerce_available() ) {
			self::$woo_sales_total_cache = 0;
			return 0;
		}

		$total = 0;
		$order_ids = wc_get_orders(
			array(
				'status'      => self::order_statuses(),
				'limit'       => -1,
				'return'      => 'ids',
				'date_created' => '>=' . self::order_query_date_floor(),
			)
		);

		foreach ( $order_ids as $order_id ) {
			$order = wc_get_order( $order_id );
			if ( ! $order ) {
				continue;
			}

			$order_total = (float) $order->get_total();
			if ( method_exists( $order, 'get_total_refunded' ) ) {
				$order_total -= abs( (float) $order->get_total_refunded() );
			}
			$total += max( 0, $order_total );
		}

		self::$woo_sales_total_cache = $total;
		set_transient( 'davenham_events_woo_sales_total', $total, HOUR_IN_SECONDS );

		return $total;
	}

	private static function woocommerce_product_sales( $product_ids ) {
		$product_ids = self::sanitize_id_list( $product_ids );
		if ( empty( $product_ids ) ) {
			return array();
		}

		sort( $product_ids );
		$cache_key = implode( ',', $product_ids ) . '|' . implode( ',', self::order_statuses() );
		if ( isset( self::$product_sales_cache[ $cache_key ] ) ) {
			return self::$product_sales_cache[ $cache_key ];
		}

		$sales = array();
		foreach ( $product_ids as $product_id ) {
			$sales[ $product_id ] = array(
				'quantity' => 0,
				'revenue'  => 0,
				'orders'   => array(),
			);
		}

		if ( ! self::woocommerce_available() ) {
			self::$product_sales_cache[ $cache_key ] = $sales;
			return $sales;
		}

		$product_lookup = array_fill_keys( $product_ids, true );
		$order_ids = wc_get_orders(
			array(
				'status'       => self::order_statuses(),
				'limit'        => -1,
				'return'       => 'ids',
				'date_created' => '>=' . self::order_query_date_floor(),
			)
		);

		foreach ( $order_ids as $order_id ) {
			$order = wc_get_order( $order_id );
			if ( ! $order ) {
				continue;
			}

			foreach ( $order->get_items( 'line_item' ) as $item_id => $item ) {
				$product_id = absint( $item->get_product_id() );
				$variation_id = absint( $item->get_variation_id() );
				$matched_id = 0;

				if ( $variation_id && isset( $product_lookup[ $variation_id ] ) ) {
					$matched_id = $variation_id;
				} elseif ( $product_id && isset( $product_lookup[ $product_id ] ) ) {
					$matched_id = $product_id;
				}

				if ( ! $matched_id ) {
					continue;
				}

				$quantity = (float) $item->get_quantity();
				if ( method_exists( $order, 'get_qty_refunded_for_item' ) ) {
					$quantity -= abs( (float) $order->get_qty_refunded_for_item( $item_id ) );
				}
				$quantity = max( 0, $quantity );

				$line_total = (float) $item->get_total() + (float) $item->get_total_tax();
				if ( method_exists( $order, 'get_total_refunded_for_item' ) ) {
					$line_total -= abs( (float) $order->get_total_refunded_for_item( $item_id, 'line_item' ) );
				}
				$line_total = max( 0, $line_total );

				$sales[ $matched_id ]['quantity'] += $quantity;
				$sales[ $matched_id ]['revenue'] += $line_total;
				$sales[ $matched_id ]['orders'][ $order_id ] = true;
			}
		}

		foreach ( $sales as $product_id => $row ) {
			$sales[ $product_id ]['orders'] = array_keys( $row['orders'] );
		}

		self::$product_sales_cache[ $cache_key ] = $sales;

		return $sales;
	}

	private static function sum_product_sales( $product_ids, $sales ) {
		$total = array(
			'quantity' => 0,
			'revenue'  => 0,
		);

		foreach ( self::sanitize_id_list( $product_ids ) as $product_id ) {
			if ( empty( $sales[ $product_id ] ) ) {
				continue;
			}
			$total['quantity'] += (float) $sales[ $product_id ]['quantity'];
			$total['revenue'] += (float) $sales[ $product_id ]['revenue'];
		}

		return $total;
	}

	private static function render_event_sales_table( $financials ) {
		?>
		<table class="widefat striped def-sales-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Source', 'davenham-events-fundraising' ); ?></th>
					<th><?php esc_html_e( 'Quantity', 'davenham-events-fundraising' ); ?></th>
					<th><?php esc_html_e( 'Income', 'davenham-events-fundraising' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><?php esc_html_e( 'Ticket products', 'davenham-events-fundraising' ); ?></td>
					<td><?php echo esc_html( (string) $financials['tickets_sold'] ); ?></td>
					<td><?php echo esc_html( self::money_plain( $financials['ticket_revenue'] ) ); ?></td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Donation products', 'davenham-events-fundraising' ); ?></td>
					<td>-</td>
					<td><?php echo esc_html( self::money_plain( $financials['donation_revenue'] ) ); ?></td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Additional item products', 'davenham-events-fundraising' ); ?></td>
					<td>-</td>
					<td><?php echo esc_html( self::money_plain( $financials['addon_revenue'] ) ); ?></td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Manual income', 'davenham-events-fundraising' ); ?></td>
					<td>-</td>
					<td><?php echo esc_html( self::money_plain( $financials['manual_income'] ) ); ?></td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Shopping/inventory costs', 'davenham-events-fundraising' ); ?></td>
					<td>-</td>
					<td>-<?php echo esc_html( self::money_plain( $financials['expenses'] ) ); ?></td>
				</tr>
				<tr class="def-sales-table__total">
					<td><?php esc_html_e( 'Profit', 'davenham-events-fundraising' ); ?></td>
					<td></td>
					<td><?php echo esc_html( self::money_plain( $financials['profit'] ) ); ?></td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	private static function render_dashboard_events_table( $events, $settings = null ) {
		$settings = $settings ? $settings : self::settings();

		if ( empty( $events ) ) {
			echo '<p>' . esc_html__( 'No events have been created yet.', 'davenham-events-fundraising' ) . '</p>';
			return;
		}
		?>
		<table class="widefat striped def-dashboard-events">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Event', 'davenham-events-fundraising' ); ?></th>
					<th><?php esc_html_e( 'Date', 'davenham-events-fundraising' ); ?></th>
					<th><?php esc_html_e( 'Tickets/items sold', 'davenham-events-fundraising' ); ?></th>
					<th><?php esc_html_e( 'Income', 'davenham-events-fundraising' ); ?></th>
					<th><?php esc_html_e( 'Costs', 'davenham-events-fundraising' ); ?></th>
					<th><?php esc_html_e( 'Profit', 'davenham-events-fundraising' ); ?></th>
					<th><?php esc_html_e( 'Banner', 'davenham-events-fundraising' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'davenham-events-fundraising' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $events as $event ) : ?>
					<?php $financials = self::event_financials( $event->ID ); ?>
					<tr>
						<td><strong><?php echo esc_html( get_the_title( $event ) ); ?></strong></td>
						<td><?php echo esc_html( self::event_date_label( $event->ID ) ); ?></td>
						<td><?php echo esc_html( (string) $financials['tickets_sold'] ); ?></td>
						<td><?php echo esc_html( self::money_plain( $financials['gross_revenue'] ) ); ?></td>
						<td><?php echo esc_html( self::money_plain( $financials['expenses'] ) ); ?></td>
						<td><strong><?php echo esc_html( self::money_plain( $financials['profit'] ) ); ?></strong></td>
						<td><?php echo esc_html( self::event_banner_status_label( $event->ID, $settings ) ); ?></td>
						<td>
							<a href="<?php echo esc_url( get_edit_post_link( $event->ID ) ); ?>"><?php esc_html_e( 'Edit', 'davenham-events-fundraising' ); ?></a>
							<?php if ( 'publish' === $event->post_status ) : ?>
								&nbsp;|&nbsp;<a href="<?php echo esc_url( get_permalink( $event ) ); ?>"><?php esc_html_e( 'View', 'davenham-events-fundraising' ); ?></a>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	private static function event_banner_status_label( $event_id, $settings ) {
		if ( 'none' === $settings['event_count_mode'] ) {
			return __( 'Not attributed', 'davenham-events-fundraising' );
		}

		if ( 'all' === $settings['event_count_mode'] ) {
			return __( 'Counts', 'davenham-events-fundraising' );
		}

		return in_array( (int) $event_id, self::sanitize_id_list( $settings['banner_event_ids'] ?? array() ), true )
			? __( 'Counts', 'davenham-events-fundraising' )
			: __( 'Not attributed', 'davenham-events-fundraising' );
	}

	private static function render_public_product_card( $product_id ) {
		$product = function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : null;
		if ( ! $product ) {
			return;
		}

		$button_classes = 'def-product-card__button';
		$button_classes .= ' product_type_' . sanitize_html_class( $product->get_type() );
		if ( method_exists( $product, 'supports' ) && $product->supports( 'ajax_add_to_cart' ) ) {
			$button_classes .= ' add_to_cart_button ajax_add_to_cart';
		}
		?>
		<article class="def-product-card">
			<h3><?php echo esc_html( $product->get_name() ); ?></h3>
			<?php if ( $product->get_short_description() ) : ?>
				<div class="def-product-card__description"><?php echo wp_kses_post( wpautop( $product->get_short_description() ) ); ?></div>
			<?php endif; ?>
			<div class="def-product-card__footer">
				<span class="def-product-card__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
				<?php if ( $product->is_purchasable() && $product->is_in_stock() ) : ?>
					<a
						href="<?php echo esc_url( $product->add_to_cart_url() ); ?>"
						class="<?php echo esc_attr( $button_classes ); ?>"
						data-quantity="1"
						data-product_id="<?php echo esc_attr( (string) $product->get_id() ); ?>"
						data-product_sku="<?php echo esc_attr( $product->get_sku() ); ?>"
						aria-label="<?php echo esc_attr( $product->add_to_cart_description() ); ?>"
						rel="nofollow"
					><?php echo esc_html( $product->add_to_cart_text() ); ?></a>
				<?php else : ?>
					<a class="def-product-card__button" href="<?php echo esc_url( get_permalink( $product_id ) ); ?>"><?php esc_html_e( 'View details', 'davenham-events-fundraising' ); ?></a>
				<?php endif; ?>
			</div>
		</article>
		<?php
	}

	private static function render_product_multiselect( $label, $name, $selected_ids, $products ) {
		$field_id = sanitize_key( $name . '_' . $label );
		$selected_ids = self::sanitize_id_list( $selected_ids );
		?>
		<label class="def-product-select" for="<?php echo esc_attr( $field_id ); ?>">
			<span><?php echo esc_html( $label ); ?></span>
			<select id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $name ); ?>" multiple size="8">
				<?php foreach ( $products as $product_id => $product_label ) : ?>
					<option value="<?php echo esc_attr( (string) $product_id ); ?>" <?php selected( in_array( (int) $product_id, $selected_ids, true ) ); ?>><?php echo esc_html( $product_label ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<?php
	}

	private static function render_banner_event_multiselect( $selected_ids ) {
		$events = self::event_posts();
		$selected_ids = self::sanitize_id_list( $selected_ids );

		if ( empty( $events ) ) {
			echo '<p class="description">' . esc_html__( 'Create events first, then return here to choose which ones should count towards the banner.', 'davenham-events-fundraising' ) . '</p>';
			return;
		}
		?>
		<select class="def-event-picker" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[banner_event_ids][]" multiple size="<?php echo esc_attr( (string) min( 10, max( 4, count( $events ) ) ) ); ?>">
			<?php foreach ( $events as $event ) : ?>
				<option value="<?php echo esc_attr( (string) $event->ID ); ?>" <?php selected( in_array( (int) $event->ID, $selected_ids, true ) ); ?>>
					<?php echo esc_html( self::event_option_label( $event ) ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<p class="description"><?php esc_html_e( 'Hold Command on Mac or Ctrl on Windows to select more than one event.', 'davenham-events-fundraising' ); ?></p>
		<?php
	}

	private static function render_banner_page_multiselect( $selected_ids ) {
		$pages = get_pages(
			array(
				'post_status' => array( 'publish', 'private' ),
				'sort_column' => 'menu_order,post_title',
			)
		);
		$selected_ids = self::sanitize_id_list( $selected_ids );

		if ( empty( $pages ) ) {
			echo '<p class="description">' . esc_html__( 'No pages are available to choose yet.', 'davenham-events-fundraising' ) . '</p>';
			return;
		}
		?>
		<select class="def-event-picker" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[banner_page_ids][]" multiple size="<?php echo esc_attr( (string) min( 10, max( 4, count( $pages ) ) ) ); ?>">
			<?php foreach ( $pages as $page ) : ?>
				<option value="<?php echo esc_attr( (string) $page->ID ); ?>" <?php selected( in_array( (int) $page->ID, $selected_ids, true ) ); ?>>
					<?php echo esc_html( get_the_title( $page ) . ' - ' . get_permalink( $page ) ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<p class="description"><?php esc_html_e( 'Used only when Display location is set to Only selected pages.', 'davenham-events-fundraising' ); ?></p>
		<?php
	}

	private static function event_option_label( $event ) {
		$date = self::event_date_label( $event->ID );
		$status = 'publish' === $event->post_status ? '' : ' (' . $event->post_status . ')';

		return get_the_title( $event ) . ' - ' . $date . $status;
	}

	private static function render_banner_preview_or_notice() {
		$output = self::render_fundraising_banner( false );
		if ( $output ) {
			return $output;
		}

		return '<div class="def-empty-state"><strong>' . esc_html__( 'Banner is not live yet.', 'davenham-events-fundraising' ) . '</strong><p>' . esc_html__( 'Set a target total greater than zero, add the banner title/text, then tick Banner enabled.', 'davenham-events-fundraising' ) . '</p></div>';
	}

	private static function products_for_select( $meta = array() ) {
		$products = array();

		if ( function_exists( 'wc_get_products' ) ) {
			$items = wc_get_products(
				array(
					'status'  => array( 'publish', 'private', 'draft' ),
					'limit'   => -1,
					'orderby' => 'title',
					'order'   => 'ASC',
				)
			);

			foreach ( $items as $product ) {
				$price = wp_strip_all_tags( $product->get_price_html() );
				$products[ $product->get_id() ] = '#' . $product->get_id() . ' - ' . $product->get_name() . ( $price ? ' - ' . $price : '' );
			}
		}

		$selected_ids = array();
		foreach ( array( 'ticket_product_ids', 'donation_product_ids', 'addon_product_ids' ) as $key ) {
			if ( ! empty( $meta[ $key ] ) ) {
				$selected_ids = array_merge( $selected_ids, $meta[ $key ] );
			}
		}

		foreach ( self::sanitize_id_list( $selected_ids ) as $product_id ) {
			if ( ! isset( $products[ $product_id ] ) ) {
				$products[ $product_id ] = '#' . $product_id . ' - ' . __( 'Selected product', 'davenham-events-fundraising' );
			}
		}

		return $products;
	}

	private static function render_manual_income_row( $index, $row ) {
		$row = wp_parse_args(
			is_array( $row ) ? $row : array(),
			array(
				'label'  => '',
				'amount' => '',
				'note'   => '',
			)
		);
		?>
		<div class="def-repeatable-row def-repeatable-row--income">
			<label class="def-field-wide">
				<span><?php esc_html_e( 'Label', 'davenham-events-fundraising' ); ?></span>
				<input type="text" name="davenham_event_manual_income[<?php echo esc_attr( (string) $index ); ?>][label]" value="<?php echo esc_attr( $row['label'] ); ?>" placeholder="<?php esc_attr_e( 'Door tickets, food sales...', 'davenham-events-fundraising' ); ?>">
			</label>
			<label class="def-field-small">
				<span><?php esc_html_e( 'Amount', 'davenham-events-fundraising' ); ?></span>
				<input type="number" step="0.01" name="davenham_event_manual_income[<?php echo esc_attr( (string) $index ); ?>][amount]" value="<?php echo esc_attr( (string) $row['amount'] ); ?>">
			</label>
			<label class="def-field-wide">
				<span><?php esc_html_e( 'Note', 'davenham-events-fundraising' ); ?></span>
				<input type="text" name="davenham_event_manual_income[<?php echo esc_attr( (string) $index ); ?>][note]" value="<?php echo esc_attr( $row['note'] ); ?>">
			</label>
			<button type="button" class="button button-link-delete" data-def-remove-row><?php esc_html_e( 'Remove', 'davenham-events-fundraising' ); ?></button>
		</div>
		<?php
	}

	private static function render_inventory_row( $index, $row ) {
		$row = wp_parse_args(
			is_array( $row ) ? $row : array(),
			array(
				'item'      => '',
				'quantity'  => '',
				'unit_cost' => '',
				'status'    => 'needed',
				'supplier'  => '',
				'note'      => '',
			)
		);
		?>
		<div class="def-repeatable-row def-repeatable-row--inventory">
			<label class="def-field-item">
				<span class="screen-reader-text"><?php esc_html_e( 'Item', 'davenham-events-fundraising' ); ?></span>
				<input type="text" name="davenham_event_inventory[<?php echo esc_attr( (string) $index ); ?>][item]" value="<?php echo esc_attr( $row['item'] ); ?>" placeholder="<?php esc_attr_e( 'Food, raffle prizes, decorations...', 'davenham-events-fundraising' ); ?>">
			</label>
			<label class="def-field-small">
				<span class="screen-reader-text"><?php esc_html_e( 'Qty', 'davenham-events-fundraising' ); ?></span>
				<input type="number" min="0" step="0.01" name="davenham_event_inventory[<?php echo esc_attr( (string) $index ); ?>][quantity]" value="<?php echo esc_attr( (string) $row['quantity'] ); ?>">
			</label>
			<label class="def-field-small">
				<span class="screen-reader-text"><?php esc_html_e( 'Unit cost', 'davenham-events-fundraising' ); ?></span>
				<input type="number" min="0" step="0.01" name="davenham_event_inventory[<?php echo esc_attr( (string) $index ); ?>][unit_cost]" value="<?php echo esc_attr( (string) $row['unit_cost'] ); ?>">
			</label>
			<label class="def-field-medium">
				<span class="screen-reader-text"><?php esc_html_e( 'Status', 'davenham-events-fundraising' ); ?></span>
				<select name="davenham_event_inventory[<?php echo esc_attr( (string) $index ); ?>][status]">
					<option value="needed" <?php selected( $row['status'], 'needed' ); ?>><?php esc_html_e( 'Need to buy', 'davenham-events-fundraising' ); ?></option>
					<option value="bought" <?php selected( $row['status'], 'bought' ); ?>><?php esc_html_e( 'Bought / in hand', 'davenham-events-fundraising' ); ?></option>
				</select>
			</label>
			<label class="def-field-medium">
				<span class="screen-reader-text"><?php esc_html_e( 'Supplier', 'davenham-events-fundraising' ); ?></span>
				<input type="text" name="davenham_event_inventory[<?php echo esc_attr( (string) $index ); ?>][supplier]" value="<?php echo esc_attr( $row['supplier'] ); ?>">
			</label>
			<input type="hidden" name="davenham_event_inventory[<?php echo esc_attr( (string) $index ); ?>][note]" value="<?php echo esc_attr( $row['note'] ); ?>">
			<span class="def-row-total" data-def-row-total><?php echo esc_html( self::money_plain( self::inventory_row_cost( $row ) ) ); ?></span>
			<button type="button" class="button button-link-delete def-icon-button" data-def-remove-row aria-label="<?php esc_attr_e( 'Remove shopping item', 'davenham-events-fundraising' ); ?>"><span class="dashicons dashicons-trash" aria-hidden="true"></span></button>
		</div>
		<?php
	}

	private static function render_checklist_row( $index, $row ) {
		$row = wp_parse_args(
			is_array( $row ) ? $row : array(),
			array(
				'task'     => '',
				'owner'    => '',
				'due_date' => '',
				'status'   => 'todo',
				'note'     => '',
			)
		);
		?>
		<div class="def-repeatable-row def-repeatable-row--checklist">
			<label class="def-check-done">
				<span class="screen-reader-text"><?php esc_html_e( 'Done', 'davenham-events-fundraising' ); ?></span>
				<input type="checkbox" data-def-check-done <?php checked( $row['status'], 'done' ); ?>>
			</label>
			<label class="def-field-item">
				<span class="screen-reader-text"><?php esc_html_e( 'Task', 'davenham-events-fundraising' ); ?></span>
				<input type="text" name="davenham_event_checklist[<?php echo esc_attr( (string) $index ); ?>][task]" value="<?php echo esc_attr( $row['task'] ); ?>" placeholder="<?php esc_attr_e( 'Put signs out, check float, open gate...', 'davenham-events-fundraising' ); ?>">
			</label>
			<label class="def-field-medium">
				<span class="screen-reader-text"><?php esc_html_e( 'Who', 'davenham-events-fundraising' ); ?></span>
				<input type="text" name="davenham_event_checklist[<?php echo esc_attr( (string) $index ); ?>][owner]" value="<?php echo esc_attr( $row['owner'] ); ?>">
			</label>
			<label class="def-field-small">
				<span class="screen-reader-text"><?php esc_html_e( 'Due', 'davenham-events-fundraising' ); ?></span>
				<input type="date" name="davenham_event_checklist[<?php echo esc_attr( (string) $index ); ?>][due_date]" value="<?php echo esc_attr( $row['due_date'] ); ?>">
			</label>
			<label class="def-field-medium">
				<span class="screen-reader-text"><?php esc_html_e( 'Status', 'davenham-events-fundraising' ); ?></span>
				<select name="davenham_event_checklist[<?php echo esc_attr( (string) $index ); ?>][status]" data-def-check-status>
					<option value="todo" <?php selected( $row['status'], 'todo' ); ?>><?php esc_html_e( 'To do', 'davenham-events-fundraising' ); ?></option>
					<option value="doing" <?php selected( $row['status'], 'doing' ); ?>><?php esc_html_e( 'In progress', 'davenham-events-fundraising' ); ?></option>
					<option value="done" <?php selected( $row['status'], 'done' ); ?>><?php esc_html_e( 'Done', 'davenham-events-fundraising' ); ?></option>
				</select>
			</label>
			<label class="def-field-note">
				<span class="screen-reader-text"><?php esc_html_e( 'Note', 'davenham-events-fundraising' ); ?></span>
				<input type="text" name="davenham_event_checklist[<?php echo esc_attr( (string) $index ); ?>][note]" value="<?php echo esc_attr( $row['note'] ); ?>">
			</label>
			<button type="button" class="button button-link-delete def-icon-button" data-def-remove-row aria-label="<?php esc_attr_e( 'Remove checklist item', 'davenham-events-fundraising' ); ?>"><span class="dashicons dashicons-trash" aria-hidden="true"></span></button>
		</div>
		<?php
	}

	private static function sanitize_id_list( $value ) {
		if ( is_string( $value ) ) {
			$value = preg_split( '/[\s,]+/', $value );
		}

		$ids = array();
		foreach ( (array) $value as $id ) {
			$id = absint( $id );
			if ( $id > 0 ) {
				$ids[] = $id;
			}
		}

		return array_values( array_unique( $ids ) );
	}

	private static function sanitize_manual_income_rows( $rows ) {
		$clean = array();

		foreach ( (array) $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$label = sanitize_text_field( wp_unslash( $row['label'] ?? '' ) );
			$amount = self::parse_amount( wp_unslash( $row['amount'] ?? 0 ) );
			$note = sanitize_text_field( wp_unslash( $row['note'] ?? '' ) );

			if ( '' === $label && 0.0 === $amount && '' === $note ) {
				continue;
			}

			$clean[] = array(
				'label'  => $label ?: __( 'Manual income', 'davenham-events-fundraising' ),
				'amount' => $amount,
				'note'   => $note,
			);
		}

		return $clean;
	}

	private static function sanitize_inventory_rows( $rows ) {
		$clean = array();

		foreach ( (array) $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$item = sanitize_text_field( wp_unslash( $row['item'] ?? '' ) );
			$quantity = max( 0, (float) self::parse_amount( wp_unslash( $row['quantity'] ?? 0 ) ) );
			$unit_cost = max( 0, self::parse_amount( wp_unslash( $row['unit_cost'] ?? 0 ) ) );
			$status = sanitize_key( wp_unslash( $row['status'] ?? 'needed' ) );
			$supplier = sanitize_text_field( wp_unslash( $row['supplier'] ?? '' ) );
			$note = sanitize_text_field( wp_unslash( $row['note'] ?? '' ) );

			if ( '' === $item && 0.0 === $quantity && 0.0 === $unit_cost && '' === $supplier && '' === $note ) {
				continue;
			}

			if ( ! in_array( $status, array( 'needed', 'bought' ), true ) ) {
				$status = 'needed';
			}

			$clean[] = array(
				'item'      => $item ?: __( 'Shopping item', 'davenham-events-fundraising' ),
				'quantity'  => $quantity,
				'unit_cost' => $unit_cost,
				'status'    => $status,
				'supplier'  => $supplier,
				'note'      => $note,
			);
		}

		return $clean;
	}

	public static function sanitize_checklist_rows( $rows ) {
		$clean = array();

		foreach ( (array) $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$task = sanitize_text_field( wp_unslash( $row['task'] ?? '' ) );
			$owner = sanitize_text_field( wp_unslash( $row['owner'] ?? '' ) );
			$due_date = sanitize_text_field( wp_unslash( $row['due_date'] ?? '' ) );
			$status = sanitize_key( wp_unslash( $row['status'] ?? 'todo' ) );
			$note = sanitize_text_field( wp_unslash( $row['note'] ?? '' ) );

			if ( '' === $task && '' === $owner && '' === $due_date && '' === $note ) {
				continue;
			}

			if ( ! in_array( $status, array( 'todo', 'doing', 'done' ), true ) ) {
				$status = 'todo';
			}

			$clean[] = array(
				'task'     => $task ?: __( 'Checklist item', 'davenham-events-fundraising' ),
				'owner'    => $owner,
				'due_date' => $due_date,
				'status'   => $status,
				'note'     => $note,
			);
		}

		return $clean;
	}

	private static function parse_amount( $value ) {
		if ( is_array( $value ) ) {
			return 0;
		}

		$value = (string) $value;
		$value = str_replace( ',', '', $value );
		$value = preg_replace( '/[^0-9.\-]/', '', $value );

		if ( '' === $value || '-' === $value || '.' === $value ) {
			return 0;
		}

		return round( (float) $value, 2 );
	}

	private static function inventory_row_cost( $row ) {
		return max( 0, (float) ( $row['quantity'] ?? 0 ) ) * max( 0, (float) ( $row['unit_cost'] ?? 0 ) );
	}

	private static function format_time( $time ) {
		if ( empty( $time ) ) {
			return '';
		}

		$timestamp = strtotime( $time );
		return $timestamp ? date_i18n( 'g:i a', $timestamp ) : $time;
	}
}

register_activation_hook( DEF_FILE, array( 'Davenham_Events_Fundraising', 'activate' ) );
register_deactivation_hook( DEF_FILE, array( 'Davenham_Events_Fundraising', 'deactivate' ) );
Davenham_Events_Fundraising::init();
