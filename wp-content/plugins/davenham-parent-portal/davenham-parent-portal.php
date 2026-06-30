<?php
/**
 * Plugin Name: Davenham Parent Portal
 * Plugin URI:  https://davenhamscouts.org.uk
 * Description: Parents self-register, an admin approves them, and they get a Parent login to a dashboard. Foundation for digital event consent forms.
 * Version:     1.0.0
 * Author:      Davenham Scout Group
 * Text Domain: davenham-parent-portal
 * Requires at least: 6.0
 * Requires PHP: 7.0
 */

defined( 'ABSPATH' ) || exit;

define( 'DPP_VERSION', '1.0.0' );
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

	/**
	 * Single source of truth for sections (slug => label). Filterable so the
	 * volunteer tracker (plugin 2) and events can share it.
	 */
	public static function sections() {
		$sections = array(
			'monday-beavers' => 'Monday Beavers',
			'monday-scouts'  => 'Monday Scouts',
			'tuesday-cubs'   => 'Tuesday Cubs',
			'friday-cubs'    => 'Friday Cubs',
		);
		return apply_filters( 'davenham_sections', $sections );
	}

	public static function section_label( $slug ) {
		$sections = self::sections();
		return isset( $sections[ $slug ] ) ? $sections[ $slug ] : '';
	}

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_application_cpt' ) );
		add_action( 'init', array( __CLASS__, 'register_shortcodes' ) );

		add_action( 'admin_init', array( __CLASS__, 'maybe_upgrade' ) );

		// Front-end registration submit.
		add_action( 'template_redirect', array( __CLASS__, 'handle_registration' ) );

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
		self::create_role();
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
}

register_activation_hook( __FILE__, array( 'Davenham_Parent_Portal', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Davenham_Parent_Portal', 'deactivate' ) );

Davenham_Parent_Portal::init();
