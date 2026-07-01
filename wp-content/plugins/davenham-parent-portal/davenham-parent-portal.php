<?php
/**
 * Plugin Name: Davenham Parent Portal
 * Plugin URI:  https://davenhamscouts.org.uk
 * Description: Parents self-register, an admin approves them, and they get a Parent login to a dashboard with digital event consent forms.
 * Version:     1.2.2
 * Author:      Davenham Scout Group
 * Text Domain: davenham-parent-portal
 * Requires at least: 6.0
 * Requires PHP: 7.0
 */

defined( 'ABSPATH' ) || exit;

define( 'DPP_VERSION', '1.2.2' );
define( 'DPP_FILE', __FILE__ );
define( 'DPP_DIR', plugin_dir_path( __FILE__ ) );
define( 'DPP_URL', plugin_dir_url( __FILE__ ) );

final class Davenham_Parent_Portal {

	const ROLE           = 'davenham_parent';
	const CAP            = 'access_parent_portal';
	const APP_CPT        = 'dpp_application';
	const CHILDREN_META  = '_dpp_children';
	const STATUS_META    = '_dpp_status';
	const FIELDS_META    = '_dpp_fields';
	const USER_META      = '_dpp_user_id';
	const VERSION_OPTION = 'davenham_parent_portal_version';
	const PORTAL_PAGE_OPTION = 'dpp_portal_page_id';
	const REGISTER_NONCE = 'dpp_register';

	// Event consent (1B).
	const CONSENT_CPT    = 'dpp_consent';
	const VIEW_CAP       = 'view_event_consents';
	const CONSENT_NONCE  = 'dpp_consent';
	const CONSENT_META   = '_dpp_consent';

	// Admin-managed sections taxonomy (grows over time, no code change).
	const SECTION_TAX    = 'davenham_section';

	private static $sections_cache = null;

	/**
	 * Default sections seeded on first activation (and the fallback if the
	 * taxonomy is ever empty, so the forms never break).
	 */
	private static function default_sections() {
		return array( 'Monday Beavers', 'Monday Scouts', 'Tuesday Cubs', 'Friday Cubs' );
	}

	/**
	 * Single source of truth for sections (slug => label), read from the
	 * admin-managed taxonomy. Filterable so other plugins can extend it.
	 */
	public static function sections() {
		if ( null !== self::$sections_cache ) {
			return self::$sections_cache;
		}

		$out = array();
		if ( taxonomy_exists( self::SECTION_TAX ) ) {
			$terms = get_terms(
				array(
					'taxonomy'   => self::SECTION_TAX,
					'hide_empty' => false,
					'orderby'    => 'name',
				)
			);
			if ( ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term ) {
					$out[ $term->slug ] = $term->name;
				}
			}
		}

		if ( empty( $out ) ) {
			foreach ( self::default_sections() as $label ) {
				$out[ sanitize_title( $label ) ] = $label;
			}
		}

		self::$sections_cache = apply_filters( 'davenham_sections', $out );
		return self::$sections_cache;
	}

	public static function section_label( $slug ) {
		$sections = self::sections();
		if ( isset( $sections[ $slug ] ) ) {
			return $sections[ $slug ];
		}
		// Graceful fallback if a section was later renamed/removed.
		return $slug ? ucwords( str_replace( '-', ' ', $slug ) ) : '';
	}

	public static function register_section_taxonomy() {
		register_taxonomy(
			self::SECTION_TAX,
			array( self::APP_CPT ),
			array(
				'labels'            => array(
					'name'          => __( 'Sections', 'davenham-parent-portal' ),
					'singular_name' => __( 'Section', 'davenham-parent-portal' ),
					'menu_name'     => __( 'Sections', 'davenham-parent-portal' ),
					'add_new_item'  => __( 'Add New Section', 'davenham-parent-portal' ),
					'edit_item'     => __( 'Edit Section', 'davenham-parent-portal' ),
					'search_items'  => __( 'Search Sections', 'davenham-parent-portal' ),
				),
				'public'            => false,
				'show_ui'           => true,
				'show_in_menu'      => true,
				'show_admin_column' => false,
				'show_in_rest'      => false,
				'hierarchical'      => true,
				'rewrite'           => false,
				'capabilities'      => array(
					'manage_terms' => 'manage_options',
					'edit_terms'   => 'manage_options',
					'delete_terms' => 'manage_options',
					'assign_terms' => 'manage_options',
				),
			)
		);
	}

	public static function ensure_default_sections() {
		if ( ! taxonomy_exists( self::SECTION_TAX ) ) {
			self::register_section_taxonomy();
		}
		$terms = get_terms(
			array(
				'taxonomy'   => self::SECTION_TAX,
				'hide_empty' => false,
			)
		);
		// Only seed when there are no sections at all — never re-add after the
		// group starts managing their own list.
		if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
			return;
		}
		foreach ( self::default_sections() as $label ) {
			if ( ! term_exists( $label, self::SECTION_TAX ) ) {
				wp_insert_term( $label, self::SECTION_TAX );
			}
		}
	}

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_application_cpt' ) );
		add_action( 'init', array( __CLASS__, 'register_consent_cpt' ) );
		add_action( 'init', array( __CLASS__, 'register_section_taxonomy' ) );
		add_action( 'init', array( __CLASS__, 'register_shortcodes' ) );

		add_action( 'admin_init', array( __CLASS__, 'maybe_upgrade' ) );

		// Front-end registration submit.
		add_action( 'template_redirect', array( __CLASS__, 'handle_registration' ) );

		// Event consent (1B).
		add_action( 'template_redirect', array( __CLASS__, 'handle_consent' ) );
		add_filter( 'the_content', array( __CLASS__, 'inject_event_consent' ) );
		add_action( 'admin_menu', array( __CLASS__, 'register_consents_admin_page' ) );
		add_action( 'admin_post_dpp_export_consents', array( __CLASS__, 'export_consents_csv' ) );

		// Approval workflow.
		add_action( 'admin_post_dpp_approve', array( __CLASS__, 'handle_approve' ) );
		add_action( 'admin_post_dpp_reject', array( __CLASS__, 'handle_reject' ) );

		// Application admin UI.
		add_filter( 'manage_' . self::APP_CPT . '_posts_columns', array( __CLASS__, 'application_columns' ) );
		add_action( 'manage_' . self::APP_CPT . '_posts_custom_column', array( __CLASS__, 'application_column_content' ), 10, 2 );
		add_filter( 'post_row_actions', array( __CLASS__, 'application_row_actions' ), 10, 2 );
		add_action( 'add_meta_boxes', array( __CLASS__, 'register_meta_boxes' ) );
		add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ) );

		// Send parents to the dashboard after login.
		add_filter( 'login_redirect', array( __CLASS__, 'login_redirect' ), 10, 3 );

		// Assets.
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_public_assets' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
	}

	/* ---------------------------------------------------------------------
	 * Activation / setup
	 * ------------------------------------------------------------------- */

	public static function activate() {
		self::register_application_cpt();
		self::register_section_taxonomy();
		self::create_role();
		self::ensure_default_sections();
		self::ensure_portal_page();
		update_option( self::VERSION_OPTION, DPP_VERSION, false );
		flush_rewrite_rules();
	}

	public static function deactivate() {
		flush_rewrite_rules();
	}

	public static function maybe_upgrade() {
		if ( get_option( self::VERSION_OPTION ) === DPP_VERSION ) {
			return;
		}
		self::create_role();
		self::ensure_default_sections();
		self::ensure_portal_page();
		update_option( self::VERSION_OPTION, DPP_VERSION, false );
	}

	public static function create_role() {
		$caps = array(
			'read'      => true,
			self::CAP   => true,
		);
		if ( ! get_role( self::ROLE ) ) {
			add_role( self::ROLE, 'Parent', $caps );
		} else {
			$role = get_role( self::ROLE );
			foreach ( $caps as $cap => $grant ) {
				$role->add_cap( $cap );
			}
		}

		$admin = get_role( 'administrator' );
		if ( $admin ) {
			$admin->add_cap( self::CAP );
			$admin->add_cap( self::VIEW_CAP );
		}

		// Leaders and trustees (from the documents plugin) may view consents,
		// if those roles exist. No hard dependency on that plugin.
		foreach ( array( 'davenham_trustee', 'davenham_leader' ) as $role_slug ) {
			$role = get_role( $role_slug );
			if ( $role ) {
				$role->add_cap( self::VIEW_CAP );
			}
		}
	}

	/**
	 * Auto-create a published "Parent Portal" page holding the dashboard
	 * shortcode, so the login redirect has a stable target.
	 */
	public static function ensure_portal_page() {
		$page_id = (int) get_option( self::PORTAL_PAGE_OPTION );
		if ( $page_id && 'page' === get_post_type( $page_id ) && 'trash' !== get_post_status( $page_id ) ) {
			return;
		}

		$page_id = wp_insert_post(
			array(
				'post_title'   => 'Parent Portal',
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => '[davenham_parent_dashboard]',
			)
		);

		if ( $page_id && ! is_wp_error( $page_id ) ) {
			update_option( self::PORTAL_PAGE_OPTION, (int) $page_id, false );
		}
	}

	public static function portal_url() {
		$page_id = (int) get_option( self::PORTAL_PAGE_OPTION );
		return $page_id ? get_permalink( $page_id ) : home_url( '/' );
	}

	/* ---------------------------------------------------------------------
	 * Application CPT
	 * ------------------------------------------------------------------- */

	public static function register_application_cpt() {
		if ( post_type_exists( self::APP_CPT ) ) {
			return;
		}

		$admin_only = array(
			'edit_posts'          => 'manage_options',
			'edit_others_posts'   => 'manage_options',
			'publish_posts'       => 'manage_options',
			'read_private_posts'  => 'manage_options',
			'delete_posts'        => 'manage_options',
			'delete_others_posts' => 'manage_options',
			'create_posts'        => 'do_not_allow', // applications only arrive via the form
		);

		register_post_type(
			self::APP_CPT,
			array(
				'labels'          => array(
					'name'          => __( 'Parent Applications', 'davenham-parent-portal' ),
					'singular_name' => __( 'Parent Application', 'davenham-parent-portal' ),
					'menu_name'     => __( 'Parent Applications', 'davenham-parent-portal' ),
					'search_items'  => __( 'Search Applications', 'davenham-parent-portal' ),
					'not_found'     => __( 'No applications.', 'davenham-parent-portal' ),
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => true,
				'show_in_rest'    => false,
				'menu_icon'       => 'dashicons-groups',
				'menu_position'   => 26,
				'supports'        => array( 'title' ),
				'capabilities'    => $admin_only,
				'map_meta_cap'    => true,
			)
		);
	}

	/* ---------------------------------------------------------------------
	 * Flash messaging (survives the post-redirect-get on validation errors)
	 * ------------------------------------------------------------------- */

	private static function store_flash( $data ) {
		$token = wp_generate_password( 20, false, false );
		set_transient( 'dpp_flash_' . $token, $data, 10 * MINUTE_IN_SECONDS );
		return $token;
	}

	private static function get_flash( $token ) {
		$token = preg_replace( '/[^A-Za-z0-9]/', '', (string) $token );
		if ( '' === $token ) {
			return null;
		}
		$data = get_transient( 'dpp_flash_' . $token );
		if ( false !== $data ) {
			delete_transient( 'dpp_flash_' . $token );
			return $data;
		}
		return null;
	}

	private static function redirect_with_flash( $status, $data ) {
		$target = wp_get_referer();
		if ( ! $target ) {
			$target = home_url( '/' );
		}
		$token = self::store_flash( $data );
		$url   = add_query_arg(
			array(
				'dpp_status' => $status,
				'dpp_token'  => $token,
			),
			$target
		);
		wp_safe_redirect( $url );
		exit;
	}

	/* ---------------------------------------------------------------------
	 * Registration handling
	 * ------------------------------------------------------------------- */

	public static function handle_registration() {
		if ( empty( $_POST['dpp_form'] ) || 'register' !== $_POST['dpp_form'] ) {
			return;
		}

		if ( ! isset( $_POST['dpp_register_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['dpp_register_nonce'] ) ), self::REGISTER_NONCE ) ) {
			self::redirect_with_flash( 'error', array( 'errors' => array( __( 'Your session expired. Please try again.', 'davenham-parent-portal' ) ), 'values' => array() ) );
		}

		// Honeypot — silently accept without storing if a bot filled it.
		if ( ! empty( $_POST['dpp_website'] ) ) {
			self::redirect_with_flash( 'success', array() );
		}

		$values = array(
			'parent_name' => isset( $_POST['dpp_parent_name'] ) ? sanitize_text_field( wp_unslash( $_POST['dpp_parent_name'] ) ) : '',
			'email'       => isset( $_POST['dpp_email'] ) ? sanitize_email( wp_unslash( $_POST['dpp_email'] ) ) : '',
			'phone'       => isset( $_POST['dpp_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['dpp_phone'] ) ) : '',
		);

		$children = self::collect_children();
		$values['children'] = $children;

		$errors = array();
		if ( '' === $values['parent_name'] ) {
			$errors[] = __( 'Please enter your name.', 'davenham-parent-portal' );
		}
		if ( '' === $values['email'] || ! is_email( $values['email'] ) ) {
			$errors[] = __( 'Please enter a valid email address.', 'davenham-parent-portal' );
		}
		if ( empty( $children ) ) {
			$errors[] = __( 'Please add at least one child, with their section.', 'davenham-parent-portal' );
		}
		if ( $values['email'] && is_email( $values['email'] ) && email_exists( $values['email'] ) ) {
			$errors[] = __( 'An account with that email already exists. Please log in instead, or contact us.', 'davenham-parent-portal' );
		}

		if ( ! empty( $errors ) ) {
			self::redirect_with_flash( 'error', array( 'errors' => $errors, 'values' => $values ) );
		}

		// Store the pending application — no WordPress user yet.
		$title   = $values['parent_name'] . ' (' . $values['email'] . ')';
		$post_id = wp_insert_post(
			array(
				'post_type'   => self::APP_CPT,
				'post_status' => 'private',
				'post_title'  => $title,
			),
			true
		);

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			self::redirect_with_flash( 'error', array( 'errors' => array( __( 'Sorry, something went wrong saving your details. Please try again.', 'davenham-parent-portal' ) ), 'values' => $values ) );
		}

		update_post_meta( $post_id, self::STATUS_META, 'pending' );
		update_post_meta( $post_id, self::FIELDS_META, array(
			'parent_name' => $values['parent_name'],
			'email'       => $values['email'],
			'phone'       => $values['phone'],
			'submitted'   => current_time( 'mysql' ),
		) );
		update_post_meta( $post_id, self::CHILDREN_META, $children );

		self::notify_admin_new_application( $post_id, $values );

		self::redirect_with_flash( 'success', array() );
	}

	private static function collect_children() {
		$children = array();
		if ( empty( $_POST['dpp_child_name'] ) || ! is_array( $_POST['dpp_child_name'] ) ) {
			return $children;
		}

		$names    = wp_unslash( $_POST['dpp_child_name'] );
		$dobs     = isset( $_POST['dpp_child_dob'] ) ? wp_unslash( $_POST['dpp_child_dob'] ) : array();
		$sections = isset( $_POST['dpp_child_section'] ) ? wp_unslash( $_POST['dpp_child_section'] ) : array();
		$valid_sections = self::sections();

		$count = count( $names );
		for ( $i = 0; $i < $count; $i++ ) {
			$name = sanitize_text_field( isset( $names[ $i ] ) ? $names[ $i ] : '' );
			if ( '' === $name ) {
				continue;
			}
			$section = isset( $sections[ $i ] ) ? sanitize_key( $sections[ $i ] ) : '';
			if ( ! isset( $valid_sections[ $section ] ) ) {
				$section = '';
			}
			$children[] = array(
				'name'    => $name,
				'dob'     => isset( $dobs[ $i ] ) ? sanitize_text_field( $dobs[ $i ] ) : '',
				'section' => $section,
			);
		}
		return $children;
	}

	private static function admin_email_recipient() {
		if ( function_exists( 'db_get_site_settings' ) ) {
			$settings = db_get_site_settings();
			if ( ! empty( $settings['form_notifications_email'] ) && is_email( $settings['form_notifications_email'] ) ) {
				return $settings['form_notifications_email'];
			}
		}
		return get_option( 'admin_email' );
	}

	private static function mail_headers( $reply_to = '' ) {
		$domain  = wp_parse_url( home_url(), PHP_URL_HOST );
		$domain  = $domain ? preg_replace( '/^www\./', '', $domain ) : 'davenhamscouts.org.uk';
		$headers = array( 'From: "1st Davenham Scouts" <no-reply@' . $domain . '>' );
		if ( $reply_to && is_email( $reply_to ) ) {
			$headers[] = 'Reply-To: ' . $reply_to;
		}
		return $headers;
	}

	private static function notify_admin_new_application( $post_id, $values ) {
		$to      = self::admin_email_recipient();
		$subject = '[Davenham] New parent registration: ' . $values['parent_name'];
		$lines   = array();
		$lines[] = 'A parent has registered and is awaiting approval.';
		$lines[] = '';
		$lines[] = 'Name:  ' . $values['parent_name'];
		$lines[] = 'Email: ' . $values['email'];
		$lines[] = 'Phone: ' . ( $values['phone'] ? $values['phone'] : '—' );
		$lines[] = '';
		$lines[] = 'Children:';
		foreach ( $values['children'] as $child ) {
			$lines[] = ' - ' . $child['name'] . ' (' . ( self::section_label( $child['section'] ) ? self::section_label( $child['section'] ) : 'section not set' ) . ')';
		}
		$lines[] = '';
		$lines[] = 'Approve or reject: ' . admin_url( 'post.php?post=' . $post_id . '&action=edit' );

		// Best-effort; never blocks registration.
		wp_mail( $to, $subject, implode( "\n", $lines ), self::mail_headers( $values['email'] ) );
	}

	/* ---------------------------------------------------------------------
	 * Approval workflow
	 * ------------------------------------------------------------------- */

	public static function handle_approve() {
		$app_id = isset( $_GET['app'] ) ? absint( $_GET['app'] ) : 0;
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'davenham-parent-portal' ) );
		}
		check_admin_referer( 'dpp_approve_' . $app_id );

		$app = get_post( $app_id );
		if ( ! $app || self::APP_CPT !== $app->post_type ) {
			self::redirect_to_list( 'error' );
		}

		$fields   = get_post_meta( $app_id, self::FIELDS_META, true );
		$children = get_post_meta( $app_id, self::CHILDREN_META, true );
		$email    = is_array( $fields ) && ! empty( $fields['email'] ) ? $fields['email'] : '';
		$name     = is_array( $fields ) && ! empty( $fields['parent_name'] ) ? $fields['parent_name'] : '';

		if ( ! $email || ! is_email( $email ) ) {
			self::redirect_to_list( 'error' );
		}

		$existing = get_user_by( 'email', $email );
		if ( $existing ) {
			// Promote the existing account rather than duplicate it.
			$existing->add_role( self::ROLE );
			$user_id = $existing->ID;
			$new_user = false;
		} else {
			$user_id = wp_insert_user(
				array(
					'user_login'   => self::unique_login( $email ),
					'user_email'   => $email,
					'user_pass'    => wp_generate_password( 24 ),
					'display_name' => $name ? $name : $email,
					'role'         => self::ROLE,
				)
			);
			$new_user = true;
		}

		if ( is_wp_error( $user_id ) || ! $user_id ) {
			self::redirect_to_list( 'error' );
		}

		if ( is_array( $children ) ) {
			update_user_meta( $user_id, self::CHILDREN_META, $children );
		}
		update_post_meta( $app_id, self::STATUS_META, 'approved' );
		update_post_meta( $app_id, self::USER_META, (int) $user_id );

		if ( $new_user ) {
			// WordPress emails the parent a "set your password" link — we never
			// see or set their password.
			wp_send_new_user_notifications( $user_id, 'user' );
		}

		self::redirect_to_list( 'approved' );
	}

	public static function handle_reject() {
		$app_id = isset( $_GET['app'] ) ? absint( $_GET['app'] ) : 0;
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'davenham-parent-portal' ) );
		}
		check_admin_referer( 'dpp_reject_' . $app_id );

		$app = get_post( $app_id );
		if ( $app && self::APP_CPT === $app->post_type ) {
			update_post_meta( $app_id, self::STATUS_META, 'rejected' );
		}
		self::redirect_to_list( 'rejected' );
	}

	private static function unique_login( $email ) {
		$base  = sanitize_user( current( explode( '@', $email ) ), true );
		$base  = $base ? $base : 'parent';
		$login = $base;
		$n     = 1;
		while ( username_exists( $login ) ) {
			$n++;
			$login = $base . $n;
		}
		return $login;
	}

	private static function redirect_to_list( $status ) {
		$url = add_query_arg(
			array(
				'post_type'  => self::APP_CPT,
				'dpp_result' => $status,
			),
			admin_url( 'edit.php' )
		);
		wp_safe_redirect( $url );
		exit;
	}

	private static function approve_url( $app_id ) {
		return wp_nonce_url(
			admin_url( 'admin-post.php?action=dpp_approve&app=' . (int) $app_id ),
			'dpp_approve_' . (int) $app_id
		);
	}

	private static function reject_url( $app_id ) {
		return wp_nonce_url(
			admin_url( 'admin-post.php?action=dpp_reject&app=' . (int) $app_id ),
			'dpp_reject_' . (int) $app_id
		);
	}

	/* ---------------------------------------------------------------------
	 * Application admin UI
	 * ------------------------------------------------------------------- */

	public static function application_columns( $columns ) {
		return array(
			'cb'          => isset( $columns['cb'] ) ? $columns['cb'] : '',
			'title'       => __( 'Applicant', 'davenham-parent-portal' ),
			'dpp_status'  => __( 'Status', 'davenham-parent-portal' ),
			'dpp_kids'    => __( 'Children', 'davenham-parent-portal' ),
			'dpp_actions' => __( 'Actions', 'davenham-parent-portal' ),
			'date'        => __( 'Submitted', 'davenham-parent-portal' ),
		);
	}

	public static function application_column_content( $column, $post_id ) {
		if ( 'dpp_status' === $column ) {
			$status = get_post_meta( $post_id, self::STATUS_META, true );
			$status = $status ? $status : 'pending';
			$colors = array( 'pending' => '#b26a00', 'approved' => '#1d6f42', 'rejected' => '#a00' );
			$color  = isset( $colors[ $status ] ) ? $colors[ $status ] : '#444';
			echo '<strong style="color:' . esc_attr( $color ) . ';">' . esc_html( ucfirst( $status ) ) . '</strong>';
		} elseif ( 'dpp_kids' === $column ) {
			$children = get_post_meta( $post_id, self::CHILDREN_META, true );
			if ( is_array( $children ) && ! empty( $children ) ) {
				$bits = array();
				foreach ( $children as $c ) {
					$bits[] = esc_html( $c['name'] . ' · ' . ( self::section_label( $c['section'] ) ? self::section_label( $c['section'] ) : '—' ) );
				}
				echo implode( '<br>', $bits ); // already escaped above
			} else {
				echo '—';
			}
		} elseif ( 'dpp_actions' === $column ) {
			$status = get_post_meta( $post_id, self::STATUS_META, true );
			if ( 'approved' === $status ) {
				echo '<span style="color:#1d6f42;">' . esc_html__( 'Approved', 'davenham-parent-portal' ) . '</span>';
			} else {
				echo '<a class="button button-primary button-small" href="' . esc_url( self::approve_url( $post_id ) ) . '">' . esc_html__( 'Approve', 'davenham-parent-portal' ) . '</a> ';
				echo '<a class="button button-small" href="' . esc_url( self::reject_url( $post_id ) ) . '" onclick="return confirm(\'' . esc_js( __( 'Reject this application?', 'davenham-parent-portal' ) ) . '\');">' . esc_html__( 'Reject', 'davenham-parent-portal' ) . '</a>';
			}
		}
	}

	public static function application_row_actions( $actions, $post ) {
		if ( self::APP_CPT === $post->post_type ) {
			// Keep it clean — actions live in the dedicated column / meta box.
			unset( $actions['inline hide-if-no-js'] );
		}
		return $actions;
	}

	public static function register_meta_boxes() {
		// The sections taxonomy is attached to this CPT only to surface its
		// management menu — don't clutter the application screen with its box.
		remove_meta_box( 'davenham_sectiondiv', self::APP_CPT, 'side' );

		add_meta_box(
			'dpp_application_details',
			__( 'Application', 'davenham-parent-portal' ),
			array( __CLASS__, 'render_application_meta_box' ),
			self::APP_CPT,
			'normal',
			'high'
		);
	}

	public static function render_application_meta_box( $post ) {
		$fields   = get_post_meta( $post->ID, self::FIELDS_META, true );
		$children = get_post_meta( $post->ID, self::CHILDREN_META, true );
		$status   = get_post_meta( $post->ID, self::STATUS_META, true );
		$status   = $status ? $status : 'pending';
		$fields   = is_array( $fields ) ? $fields : array();

		echo '<p><strong>' . esc_html__( 'Status:', 'davenham-parent-portal' ) . '</strong> ' . esc_html( ucfirst( $status ) ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Name:', 'davenham-parent-portal' ) . '</strong> ' . esc_html( isset( $fields['parent_name'] ) ? $fields['parent_name'] : '' ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Email:', 'davenham-parent-portal' ) . '</strong> ' . esc_html( isset( $fields['email'] ) ? $fields['email'] : '' ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Phone:', 'davenham-parent-portal' ) . '</strong> ' . esc_html( isset( $fields['phone'] ) && $fields['phone'] ? $fields['phone'] : '—' ) . '</p>';

		echo '<p><strong>' . esc_html__( 'Children:', 'davenham-parent-portal' ) . '</strong></p><ul style="margin-left:18px;list-style:disc;">';
		if ( is_array( $children ) && ! empty( $children ) ) {
			foreach ( $children as $c ) {
				$label = self::section_label( $c['section'] );
				echo '<li>' . esc_html( $c['name'] ) . ' — ' . esc_html( $label ? $label : __( 'section not set', 'davenham-parent-portal' ) );
				if ( ! empty( $c['dob'] ) ) {
					echo ' <span style="color:#777;">(' . esc_html( $c['dob'] ) . ')</span>';
				}
				echo '</li>';
			}
		} else {
			echo '<li>—</li>';
		}
		echo '</ul>';

		if ( 'approved' !== $status ) {
			echo '<p style="margin-top:14px;">';
			echo '<a class="button button-primary" href="' . esc_url( self::approve_url( $post->ID ) ) . '">' . esc_html__( 'Approve &amp; create login', 'davenham-parent-portal' ) . '</a> ';
			echo '<a class="button" href="' . esc_url( self::reject_url( $post->ID ) ) . '" onclick="return confirm(\'' . esc_js( __( 'Reject this application?', 'davenham-parent-portal' ) ) . '\');">' . esc_html__( 'Reject', 'davenham-parent-portal' ) . '</a>';
			echo '</p>';
			echo '<p class="description">' . esc_html__( 'Approving creates a Parent login and emails them a link to set their own password.', 'davenham-parent-portal' ) . '</p>';
		} else {
			$uid = (int) get_post_meta( $post->ID, self::USER_META, true );
			if ( $uid ) {
				echo '<p><a href="' . esc_url( admin_url( 'user-edit.php?user_id=' . $uid ) ) . '">' . esc_html__( 'View the parent user', 'davenham-parent-portal' ) . '</a></p>';
			}
		}
	}

	public static function admin_notices() {
		if ( empty( $_GET['dpp_result'] ) ) {
			return;
		}
		$result = sanitize_key( wp_unslash( $_GET['dpp_result'] ) );
		$map    = array(
			'approved' => array( 'updated', __( 'Parent approved — a login was created and a password-setup email sent.', 'davenham-parent-portal' ) ),
			'rejected' => array( 'updated', __( 'Application marked as rejected.', 'davenham-parent-portal' ) ),
			'error'    => array( 'error', __( 'Sorry, that action could not be completed.', 'davenham-parent-portal' ) ),
		);
		if ( isset( $map[ $result ] ) ) {
			$class = 'error' === $map[ $result ][0] ? 'notice-error' : 'notice-success';
			echo '<div class="notice ' . esc_attr( $class ) . ' is-dismissible"><p>' . esc_html( $map[ $result ][1] ) . '</p></div>';
		}
	}

	/* ---------------------------------------------------------------------
	 * Login redirect
	 * ------------------------------------------------------------------- */

	public static function login_redirect( $redirect_to, $requested, $user ) {
		if ( $user instanceof WP_User && user_can( $user, self::CAP ) && ! user_can( $user, 'manage_options' ) ) {
			return self::portal_url();
		}
		return $redirect_to;
	}

	/* ---------------------------------------------------------------------
	 * Shortcodes / front-end
	 * ------------------------------------------------------------------- */

	public static function register_shortcodes() {
		add_shortcode( 'davenham_parent_register', array( __CLASS__, 'render_register' ) );
		add_shortcode( 'davenham_parent_dashboard', array( __CLASS__, 'render_dashboard' ) );
		add_shortcode( 'davenham_event_consent', array( __CLASS__, 'render_consent' ) );
	}

	public static function register_public_assets() {
		wp_register_style( 'davenham-parent-portal', DPP_URL . 'assets/portal.css', array(), DPP_VERSION );
		wp_register_script( 'davenham-parent-portal', DPP_URL . 'assets/portal.js', array(), DPP_VERSION, true );
	}

	public static function enqueue_admin_assets() {
		$screen = get_current_screen();
		if ( $screen && self::APP_CPT === $screen->post_type ) {
			wp_enqueue_style( 'davenham-parent-portal', DPP_URL . 'assets/portal.css', array(), DPP_VERSION );
		}
	}

	public static function render_register( $atts ) {
		wp_enqueue_style( 'davenham-parent-portal' );
		wp_enqueue_script( 'davenham-parent-portal' );

		$flash  = isset( $_GET['dpp_token'] ) ? self::get_flash( sanitize_text_field( wp_unslash( $_GET['dpp_token'] ) ) ) : null;
		$status = isset( $_GET['dpp_status'] ) ? sanitize_key( wp_unslash( $_GET['dpp_status'] ) ) : '';
		$errors = ( $flash && ! empty( $flash['errors'] ) ) ? $flash['errors'] : array();
		$values = ( $flash && ! empty( $flash['values'] ) ) ? $flash['values'] : array();
		$logged_in = is_user_logged_in();
		$sections  = self::sections();

		ob_start();
		include DPP_DIR . 'templates/register.php';
		return ob_get_clean();
	}

	public static function render_dashboard( $atts ) {
		wp_enqueue_style( 'davenham-parent-portal' );

		$can = is_user_logged_in() && current_user_can( self::CAP );
		$here = get_permalink();
		$login_url = wp_login_url( $here ? $here : home_url( '/' ) );

		$children = array();
		$events   = array();
		if ( $can ) {
			$children = get_user_meta( get_current_user_id(), self::CHILDREN_META, true );
			$children = is_array( $children ) ? $children : array();
			$events   = self::upcoming_events();
		}
		$user = wp_get_current_user();

		ob_start();
		include DPP_DIR . 'templates/dashboard.php';
		return ob_get_clean();
	}

	/**
	 * Upcoming published events (the event CPT has no section field yet, so we
	 * show all upcoming events for now; section filtering arrives with plugin 2).
	 */
	private static function upcoming_events() {
		if ( ! post_type_exists( 'event' ) ) {
			return array();
		}
		$today = current_time( 'Y-m-d' );
		$q = new WP_Query(
			array(
				'post_type'      => 'event',
				'post_status'    => 'publish',
				'posts_per_page' => 20,
				'no_found_rows'  => true,
				'meta_key'       => 'event_date',
				'orderby'        => 'meta_value',
				'order'          => 'ASC',
				'meta_query'     => array(
					array(
						'key'     => 'event_date',
						'value'   => $today,
						'compare' => '>=',
						'type'    => 'DATE',
					),
				),
			)
		);
		return $q->posts;
	}

	public static function section_label_public( $slug ) {
		return self::section_label( $slug );
	}

	/* =====================================================================
	 * Event consent (1B)
	 * =================================================================== */

	public static function register_consent_cpt() {
		if ( post_type_exists( self::CONSENT_CPT ) ) {
			return;
		}
		$caps = array(
			'edit_posts'          => self::VIEW_CAP,
			'edit_others_posts'   => self::VIEW_CAP,
			'publish_posts'       => self::VIEW_CAP,
			'read_private_posts'  => self::VIEW_CAP,
			'delete_posts'        => 'manage_options',
			'delete_others_posts' => 'manage_options',
			'create_posts'        => 'do_not_allow', // consents only arrive via the form
		);
		register_post_type(
			self::CONSENT_CPT,
			array(
				'labels'       => array(
					'name'          => __( 'Event Consents', 'davenham-parent-portal' ),
					'singular_name' => __( 'Consent', 'davenham-parent-portal' ),
				),
				'public'       => false,
				'show_ui'      => false,
				'show_in_menu' => false,
				'show_in_rest' => false,
				'supports'     => array( 'title' ),
				'capabilities' => $caps,
				'map_meta_cap' => true,
			)
		);
	}

	private static function parent_children_for( $user_id = 0 ) {
		$user_id  = $user_id ? (int) $user_id : get_current_user_id();
		$children = get_user_meta( $user_id, self::CHILDREN_META, true );
		return is_array( $children ) ? $children : array();
	}

	private static function find_consent( $event_id, $user_id, $child_name ) {
		$q = new WP_Query(
			array(
				'post_type'      => self::CONSENT_CPT,
				'post_status'    => array( 'private', 'publish' ),
				'posts_per_page' => 1,
				'no_found_rows'  => true,
				'fields'         => 'ids',
				'meta_query'     => array(
					'relation' => 'AND',
					array( 'key' => '_dpp_event_id', 'value' => (int) $event_id ),
					array( 'key' => '_dpp_user_id', 'value' => (int) $user_id ),
					array( 'key' => '_dpp_child', 'value' => $child_name ),
				),
			)
		);
		return ! empty( $q->posts ) ? (int) $q->posts[0] : 0;
	}

	public static function handle_consent() {
		if ( empty( $_POST['dpp_form'] ) || 'consent' !== $_POST['dpp_form'] ) {
			return;
		}
		if ( ! is_user_logged_in() || ! current_user_can( self::CAP ) ) {
			self::redirect_with_flash( 'error', array( 'errors' => array( __( 'Please log in as a parent first.', 'davenham-parent-portal' ) ) ) );
		}
		if ( ! isset( $_POST['dpp_consent_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['dpp_consent_nonce'] ) ), self::CONSENT_NONCE ) ) {
			self::redirect_with_flash( 'error', array( 'errors' => array( __( 'Your session expired. Please try again.', 'davenham-parent-portal' ) ) ) );
		}

		$event_id = isset( $_POST['dpp_event_id'] ) ? absint( $_POST['dpp_event_id'] ) : 0;
		$event    = $event_id ? get_post( $event_id ) : null;
		if ( ! $event || 'event' !== $event->post_type ) {
			self::redirect_with_flash( 'error', array( 'errors' => array( __( 'That event could not be found.', 'davenham-parent-portal' ) ) ) );
		}

		$children = self::parent_children_for();
		$idx      = isset( $_POST['dpp_child_index'] ) ? intval( $_POST['dpp_child_index'] ) : -1;
		if ( ! isset( $children[ $idx ] ) ) {
			self::redirect_with_flash( 'error', array( 'errors' => array( __( 'Please choose which child this consent is for.', 'davenham-parent-portal' ) ) ) );
		}
		$child = $children[ $idx ];

		$attending = ( isset( $_POST['dpp_attending'] ) && 'yes' === $_POST['dpp_attending'] ) ? 'yes' : 'no';
		$data      = array(
			'attending'       => $attending,
			'photo_consent'   => ( isset( $_POST['dpp_photo'] ) && 'yes' === $_POST['dpp_photo'] ) ? 'yes' : 'no',
			'medical'         => isset( $_POST['dpp_medical'] ) ? sanitize_textarea_field( wp_unslash( $_POST['dpp_medical'] ) ) : '',
			'medications'     => isset( $_POST['dpp_medications'] ) ? sanitize_textarea_field( wp_unslash( $_POST['dpp_medications'] ) ) : '',
			'dietary'         => isset( $_POST['dpp_dietary'] ) ? sanitize_textarea_field( wp_unslash( $_POST['dpp_dietary'] ) ) : '',
			'additional'      => isset( $_POST['dpp_additional'] ) ? sanitize_textarea_field( wp_unslash( $_POST['dpp_additional'] ) ) : '',
			'emergency_name'  => isset( $_POST['dpp_emergency_name'] ) ? sanitize_text_field( wp_unslash( $_POST['dpp_emergency_name'] ) ) : '',
			'emergency_phone' => isset( $_POST['dpp_emergency_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['dpp_emergency_phone'] ) ) : '',
			'signature'       => isset( $_POST['dpp_signature'] ) ? sanitize_text_field( wp_unslash( $_POST['dpp_signature'] ) ) : '',
		);

		$errors = array();
		if ( '' === $data['signature'] ) {
			$errors[] = __( 'Please type your name to sign the form.', 'davenham-parent-portal' );
		}
		if ( 'yes' === $attending && ( '' === $data['emergency_name'] || '' === $data['emergency_phone'] ) ) {
			$errors[] = __( 'Please give an emergency contact name and phone number.', 'davenham-parent-portal' );
		}
		if ( ! empty( $errors ) ) {
			self::redirect_with_flash( 'error', array( 'errors' => $errors ) );
		}

		$user_id  = get_current_user_id();
		$existing = self::find_consent( $event_id, $user_id, $child['name'] );
		$postarr  = array(
			'post_type'   => self::CONSENT_CPT,
			'post_status' => 'private',
			'post_title'  => 'Consent — ' . $child['name'] . ' — ' . get_the_title( $event ),
		);
		if ( $existing ) {
			$postarr['ID'] = $existing;
			$cid = wp_update_post( $postarr, true );
		} else {
			$cid = wp_insert_post( $postarr, true );
		}
		if ( is_wp_error( $cid ) || ! $cid ) {
			self::redirect_with_flash( 'error', array( 'errors' => array( __( 'Sorry, your consent could not be saved. Please try again.', 'davenham-parent-portal' ) ) ) );
		}

		update_post_meta( $cid, '_dpp_event_id', (int) $event_id );
		update_post_meta( $cid, '_dpp_user_id', (int) $user_id );
		update_post_meta( $cid, '_dpp_child', $child['name'] );
		update_post_meta( $cid, '_dpp_section', isset( $child['section'] ) ? $child['section'] : '' );
		update_post_meta( $cid, self::CONSENT_META, $data );
		update_post_meta( $cid, '_dpp_signed_at', current_time( 'mysql' ) );

		self::redirect_with_flash( 'consent_saved', array() );
	}

	public static function render_consent( $atts ) {
		$atts     = shortcode_atts( array( 'event_id' => 0 ), $atts, 'davenham_event_consent' );
		$event_id = absint( $atts['event_id'] );
		if ( ! $event_id && is_singular( 'event' ) ) {
			$event_id = (int) get_the_ID();
		}
		return self::consent_markup( $event_id );
	}

	public static function inject_event_consent( $content ) {
		if ( is_singular( 'event' ) && in_the_loop() && is_main_query() &&
			is_user_logged_in() && current_user_can( self::CAP ) &&
			false === strpos( $content, 'davenham_event_consent' ) ) {
			$content .= self::consent_markup( (int) get_the_ID() );
		}
		return $content;
	}

	public static function consent_markup( $event_id ) {
		$event_id = (int) $event_id;
		if ( ! $event_id || ! post_type_exists( 'event' ) ) {
			return '';
		}
		wp_enqueue_style( 'davenham-parent-portal' );

		$can      = is_user_logged_in() && current_user_can( self::CAP );
		$here     = get_permalink();
		$login_url = wp_login_url( $here ? $here : home_url( '/' ) );
		$children = $can ? self::parent_children_for() : array();
		$user_id  = get_current_user_id();

		$status = isset( $_GET['dpp_status'] ) ? sanitize_key( wp_unslash( $_GET['dpp_status'] ) ) : '';
		$flash  = isset( $_GET['dpp_token'] ) ? self::get_flash( sanitize_text_field( wp_unslash( $_GET['dpp_token'] ) ) ) : null;
		$errors = ( $flash && ! empty( $flash['errors'] ) ) ? $flash['errors'] : array();

		$existing_map = array();
		if ( $can ) {
			foreach ( $children as $i => $child ) {
				$cid = self::find_consent( $event_id, $user_id, $child['name'] );
				if ( $cid ) {
					$existing_map[ $i ] = array(
						'signed_at' => get_post_meta( $cid, '_dpp_signed_at', true ),
					);
				}
			}
		}

		ob_start();
		include DPP_DIR . 'templates/consent-form.php';
		return ob_get_clean();
	}

	/* ----- Leader/trustee admin: view + export ----- */

	public static function register_consents_admin_page() {
		add_menu_page(
			__( 'Event Consents', 'davenham-parent-portal' ),
			__( 'Event Consents', 'davenham-parent-portal' ),
			self::VIEW_CAP,
			'dpp-consents',
			array( __CLASS__, 'render_consents_page' ),
			'dashicons-clipboard',
			27
		);
	}

	private static function consents_for_event( $event_id ) {
		$q = new WP_Query(
			array(
				'post_type'      => self::CONSENT_CPT,
				'post_status'    => array( 'private', 'publish' ),
				'posts_per_page' => 500,
				'no_found_rows'  => true,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'meta_key'       => '_dpp_event_id',
				'meta_value'     => (int) $event_id,
			)
		);
		return $q->posts;
	}

	public static function render_consents_page() {
		if ( ! current_user_can( self::VIEW_CAP ) ) {
			wp_die( esc_html__( 'You are not allowed to view consents.', 'davenham-parent-portal' ) );
		}
		$event_id = isset( $_GET['event'] ) ? absint( $_GET['event'] ) : 0;
		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Event Consents', 'davenham-parent-portal' ) . '</h1>';
		if ( $event_id ) {
			self::render_event_consents_table( $event_id );
		} else {
			self::render_consents_overview();
		}
		echo '</div>';
	}

	private static function render_consents_overview() {
		if ( ! post_type_exists( 'event' ) ) {
			echo '<p>' . esc_html__( 'No events found.', 'davenham-parent-portal' ) . '</p>';
			return;
		}
		$events = get_posts(
			array(
				'post_type'      => 'event',
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);
		if ( empty( $events ) ) {
			echo '<p>' . esc_html__( 'No events yet.', 'davenham-parent-portal' ) . '</p>';
			return;
		}
		echo '<p>' . esc_html__( 'Choose an event to see its consent forms.', 'davenham-parent-portal' ) . '</p>';
		echo '<table class="widefat striped"><thead><tr>';
		echo '<th>' . esc_html__( 'Event', 'davenham-parent-portal' ) . '</th>';
		echo '<th>' . esc_html__( 'Date', 'davenham-parent-portal' ) . '</th>';
		echo '<th>' . esc_html__( 'Consents', 'davenham-parent-portal' ) . '</th></tr></thead><tbody>';
		foreach ( $events as $event ) {
			$count = count( self::consents_for_event( $event->ID ) );
			$date  = get_post_meta( $event->ID, 'event_date', true );
			$url   = add_query_arg( array( 'page' => 'dpp-consents', 'event' => $event->ID ), admin_url( 'admin.php' ) );
			echo '<tr>';
			echo '<td><a href="' . esc_url( $url ) . '">' . esc_html( get_the_title( $event ) ) . '</a></td>';
			echo '<td>' . esc_html( $date ? $date : '—' ) . '</td>';
			echo '<td>' . esc_html( (string) $count ) . '</td>';
			echo '</tr>';
		}
		echo '</tbody></table>';
	}

	private static function render_event_consents_table( $event_id ) {
		$event = get_post( $event_id );
		if ( ! $event || 'event' !== $event->post_type ) {
			echo '<p>' . esc_html__( 'Event not found.', 'davenham-parent-portal' ) . '</p>';
			return;
		}
		$back = add_query_arg( array( 'page' => 'dpp-consents' ), admin_url( 'admin.php' ) );
		echo '<p><a href="' . esc_url( $back ) . '">&larr; ' . esc_html__( 'All events', 'davenham-parent-portal' ) . '</a></p>';
		echo '<h2>' . esc_html( get_the_title( $event ) ) . '</h2>';

		$consents = self::consents_for_event( $event_id );

		$export = wp_nonce_url(
			admin_url( 'admin-post.php?action=dpp_export_consents&event=' . (int) $event_id ),
			'dpp_export_' . (int) $event_id
		);
		echo '<p><a class="button button-primary" href="' . esc_url( $export ) . '">' . esc_html__( 'Download CSV', 'davenham-parent-portal' ) . '</a></p>';

		if ( empty( $consents ) ) {
			echo '<p>' . esc_html__( 'No consents submitted yet.', 'davenham-parent-portal' ) . '</p>';
			return;
		}

		$cols = array( 'Child', 'Section', 'Attending', 'Photos', 'Medical', 'Medications', 'Dietary', 'Additional', 'Emergency contact', 'Signed by', 'Signed at' );
		echo '<table class="widefat striped"><thead><tr>';
		foreach ( $cols as $c ) {
			echo '<th>' . esc_html( $c ) . '</th>';
		}
		echo '</tr></thead><tbody>';
		foreach ( $consents as $consent ) {
			$row = self::consent_row( $consent->ID );
			echo '<tr>';
			foreach ( $row as $cell ) {
				echo '<td>' . esc_html( $cell ) . '</td>';
			}
			echo '</tr>';
		}
		echo '</tbody></table>';
	}

	/**
	 * Flatten one consent post into an ordered row of display strings.
	 */
	private static function consent_row( $consent_id ) {
		$d   = get_post_meta( $consent_id, self::CONSENT_META, true );
		$d   = is_array( $d ) ? $d : array();
		$get = function ( $k ) use ( $d ) {
			return isset( $d[ $k ] ) ? $d[ $k ] : '';
		};
		$emergency = trim( $get( 'emergency_name' ) . ' ' . $get( 'emergency_phone' ) );
		return array(
			(string) get_post_meta( $consent_id, '_dpp_child', true ),
			self::section_label( (string) get_post_meta( $consent_id, '_dpp_section', true ) ),
			'yes' === $get( 'attending' ) ? 'Yes' : 'No',
			'yes' === $get( 'photo_consent' ) ? 'Yes' : 'No',
			$get( 'medical' ),
			$get( 'medications' ),
			$get( 'dietary' ),
			$get( 'additional' ),
			$emergency,
			$get( 'signature' ),
			(string) get_post_meta( $consent_id, '_dpp_signed_at', true ),
		);
	}

	public static function export_consents_csv() {
		$event_id = isset( $_GET['event'] ) ? absint( $_GET['event'] ) : 0;
		if ( ! current_user_can( self::VIEW_CAP ) ) {
			wp_die( esc_html__( 'Not allowed.', 'davenham-parent-portal' ) );
		}
		check_admin_referer( 'dpp_export_' . $event_id );

		$event = get_post( $event_id );
		if ( ! $event || 'event' !== $event->post_type ) {
			wp_die( esc_html__( 'Event not found.', 'davenham-parent-portal' ) );
		}

		$consents = self::consents_for_event( $event_id );
		$filename = 'consents-' . sanitize_title( get_the_title( $event ) ) . '.csv';

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

		while ( ob_get_level() ) {
			@ob_end_clean();
		}

		$out = fopen( 'php://output', 'w' );
		fputcsv( $out, array( 'Child', 'Section', 'Attending', 'Photos', 'Medical', 'Medications', 'Dietary', 'Additional', 'Emergency contact', 'Signed by', 'Signed at' ) );
		foreach ( $consents as $consent ) {
			fputcsv( $out, self::consent_row( $consent->ID ) );
		}
		fclose( $out );
		exit;
	}
}

register_activation_hook( __FILE__, array( 'Davenham_Parent_Portal', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Davenham_Parent_Portal', 'deactivate' ) );

Davenham_Parent_Portal::init();
