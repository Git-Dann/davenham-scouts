<?php
/**
 * Plugin Name: Davenham Admin Suite
 * Plugin URI:  https://davenhamscouts.org.uk
 * Description: White-label admin customisation, menu cleanup, and editorial polish for Davenham Scouts.
 * Version:     1.6.6
 * Author:      Davenham Scout Group
 * Text Domain: davenham-admin-suite
 */

defined( 'ABSPATH' ) || exit;

define( 'DAS_VERSION', '1.6.6' );
define( 'DAS_FILE', __FILE__ );
define( 'DAS_DIR', plugin_dir_path( __FILE__ ) );
define( 'DAS_URL', plugin_dir_url( __FILE__ ) );

final class Davenham_Admin_Suite {
	const OPTION_NAME = 'davenham_admin_suite_settings';

	private static $fallback_menu_links = [
		'index.php'              => 'Dashboard',
		'edit.php?post_type=page' => 'Pages',
		'edit.php?post_type=event' => 'Events',
		'plugins.php'            => 'Plugins',
		'tools.php'              => 'Tools',
		'update-core.php'        => 'Updates',
		'site-health.php'        => 'Site Health',
		'backuply'               => 'Backuply',
		'loginizer'              => 'Loginizer',
		'fileorganizer'          => 'File Organizer',
		'siteseo'                => 'SiteSEO',
		'kadence-blocks'         => 'Kadence',
		'speedycache'            => 'SpeedyCache',
		'woocommerce'            => 'WooCommerce',
		'davenham-events-funds'  => 'Events & Funds',
		'davenham-admin-suite'   => 'Admin',
		'upload.php'             => 'Media',
		'options-general.php'    => 'Settings',
		'users.php'              => 'Users',
	];

	private static $captured_menu_items = [];

	public static function init() {
		add_action( 'admin_init', [ __CLASS__, 'maybe_upgrade_settings' ], 5 );
		add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
		add_action( 'admin_post_davenham_admin_suite_save', [ __CLASS__, 'handle_save' ] );
		add_action( 'admin_post_davenham_admin_suite_reset_menu', [ __CLASS__, 'handle_reset_menu' ] );
		add_action( 'admin_menu', [ __CLASS__, 'register_admin_hub' ], 5 );
		add_action( 'admin_menu', [ __CLASS__, 'capture_menu_items' ], 998 );
		add_action( 'admin_menu', [ __CLASS__, 'tidy_admin_menus' ], 999 );
		add_action( 'admin_bar_menu', [ __CLASS__, 'replace_wp_logo' ], 11 );
		add_action( 'admin_bar_menu', [ __CLASS__, 'cleanup_admin_bar' ], 999 );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_frontend_assets' ] );
		add_action( 'login_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
		add_action( 'wp_dashboard_setup', [ __CLASS__, 'cleanup_dashboard' ], 25 );
		add_action( 'admin_head', [ __CLASS__, 'suppress_update_ui' ] );
		add_action( 'admin_head', [ __CLASS__, 'admin_shell_head' ] );
		// Inline the critical shell CSS at priority 0 — runs BEFORE
		// wp_print_styles so the layout is applied before first paint,
		// stopping the flash of unstyled WP admin between navigations.
		add_action( 'admin_head', [ __CLASS__, 'print_critical_shell_css' ], 0 );
		add_action( 'in_admin_header', [ __CLASS__, 'render_admin_shell' ], 1 );
		add_filter( 'admin_body_class', [ __CLASS__, 'admin_body_class' ] );
		add_filter( 'admin_footer_text', [ __CLASS__, 'admin_footer_text' ] );
		add_filter( 'update_footer', [ __CLASS__, 'admin_version_text' ], 11 );
		add_filter( 'gettext', [ __CLASS__, 'replace_thank_you_text' ], 20, 3 );
	}

	public static function defaults() {
		return [
			'admin_logo_id'        => 0,
			'admin_logo_url'       => '',
			'admin_bar_label'      => 'Davenham Scouts',
			'footer_text'          => 'Managed by Davenham Scouts.',
			'version_text'         => 'Davenham admin',
			'primary_color'        => '#003982',
			'accent_color'         => '#FF912A',
			'menu_bg_color'        => '#003982',
			'menu_text_color'      => '#F1F1F1',
			'dashboard_welcome'    => 'Welcome to the Davenham Scouts admin area.',
			'admin_shell_enabled'  => '1',
			'admin_density'        => 'comfortable',
			'hide_help_tabs'       => '1',
			'hide_wp_updates'      => '1',
			'menu_groups'          => self::default_groups(),
			'menu_items'           => self::default_menu_items(),
			'custom_links'         => [],
		];
	}

	private static function default_groups() {
		return [
			'technical'    => 'Technical',
			'maintenance'  => 'Maintenance',
			'store'        => 'Store',
			'communications'=> 'Communications',
		];
	}

	private static function default_menu_items() {
		return [
			'index.php' => [
				'label'     => 'Dashboard',
				'group'     => 'communications',
				'placement' => 'keep',
				'icon'      => 'dashboard',
				'order'     => 10,
			],
			'edit.php?post_type=page' => [
				'label'     => 'Pages',
				'group'     => 'communications',
				'placement' => 'keep',
				'icon'      => 'pages',
				'order'     => 40,
			],
			'edit.php?post_type=event' => [
				'label'     => 'Events',
				'group'     => 'store',
				'placement' => 'admin',
				'icon'      => 'calendar',
				'order'     => 35,
			],
			'plugins.php' => [
				'label'     => 'Plugins',
				'group'     => 'technical',
				'placement' => 'admin',
				'icon'      => 'plugins',
				'order'     => 300,
			],
			'tools.php' => [
				'label'     => 'Tools',
				'group'     => 'technical',
				'placement' => 'admin',
				'icon'      => 'tools',
				'order'     => 310,
			],
			'update-core.php' => [
				'label'     => 'Updates',
				'group'     => 'maintenance',
				'placement' => 'admin',
				'icon'      => 'updates',
				'order'     => 290,
			],
			'site-health.php' => [
				'label'     => 'Site Health',
				'group'     => 'maintenance',
				'placement' => 'admin',
				'icon'      => 'health',
				'order'     => 320,
			],
			'backuply' => [
				'label'     => 'Backuply',
				'group'     => 'maintenance',
				'placement' => 'admin',
				'icon'      => 'backup',
				'order'     => 330,
			],
			'loginizer' => [
				'label'     => 'Loginizer',
				'group'     => 'technical',
				'placement' => 'admin',
				'icon'      => 'security',
				'order'     => 340,
			],
			'fileorganizer' => [
				'label'     => 'File Organizer',
				'group'     => 'technical',
				'placement' => 'admin',
				'icon'      => 'folder',
				'order'     => 350,
			],
			'siteseo' => [
				'label'     => 'SiteSEO',
				'group'     => 'communications',
				'placement' => 'admin',
				'icon'      => 'marketing',
				'order'     => 360,
			],
			'kadence-blocks' => [
				'label'     => 'Kadence',
				'group'     => 'technical',
				'placement' => 'keep',
				'icon'      => 'builder',
				'order'     => 220,
			],
			'speedycache' => [
				'label'     => 'SpeedyCache',
				'group'     => 'technical',
				'placement' => 'admin',
				'icon'      => 'speed',
				'order'     => 370,
			],
			'woocommerce' => [
				'label'     => 'WooCommerce',
				'group'     => 'store',
				'placement' => 'keep',
				'icon'      => 'cart',
				'order'     => 90,
			],
			'davenham-events-funds' => [
				'label'     => 'Events & Funds',
				'group'     => 'store',
				'placement' => 'keep',
				'icon'      => 'tickets',
				'order'     => 30,
			],
			'edit.php?post_type=davenham_document' => [
				'label'     => 'Documents',
				'group'     => 'communications',
				'placement' => 'keep',
				'icon'      => 'media',
				'order'     => 60,
			],
			'edit.php?post_type=dpp_application' => [
				'label'     => 'Parent Applications',
				'group'     => 'communications',
				'placement' => 'keep',
				'icon'      => 'pages',
				'order'     => 62,
			],
			'dpp-consents' => [
				'label'     => 'Event Consents',
				'group'     => 'communications',
				'placement' => 'keep',
				'icon'      => 'tickets',
				'order'     => 64,
			],
			'davenham-admin-suite' => [
				'label'     => 'Admin',
				'group'     => 'technical',
				'placement' => 'bottom',
				'icon'      => 'admin',
				'order'     => 900,
				'divider_before' => '1',
			],
			'upload.php' => [
				'label'     => 'Media',
				'group'     => 'communications',
				'placement' => 'keep',
				'icon'      => 'media',
				'order'     => 50,
			],
		];
	}

	private static function utility_links() {
		return [
			'update-core.php' => 'Updates',
			'site-health.php' => 'Site Health',
		];
	}

	public static function settings() {
		$saved    = get_option( self::OPTION_NAME, [] );
		$settings = wp_parse_args( is_array( $saved ) ? $saved : [], self::defaults() );

		$settings['menu_groups'] = self::sanitize_group_values( $settings['menu_groups'] ?? self::default_groups() );
		$settings['menu_items']  = self::normalise_menu_items(
			is_array( $settings['menu_items'] ?? null ) ? $settings['menu_items'] : [],
			$settings['menu_groups']
		);
		$settings['custom_links'] = self::sanitize_custom_links( $settings['custom_links'] ?? [] );

		return $settings;
	}

	public static function register_settings() {
		register_setting(
			'davenham_admin_suite',
			self::OPTION_NAME,
			[
				'type'              => 'array',
				'sanitize_callback' => [ __CLASS__, 'sanitize_settings' ],
				'default'           => self::defaults(),
			]
		);
	}

	public static function maybe_upgrade_settings() {
		$saved = get_option( self::OPTION_NAME, null );
		if ( ! is_array( $saved ) ) {
			return;
		}

		if ( isset( $saved['menu_items']['site-seo-dashboard'] ) && ! isset( $saved['menu_items']['siteseo'] ) ) {
			$saved['menu_items']['siteseo'] = $saved['menu_items']['site-seo-dashboard'];
			unset( $saved['menu_items']['site-seo-dashboard'] );
		}

		$merged = wp_parse_args( $saved, self::defaults() );
		$merged['menu_groups']  = self::sanitize_group_values( $merged['menu_groups'] ?? self::default_groups() );
		$merged['menu_items']   = self::normalise_menu_items( is_array( $merged['menu_items'] ?? null ) ? $merged['menu_items'] : [], $merged['menu_groups'] );
		$merged['custom_links'] = self::sanitize_custom_links( $merged['custom_links'] ?? [] );

		if ( $merged !== $saved ) {
			update_option( self::OPTION_NAME, $merged, false );
		}
	}

	public static function sanitize_settings( $input ) {
		$current = self::settings();
		$clean   = [];

		$clean['admin_logo_id']     = absint( $input['admin_logo_id'] ?? $current['admin_logo_id'] );
		$clean['admin_logo_url']    = esc_url_raw( $input['admin_logo_url'] ?? $current['admin_logo_url'] );
		$clean['admin_bar_label']   = sanitize_text_field( $input['admin_bar_label'] ?? $current['admin_bar_label'] );
		$clean['footer_text']       = sanitize_text_field( $input['footer_text'] ?? $current['footer_text'] );
		$clean['version_text']      = sanitize_text_field( $input['version_text'] ?? $current['version_text'] );
		$clean['primary_color']     = sanitize_hex_color( $input['primary_color'] ?? $current['primary_color'] ) ?: $current['primary_color'];
		$clean['accent_color']      = sanitize_hex_color( $input['accent_color'] ?? $current['accent_color'] ) ?: $current['accent_color'];
		$clean['menu_bg_color']     = sanitize_hex_color( $input['menu_bg_color'] ?? $current['menu_bg_color'] ) ?: $current['menu_bg_color'];
		$clean['menu_text_color']   = sanitize_hex_color( $input['menu_text_color'] ?? $current['menu_text_color'] ) ?: $current['menu_text_color'];
		$clean['dashboard_welcome'] = sanitize_text_field( $input['dashboard_welcome'] ?? $current['dashboard_welcome'] );
		$clean['admin_shell_enabled'] = array_key_exists( 'admin_shell_enabled', $input ) ? ( ! empty( $input['admin_shell_enabled'] ) ? '1' : '0' ) : $current['admin_shell_enabled'];
		$clean['admin_density'] = in_array( ( $input['admin_density'] ?? $current['admin_density'] ), [ 'comfortable', 'compact' ], true ) ? sanitize_key( $input['admin_density'] ?? $current['admin_density'] ) : 'comfortable';
		$clean['hide_help_tabs'] = array_key_exists( 'hide_help_tabs', $input ) ? ( ! empty( $input['hide_help_tabs'] ) ? '1' : '0' ) : $current['hide_help_tabs'];
		$clean['hide_wp_updates']   = array_key_exists( 'hide_wp_updates', $input ) ? ( ! empty( $input['hide_wp_updates'] ) ? '1' : '0' ) : $current['hide_wp_updates'];
		$clean['menu_groups']       = self::sanitize_group_textarea( $input['menu_groups_text'] ?? '', $current['menu_groups'] );
		$clean['menu_items']        = self::sanitize_menu_items(
			$input['menu_items'] ?? [],
			$clean['menu_groups'],
			$current['menu_items']
		);
		$clean['custom_links']      = self::sanitize_custom_links( $input['custom_links'] ?? [] );

		if ( $clean['admin_logo_id'] > 0 ) {
			$logo_url = wp_get_attachment_image_url( $clean['admin_logo_id'], 'full' );
			if ( $logo_url ) {
				$clean['admin_logo_url'] = $logo_url;
			}
		}

		return $clean;
	}

	public static function register_admin_hub() {
		add_menu_page(
			'Davenham Admin',
			'Admin',
			'manage_options',
			'davenham-admin-suite',
			[ __CLASS__, 'render_overview_page' ],
			'dashicons-admin-generic',
			59
		);

		add_submenu_page(
			'davenham-admin-suite',
			'Overview',
			'Overview',
			'manage_options',
			'davenham-admin-suite',
			[ __CLASS__, 'render_overview_page' ]
		);

		add_submenu_page(
			'davenham-admin-suite',
			'Branding',
			'Branding',
			'manage_options',
			'davenham-admin-suite-branding',
			[ __CLASS__, 'render_branding_page' ]
		);

		add_submenu_page(
			'davenham-admin-suite',
			'Menu Builder',
			'Menu Builder',
			'manage_options',
			'davenham-admin-suite-menu-builder',
			[ __CLASS__, 'render_menu_builder_page' ]
		);

		add_submenu_page(
			'davenham-admin-suite',
			'Updates',
			'Updates',
			'manage_options',
			'davenham-admin-suite-updates',
			[ __CLASS__, 'render_updates_page' ]
		);

		foreach ( self::settings()['menu_groups'] as $group_slug => $group_label ) {
			add_submenu_page(
				'davenham-admin-suite',
				$group_label,
				$group_label,
				'manage_options',
				'davenham-admin-suite-group-' . $group_slug,
				function () use ( $group_slug, $group_label ) {
					self::render_group_page( $group_slug, $group_label );
				}
			);
		}
	}

	public static function capture_menu_items() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		global $menu, $submenu;

		$captured = [];
		if ( is_array( $menu ) ) {
			foreach ( $menu as $item ) {
				if ( empty( $item[2] ) ) {
					continue;
				}

				$slug = (string) $item[2];
				if ( 0 === strpos( $slug, 'separator' ) ) {
					continue;
				}

				// $item[1] is the capability required to access this menu.
				// We store it so app_nav_items() can filter out anything the
				// current user can't reach (e.g. plugin pages with elevated
				// caps that show "You need a higher level of permission").
				$cap = isset( $item[1] ) ? (string) $item[1] : 'read';

				$captured[ $slug ] = [
					'label' => self::clean_menu_label( $item[0] ?? $slug ),
					'slug'  => $slug,
					'capability' => $cap,
					'children' => self::captured_submenu_items( $slug, $submenu ),
				];
			}
		}

		foreach ( self::$fallback_menu_links as $slug => $label ) {
			if ( ! isset( $captured[ $slug ] ) ) {
				$captured[ $slug ] = [
					'label' => $label,
					'slug'  => $slug,
					'children' => [],
				];
			}
		}

		self::$captured_menu_items = $captured;
	}

	public static function tidy_admin_menus() {
		if ( ! is_admin() ) {
			return;
		}

		$settings   = self::settings();
		$menu_items = $settings['menu_items'];
		global $menu;

		if ( is_array( $menu ) ) {
			foreach ( $menu as $index => $entry ) {
				$slug = $entry[2] ?? '';
				if ( ! $slug || ! isset( $menu_items[ $slug ] ) ) {
					continue;
				}

				$custom_label = $menu_items[ $slug ]['label'];
				if ( '' !== $custom_label ) {
					$menu[ $index ][0] = $custom_label;
				}
			}
		}

		foreach ( $menu_items as $slug => $item ) {
			if ( in_array( $item['placement'], [ 'keep', 'bottom' ], true ) ) {
				continue;
			}

			remove_menu_page( $slug );

			if ( 'siteseo' === $slug ) {
				remove_menu_page( 'site-seo-dashboard' );
			}
		}

		if ( $settings['hide_wp_updates'] === '1' ) {
			remove_submenu_page( 'index.php', 'update-core.php' );
		}
	}

	public static function replace_wp_logo( WP_Admin_Bar $wp_admin_bar ) {
		$settings = self::settings();
		$logo_url = self::logo_url();
		$logo     = '';

		if ( $logo_url ) {
			$logo = sprintf(
				'<span class="das-admin-logo" style="display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;min-width:20px;max-width:20px;overflow:hidden;margin-right:8px;line-height:0;vertical-align:middle;"><img class="das-admin-logo-image" src="%1$s" alt="" width="20" height="20" style="display:block;width:20px;height:20px;min-width:20px;max-width:20px;max-height:20px;object-fit:contain;" /></span>',
				esc_url( $logo_url )
			);
		} else {
			$logo = '<span class="das-admin-logo das-admin-logo-fallback" aria-hidden="true" style="display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;min-width:20px;max-width:20px;margin-right:8px;line-height:1;font-size:16px;color:#590FA9;">&#9884;</span>';
		}

		$wp_admin_bar->remove_node( 'wp-logo' );
		$wp_admin_bar->add_node(
			[
				'id'    => 'davenham-admin-logo',
				// Link the branding to the dashboard so it's the way into
				// the admin from the front-end toolbar (we remove the
				// default "site-name → Dashboard" node below).
				'href'  => admin_url(),
				'title' => sprintf(
					'%s<span class="ab-label">%s</span>',
					$logo,
					esc_html( $settings['admin_bar_label'] )
				),
				'meta'  => [ 'class' => 'davenham-admin-logo-node' ],
			]
		);
	}

	public static function cleanup_admin_bar( WP_Admin_Bar $wp_admin_bar ) {
		// On the front end, keep a clear route into wp-admin. The default
		// site-name node carries the "Dashboard" sub-link; rather than drop
		// it entirely, re-point its top link straight at the dashboard and
		// relabel it so there's an obvious way in.
		if ( ! is_admin() ) {
			$site_name = $wp_admin_bar->get_node( 'site-name' );
			if ( $site_name ) {
				$wp_admin_bar->add_node(
					[
						'id'    => 'site-name',
						'title' => 'Dashboard',
						'href'  => admin_url(),
					]
				);
			}
		} else {
			$wp_admin_bar->remove_node( 'site-name' );
		}

		if ( self::settings()['hide_wp_updates'] === '1' ) {
			$wp_admin_bar->remove_node( 'updates' );
		}
	}

	public static function enqueue_assets() {
		$settings = self::settings();
		wp_enqueue_style( 'davenham-admin-suite', DAS_URL . 'assets/admin-suite.css', [], DAS_VERSION );
		wp_add_inline_style( 'davenham-admin-suite', self::inline_css( $settings ) );

		$screen  = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$is_hub  = $screen && false !== strpos( $screen->id, 'davenham-admin-suite' );
		$is_shell = isset( $settings['admin_shell_enabled'] ) && '1' === $settings['admin_shell_enabled'];

		if ( $is_hub ) {
			wp_enqueue_media();
		}

		if ( $is_hub || $is_shell ) {
			wp_enqueue_script( 'davenham-admin-suite', DAS_URL . 'assets/admin-suite.js', [ 'jquery' ], DAS_VERSION, true );
			wp_localize_script(
				'davenham-admin-suite',
				'davenhamAdminSuite',
				[
					'brand'       => $settings['admin_bar_label'],
					'logoUrl'     => self::logo_url(),
					'currentUser' => wp_get_current_user()->display_name,
					'nav'         => self::app_nav_items( $settings ),
					'adminGroups' => self::app_admin_groups( $settings ),
				]
			);
		}
	}

	public static function enqueue_frontend_assets() {
		if ( ! is_admin_bar_showing() ) {
			return;
		}

		$settings = self::settings();
		wp_enqueue_style( 'davenham-admin-suite', DAS_URL . 'assets/admin-suite.css', [], DAS_VERSION );
		wp_add_inline_style( 'davenham-admin-suite', self::inline_css( $settings ) );
	}

	public static function cleanup_dashboard() {
		remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );
		remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );
		remove_meta_box( 'dashboard_site_health', 'dashboard', 'normal' );

		wp_add_dashboard_widget(
			'davenham_admin_welcome',
			'Davenham Admin',
			[ __CLASS__, 'render_dashboard_welcome' ]
		);
	}

	public static function suppress_update_ui() {
		if ( self::settings()['hide_wp_updates'] !== '1' ) {
			return;
		}

		remove_action( 'admin_notices', 'update_nag', 3 );

		echo '<style>
			.update-nag,
			.notice.notice-warning[data-dismissible*="update"],
			.notice.notice-info[data-dismissible*="update"],
			.notice[data-dismissible*="update-core"],
			.plugins .plugin-update-tr,
			.themes-php .notice.notice-warning,
			.themes-php .notice.notice-info,
			.core-updates,
			.update-php .wrap > .notice-warning,
			.wp-core-ui .update-count {
				display:none !important;
			}
		</style>';
	}

	public static function admin_shell_head() {
		$settings = self::settings();

		// Hide WP's contextual help + screen-options tabs whenever the
		// admin shell is on — they sit at top:0;right:0 in the WP admin
		// chrome and collide with our shell topbar (the user's screenshot
		// showed "Help ▼" floating in the middle of the page on mobile).
		// Independent of the hide_help_tabs setting because the shell IS
		// the new chrome — these toggles never look right alongside it.
		if ( $settings['admin_shell_enabled'] === '1' || $settings['hide_help_tabs'] === '1' ) {
			echo '<style>#contextual-help-link-wrap,#screen-options-link-wrap{display:none!important;}</style>';
		}
	}

	/**
	 * Print critical shell CSS in <head> BEFORE any other stylesheet
	 * loads. Prevents the flash of native-WP-admin chrome that the
	 * user reported when navigating between menu items.
	 *
	 * The browser paints HTML the moment it has enough to render — if
	 * the main stylesheet is still loading at that point, it renders
	 * the default WP admin look (black sidebar, no topbar) and then
	 * re-paints with our shell once the CSS arrives. By inlining the
	 * minimum set of layout rules here, the shell is correct from the
	 * first paint.
	 */
	public static function print_critical_shell_css() {
		$settings = self::settings();
		if ( $settings['admin_shell_enabled'] !== '1' ) {
			return;
		}
		// Synchronous inline script — sets the html shell-active class
		// BEFORE the browser begins parsing the body. Critical CSS
		// rules can now match from the first paint, avoiding the
		// flash of native WP admin chrome.
		?>
<script>document.documentElement.classList.add('das-app-shell-active');</script>
<style id="das-shell-critical">
/* Hide WP's native admin chrome immediately — no flash of it
   appearing then disappearing on page transitions. */
html.das-app-shell-active body.davenham-admin-shell #adminmenuback,
html.das-app-shell-active body.davenham-admin-shell #adminmenuwrap,
html.das-app-shell-active body.davenham-admin-shell #adminmenu,
html.das-app-shell-active body.davenham-admin-shell #wpadminbar {
  display: none !important;
}
/* Initial body class always treated as shell on — even before our
   admin_body_class filter has been applied to the rendered HTML
   string (defensive against very early paint). */
body.davenham-admin-shell {
  background: #F1F1F1;
}
/* CSS variables used by the rail + topbar — duplicated here so they
   exist before admin-suite.css loads. */
html.das-app-shell-active body.davenham-admin-shell {
  --das-rail-width: 236px;
  --das-rail-collapsed-width: 72px;
  --das-topbar-height: 64px;
}
/* Sidebar layout — fixed position, gradient, full-height */
html.das-app-shell-active body.davenham-admin-shell .das-app-shell {
  position: relative;
  z-index: 99998;
}
html.das-app-shell-active body.davenham-admin-shell .das-app-rail {
  position: fixed;
  top: 0;
  bottom: 0;
  left: 0;
  width: var(--das-rail-width);
  background: linear-gradient(180deg, #003982 0%, #590FA9 100%);
  color: #F1F1F1;
  z-index: 99999;
  display: flex;
  flex-direction: column;
  overflow-y: auto;
}
/* Topbar — fixed at top, right of rail, full width */
html.das-app-shell-active body.davenham-admin-shell .das-app-topbar {
  position: fixed;
  top: 0;
  right: 0;
  left: var(--das-rail-width);
  height: var(--das-topbar-height);
  background: rgba(255, 255, 255, 0.96);
  border-bottom: 1px solid #CCCCCC;
  z-index: 99999;
  display: flex;
  align-items: center;
}
/* Content area — clear sidebar (left margin) + topbar (top padding) */
html.das-app-shell-active body.davenham-admin-shell #wpcontent,
html.das-app-shell-active body.davenham-admin-shell #wpfooter {
  margin-left: var(--das-rail-width) !important;
}
html.das-app-shell-active body.davenham-admin-shell #wpcontent {
  padding-top: calc(var(--das-topbar-height) + 20px) !important;
}
/* Hide flyouts initially — they only show when JS toggles is-open */
html.das-app-shell-active body.davenham-admin-shell .das-app-flyout {
  display: none;
}
html.das-app-shell-active body.davenham-admin-shell .das-app-flyout.is-open {
  display: block;
}
/* Mobile: stash the rail off-canvas + show topbar with hamburger */
@media (max-width: 782px) {
  html.das-app-shell-active body.davenham-admin-shell {
    --das-rail-width: 0px;
  }
  html.das-app-shell-active body.davenham-admin-shell .das-app-rail {
    transform: translateX(-100%);
    width: min(280px, 80vw);
    transition: transform 0.25s ease;
  }
  html.das-app-shell-active body.davenham-admin-shell.das-app-nav-open .das-app-rail {
    transform: translateX(0);
  }
  html.das-app-shell-active body.davenham-admin-shell .das-app-topbar {
    left: 0;
  }
  html.das-app-shell-active body.davenham-admin-shell #wpcontent,
  html.das-app-shell-active body.davenham-admin-shell #wpfooter {
    margin-left: 0 !important;
  }
}
</style>
		<?php
	}

	public static function admin_body_class( $classes ) {
		$settings = self::settings();
		if ( $settings['admin_shell_enabled'] !== '1' ) {
			return $classes;
		}

		$classes .= ' davenham-admin-shell';
		$classes .= ' das-density-' . sanitize_html_class( $settings['admin_density'] );

		return $classes;
	}

	/**
	 * Output the admin shell HTML server-side on every admin page.
	 *
	 * Previously the shell was built entirely by JS in initAdminShell(),
	 * which meant it failed to appear on pages that:
	 *   - don't call body_class() (so the davenham-admin-shell class is missing)
	 *   - take over the body via Backbone (Media Library grid mode, Customizer)
	 *   - run their own JS that bails before our DOMContentLoaded handler fires
	 *
	 * Server-side rendering guarantees the shell exists on every wp-admin page
	 * before any client code runs. The JS now just wires up behaviour
	 * (mobile-menu toggle, collapse, flyout open/close).
	 */
	public static function render_admin_shell() {
		$settings = self::settings();
		if ( $settings['admin_shell_enabled'] !== '1' ) {
			return;
		}

		// Variables exposed to templates/admin-shell.php
		$nav          = self::app_nav_items( $settings );
		$admin_groups = self::app_admin_groups( $settings );
		$brand        = $settings['admin_bar_label'];
		$logo_url     = self::logo_url();
		$user         = wp_get_current_user();
		$current_user = $user ? $user->display_name : '';

		$template = DAS_DIR . 'templates/admin-shell.php';
		if ( file_exists( $template ) ) {
			include $template;
		}
	}

	public static function render_dashboard_welcome() {
		$settings = self::settings();
		$summary  = self::update_summary();

		$quick_links = [
			[ 'label' => 'Pages', 'url' => admin_url( 'edit.php?post_type=page' ) ],
			[ 'label' => 'Media Library', 'url' => admin_url( 'upload.php' ) ],
			[ 'label' => 'Events & Funds', 'url' => admin_url( 'admin.php?page=davenham-events-funds' ) ],
			[ 'label' => 'Orders', 'url' => admin_url( 'edit.php?post_type=shop_order' ) ],
			[ 'label' => 'Admin Settings', 'url' => admin_url( 'admin.php?page=davenham-admin-suite' ) ],
		];

		echo '<div class="das-dashboard-widget">';
		echo '<p><strong>' . esc_html( $settings['dashboard_welcome'] ) . '</strong></p>';
		echo '<p>Use the shortcuts below for the everyday jobs. Technical tools and updates stay tucked inside <strong>Admin</strong>.</p>';
		echo '<div class="das-dashboard-actions">';
		foreach ( $quick_links as $link ) {
			echo '<a class="button button-secondary" href="' . esc_url( $link['url'] ) . '">' . esc_html( $link['label'] ) . '</a>';
		}
		echo '</div>';
		echo '<div class="das-dashboard-status">';
		echo '<span><strong>' . esc_html( (string) $summary['core'] ) . '</strong> core</span>';
		echo '<span><strong>' . esc_html( (string) $summary['plugins'] ) . '</strong> plugins</span>';
		echo '<span><strong>' . esc_html( (string) $summary['themes'] ) . '</strong> themes</span>';
		echo '</div>';
		echo '</div>';
	}

	public static function admin_footer_text() {
		return esc_html( self::settings()['footer_text'] );
	}

	public static function admin_version_text() {
		return esc_html( self::settings()['version_text'] );
	}

	public static function replace_thank_you_text( $translated, $text, $domain ) {
		if ( 'default' === $domain && 'Thank you for creating with WordPress.' === $text ) {
			return self::settings()['footer_text'];
		}

		return $translated;
	}

	public static function handle_save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Not allowed.' );
		}

		check_admin_referer( 'davenham_admin_suite_save' );

		$incoming = isset( $_POST[ self::OPTION_NAME ] ) ? wp_unslash( $_POST[ self::OPTION_NAME ] ) : [];
		$clean    = self::sanitize_settings( is_array( $incoming ) ? $incoming : [] );
		update_option( self::OPTION_NAME, $clean, false );

		$redirect = isset( $_POST['_wp_http_referer'] ) ? wp_unslash( $_POST['_wp_http_referer'] ) : admin_url( 'admin.php?page=davenham-admin-suite' );
		$redirect = add_query_arg( 'updated', '1', $redirect );

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Reset only the menu builder portion of settings (items + groups +
	 * custom links) back to defaults. Keeps branding/colours intact.
	 */
	public static function handle_reset_menu() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Not allowed.' );
		}

		check_admin_referer( 'davenham_admin_suite_reset_menu' );

		$current  = self::settings();
		$defaults = self::defaults();

		$current['menu_groups']  = $defaults['menu_groups'];
		$current['menu_items']   = $defaults['menu_items'];
		$current['custom_links'] = $defaults['custom_links'];

		update_option( self::OPTION_NAME, $current, false );

		$redirect = admin_url( 'admin.php?page=davenham-admin-suite-menu-builder&reset=1' );
		wp_safe_redirect( $redirect );
		exit;
	}

	public static function render_overview_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Not allowed.' );
		}

		$settings = self::settings();
		$summary  = self::update_summary();
		$groups   = self::grouped_admin_links( $settings );
		?>
		<div class="wrap davenham-admin-suite">
			<h1>Davenham Admin</h1>
			<p>Use this area for the technical side of the site while keeping the everyday workspace lighter for everyone else.</p>
			<?php self::render_updated_notice(); ?>
			<div class="das-overview-grid">
				<div class="das-card">
					<h2>Updates</h2>
					<ul class="das-summary-list">
						<li><strong>Core:</strong> <?php echo esc_html( (string) $summary['core'] ); ?></li>
						<li><strong>Plugins:</strong> <?php echo esc_html( (string) $summary['plugins'] ); ?></li>
						<li><strong>Themes:</strong> <?php echo esc_html( (string) $summary['themes'] ); ?></li>
					</ul>
					<p><a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=davenham-admin-suite-updates' ) ); ?>">Review updates</a></p>
				</div>
				<div class="das-card">
					<h2>Admin Builder</h2>
					<p>Rename sidebar items, move them into Admin groups, hide them completely, and add your own links for the team.</p>
					<p><a class="button button-secondary" href="<?php echo esc_url( admin_url( 'admin.php?page=davenham-admin-suite-menu-builder' ) ); ?>">Open menu builder</a></p>
				</div>
			</div>

			<div class="das-folder-grid">
				<?php foreach ( $groups as $group_slug => $group ) : ?>
					<div class="das-folder-card">
						<div class="das-folder-card__icon"><span class="dashicons dashicons-portfolio" aria-hidden="true"></span></div>
						<h2><?php echo esc_html( $group['label'] ); ?></h2>
						<p><?php echo esc_html( sprintf( _n( '%d admin item', '%d admin items', count( $group['links'] ), 'davenham-admin-suite' ), count( $group['links'] ) ) ); ?></p>
						<?php if ( empty( $group['links'] ) ) : ?>
							<span class="das-folder-empty">No links assigned</span>
						<?php else : ?>
							<ul class="das-admin-links">
								<?php foreach ( $group['links'] as $link ) : ?>
									<li><a href="<?php echo esc_url( $link['url'] ); ?>"><?php echo esc_html( $link['label'] ); ?></a></li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	public static function render_branding_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Not allowed.' );
		}

		$settings = self::settings();
		?>
		<div class="wrap davenham-admin-suite">
			<h1>Branding</h1>
			<p>Control the admin logo, footer copy, and overall colour treatment.</p>
			<?php self::render_updated_notice(); ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'davenham_admin_suite_save' ); ?>
				<input type="hidden" name="action" value="davenham_admin_suite_save">
				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row"><label for="das_admin_logo_url">Admin Logo</label></th>
							<td>
								<div class="das-media-field">
									<input type="hidden" id="das_admin_logo_id" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[admin_logo_id]" value="<?php echo esc_attr( (string) $settings['admin_logo_id'] ); ?>">
									<input type="url" class="regular-text" id="das_admin_logo_url" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[admin_logo_url]" value="<?php echo esc_attr( $settings['admin_logo_url'] ); ?>">
									<button type="button" class="button button-secondary das-media-open" data-target="#das_admin_logo_url" data-id-target="#das_admin_logo_id">Choose image</button>
									<button type="button" class="button button-link-delete das-media-clear" data-target="#das_admin_logo_url" data-id-target="#das_admin_logo_id">Remove</button>
								</div>
								<p class="description">Choose an image from the WordPress media library. If blank, the site icon is used, then the active theme screenshot as a fallback.</p>
								<?php if ( ! empty( $settings['admin_logo_url'] ) ) : ?>
									<p class="das-logo-preview"><img src="<?php echo esc_url( $settings['admin_logo_url'] ); ?>" alt="" /></p>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="das_admin_bar_label">Admin Bar Label</label></th>
							<td><input type="text" class="regular-text" id="das_admin_bar_label" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[admin_bar_label]" value="<?php echo esc_attr( $settings['admin_bar_label'] ); ?>"></td>
						</tr>
						<tr>
							<th scope="row"><label for="das_footer_text">Footer Text</label></th>
							<td><input type="text" class="regular-text" id="das_footer_text" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[footer_text]" value="<?php echo esc_attr( $settings['footer_text'] ); ?>"></td>
						</tr>
						<tr>
							<th scope="row"><label for="das_version_text">Footer Right Text</label></th>
							<td><input type="text" class="regular-text" id="das_version_text" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[version_text]" value="<?php echo esc_attr( $settings['version_text'] ); ?>"></td>
						</tr>
						<tr>
							<th scope="row"><label for="das_dashboard_welcome">Dashboard Welcome</label></th>
							<td><input type="text" class="large-text" id="das_dashboard_welcome" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[dashboard_welcome]" value="<?php echo esc_attr( $settings['dashboard_welcome'] ); ?>"></td>
						</tr>
						<tr>
							<th scope="row">Admin Experience</th>
							<td>
								<p><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[admin_shell_enabled]" value="1" <?php checked( $settings['admin_shell_enabled'], '1' ); ?>> Use the Davenham custom admin shell.</label></p>
								<p><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[hide_help_tabs]" value="1" <?php checked( $settings['hide_help_tabs'], '1' ); ?>> Hide WordPress Help and Screen Options tabs for a cleaner workspace.</label></p>
								<label for="das_admin_density">Admin density</label>
								<select id="das_admin_density" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[admin_density]">
									<option value="comfortable" <?php selected( $settings['admin_density'], 'comfortable' ); ?>>Comfortable</option>
									<option value="compact" <?php selected( $settings['admin_density'], 'compact' ); ?>>Compact</option>
								</select>
								<p class="description">Comfortable is easier for occasional editors and touch devices. Compact gives technical users more on-screen rows.</p>
							</td>
						</tr>
						<tr>
							<th scope="row">Admin Colours</th>
							<td class="das-color-grid">
								<label>Primary <input type="color" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[primary_color]" value="<?php echo esc_attr( $settings['primary_color'] ); ?>"></label>
								<label>Accent <input type="color" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[accent_color]" value="<?php echo esc_attr( $settings['accent_color'] ); ?>"></label>
								<label>Menu Background <input type="color" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[menu_bg_color]" value="<?php echo esc_attr( $settings['menu_bg_color'] ); ?>"></label>
								<label>Menu Text <input type="color" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[menu_text_color]" value="<?php echo esc_attr( $settings['menu_text_color'] ); ?>"></label>
							</td>
						</tr>
					</tbody>
				</table>
				<?php submit_button( 'Save Branding' ); ?>
			</form>
		</div>
		<?php
	}

	public static function render_menu_builder_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Not allowed.' );
		}

		$settings = self::settings();
		$catalog  = self::available_menu_catalog();
		$items    = $settings['menu_items'];
		$groups   = $settings['menu_groups'];

		// Pre-sort catalog by saved order so the cards render in their
		// current sidebar order; missing items fall back to 500/alphabetical.
		uksort(
			$catalog,
			function ( $left, $right ) use ( $items, $catalog ) {
				$lo = isset( $items[ $left ]['order'] ) ? (int) $items[ $left ]['order'] : 500;
				$ro = isset( $items[ $right ]['order'] ) ? (int) $items[ $right ]['order'] : 500;
				if ( $lo === $ro ) {
					return strcasecmp( $catalog[ $left ]['label'], $catalog[ $right ]['label'] );
				}
				return $lo <=> $ro;
			}
		);

		// Bucket catalog items by their saved placement so each section can
		// render its own card list. Items the saved config calls "admin"
		// further subdivide by group; everything else lands under the
		// placement bucket only.
		$buckets = [
			'keep'   => [],
			'bottom' => [],
			'admin'  => [],
			'hide'   => [],
		];
		foreach ( $catalog as $slug => $item ) {
			$config    = isset( $items[ $slug ] ) ? $items[ $slug ] : null;
			$placement = $config && isset( $config['placement'] ) ? $config['placement'] : 'keep';
			if ( ! isset( $buckets[ $placement ] ) ) {
				$placement = 'keep';
			}
			$buckets[ $placement ][ $slug ] = [ 'catalog' => $item, 'config' => $config ];
		}

		$counts = [
			'keep'   => count( $buckets['keep'] ),
			'bottom' => count( $buckets['bottom'] ),
			'admin'  => count( $buckets['admin'] ),
			'hide'   => count( $buckets['hide'] ),
			'custom' => count( $settings['custom_links'] ),
		];

		$reset_url = wp_nonce_url(
			admin_url( 'admin-post.php?action=davenham_admin_suite_reset_menu' ),
			'davenham_admin_suite_reset_menu'
		);

		$reset_notice = isset( $_GET['reset'] ) && '1' === (string) $_GET['reset'];
		?>
		<div class="wrap davenham-admin-suite das-menu-builder">
			<div class="das-mb-header">
				<div>
					<h1>Menu Builder</h1>
					<p class="das-mb-lede">Drag items between sections to control the sidebar. Click an icon to change it. Rename anything inline. Changes save in one click.</p>
				</div>
				<div class="das-mb-counts" aria-live="polite">
					<span class="das-mb-count" data-das-count="keep"><strong><?php echo (int) $counts['keep']; ?></strong> Main</span>
					<span class="das-mb-count" data-das-count="bottom"><strong><?php echo (int) $counts['bottom']; ?></strong> Bottom</span>
					<span class="das-mb-count" data-das-count="admin"><strong><?php echo (int) $counts['admin']; ?></strong> Admin</span>
					<span class="das-mb-count" data-das-count="hide"><strong><?php echo (int) $counts['hide']; ?></strong> Hidden</span>
					<span class="das-mb-count" data-das-count="custom"><strong><?php echo (int) $counts['custom']; ?></strong> Custom</span>
				</div>
			</div>

			<?php self::render_updated_notice(); ?>
			<?php if ( $reset_notice ) : ?>
				<div class="notice notice-success"><p>Menu builder restored to defaults.</p></div>
			<?php endif; ?>

			<div class="das-mb-toolbar">
				<label class="das-mb-search">
					<span class="dashicons dashicons-search" aria-hidden="true"></span>
					<input type="search" id="das-menu-search" placeholder="Search menu items…" autocomplete="off">
				</label>
				<a class="button button-link-delete das-mb-reset" href="<?php echo esc_url( $reset_url ); ?>" data-das-confirm="Reset the sidebar to defaults? Your icons, labels, and folders for menu items will be discarded. Branding and colours stay.">Reset to defaults</a>
			</div>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="das-mb-form" id="das-menu-builder-form">
				<?php wp_nonce_field( 'davenham_admin_suite_save' ); ?>
				<input type="hidden" name="action" value="davenham_admin_suite_save">

				<section class="das-mb-section das-mb-folders">
					<header class="das-mb-section-head">
						<h2>Admin folders</h2>
						<p class="description">Items set to <em>Admin flyout</em> live inside one of these folders.</p>
					</header>
					<div class="das-folder-chips" data-das-folder-chips>
						<?php foreach ( $groups as $group_slug => $group_label ) : ?>
							<span class="das-folder-chip" data-das-folder-chip>
								<span class="dashicons dashicons-portfolio" aria-hidden="true"></span>
								<input type="text" class="das-folder-chip-input" value="<?php echo esc_attr( $group_label ); ?>" aria-label="Folder name">
								<button type="button" class="das-folder-chip-remove" aria-label="Remove folder">&times;</button>
							</span>
						<?php endforeach; ?>
						<button type="button" class="button button-secondary das-add-folder">+ Add folder</button>
					</div>
					<textarea hidden name="<?php echo esc_attr( self::OPTION_NAME ); ?>[menu_groups_text]" id="das-folders-mirror"><?php echo esc_textarea( self::groups_textarea_value( $groups ) ); ?></textarea>
				</section>

				<?php
				$sections = [
					'keep'   => [ 'title' => 'Main sidebar', 'help' => 'Items here show in the main sidebar.' ],
					'bottom' => [ 'title' => 'Bottom sidebar', 'help' => 'Pushed to the bottom of the sidebar (useful for settings, logout, etc.).' ],
					'admin'  => [ 'title' => 'Admin flyout', 'help' => 'Items pile inside the Admin folder dropdown, grouped by folder.' ],
					'hide'   => [ 'title' => 'Hidden', 'help' => 'Items here are removed from the sidebar entirely.' ],
				];
				foreach ( $sections as $bucket_key => $meta ) :
					?>
					<section class="das-mb-section das-mb-bucket das-mb-bucket--<?php echo esc_attr( $bucket_key ); ?>" data-das-bucket="<?php echo esc_attr( $bucket_key ); ?>">
						<header class="das-mb-section-head">
							<h2><?php echo esc_html( $meta['title'] ); ?> <span class="das-mb-bucket-count" data-das-bucket-count><?php echo (int) $counts[ $bucket_key ]; ?></span></h2>
							<p class="description"><?php echo esc_html( $meta['help'] ); ?></p>
						</header>
						<div class="das-mb-cards" data-das-drop-zone>
							<?php
							if ( empty( $buckets[ $bucket_key ] ) ) {
								echo '<p class="das-mb-empty">Drop items here.</p>';
							} else {
								foreach ( $buckets[ $bucket_key ] as $slug => $entry ) {
									self::render_menu_item_card( $slug, $entry['catalog'], $entry['config'], $groups );
								}
							}
							?>
						</div>
					</section>
					<?php
				endforeach;
				?>

				<section class="das-mb-section das-mb-custom" data-das-custom-section>
					<header class="das-mb-section-head">
						<div>
							<h2>Custom links <span class="das-mb-bucket-count" data-das-custom-count><?php echo (int) $counts['custom']; ?></span></h2>
							<p class="description">Add quick shortcuts to plugin screens, docs, or external tools.</p>
						</div>
						<button type="button" class="button button-secondary das-add-custom-link">+ Add custom link</button>
					</header>
					<div class="das-mb-cards das-mb-cards--custom" data-das-custom-rows data-next-index="<?php echo esc_attr( (string) count( $settings['custom_links'] ) ); ?>">
						<?php foreach ( $settings['custom_links'] as $index => $link ) : ?>
							<?php self::render_custom_link_card( $index, $link, $groups ); ?>
						<?php endforeach; ?>
					</div>
					<script type="text/template" id="das-custom-link-template">
						<?php
						self::render_custom_link_card(
							'__INDEX__',
							[ 'label' => '', 'url' => '', 'group' => self::default_group_slug( $groups ), 'icon' => 'pin', 'order' => 500 ],
							$groups
						);
						?>
					</script>
				</section>

				<section class="das-mb-section das-mb-section--extras">
					<label class="das-compact-check">
						<input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[hide_wp_updates]" value="1" <?php checked( $settings['hide_wp_updates'], '1' ); ?>>
						Hide update banners and update shortcuts outside the Admin area.
					</label>
				</section>

				<div class="das-mb-savebar">
					<div class="das-mb-savebar__status" data-das-dirty-status>No changes yet</div>
					<div class="das-mb-savebar__actions">
						<button type="button" class="button button-secondary das-mb-discard">Discard changes</button>
						<?php submit_button( 'Save menu', 'primary', 'submit', false ); ?>
					</div>
				</div>
			</form>

			<?php self::render_icon_popover_grid(); ?>
		</div>
		<?php
	}

	/**
	 * One menu item card. Used inside each section's drop-zone. Stores
	 * placement / group / order / icon / label / divider as hidden inputs
	 * so the existing sanitize_menu_items() pipeline still works unchanged.
	 */
	private static function render_menu_item_card( $slug, $catalog_item, $config, $groups ) {
		if ( ! is_array( $config ) ) {
			$config = [
				'label'          => $catalog_item['label'],
				'group'          => self::default_group_slug( $groups ),
				'placement'      => 'keep',
				'icon'           => self::menu_icon_key( $slug, $catalog_item['label'] ),
				'order'          => 500,
				'divider_before' => '0',
			];
		}

		$prefix      = self::OPTION_NAME . '[menu_items][' . $slug . ']';
		$icon_key    = self::valid_icon_key( $config['icon'] ?? self::menu_icon_key( $slug, $catalog_item['label'] ) );
		$icon_class  = self::icon_dashicon( $icon_key );
		$url         = self::menu_slug_url( $slug );
		$placement   = isset( $config['placement'] ) ? $config['placement'] : 'keep';
		$group_value = isset( $config['group'] ) ? $config['group'] : self::default_group_slug( $groups );
		$divider_on  = ! empty( $config['divider_before'] ) && '1' === (string) $config['divider_before'];
		$child_count = ! empty( $catalog_item['children'] ) && is_array( $catalog_item['children'] ) ? count( $catalog_item['children'] ) : 0;

		$search_text = strtolower( $config['label'] . ' ' . $catalog_item['label'] . ' ' . $slug );
		?>
		<div class="das-mb-card" draggable="true" data-das-card data-das-slug="<?php echo esc_attr( $slug ); ?>" data-das-placement="<?php echo esc_attr( $placement ); ?>" data-das-search="<?php echo esc_attr( $search_text ); ?>">
			<button type="button" class="das-mb-card__handle" aria-label="Drag <?php echo esc_attr( $config['label'] ); ?>" tabindex="-1"><span class="dashicons dashicons-menu" aria-hidden="true"></span></button>

			<button type="button" class="das-mb-card__icon" data-das-icon-trigger aria-label="Change icon">
				<span class="dashicons <?php echo esc_attr( $icon_class ); ?>" aria-hidden="true"></span>
			</button>
			<input type="hidden" class="das-mb-icon-value" name="<?php echo esc_attr( $prefix ); ?>[icon]" value="<?php echo esc_attr( $icon_key ); ?>">

			<div class="das-mb-card__body">
				<input type="text" class="das-mb-card__label" name="<?php echo esc_attr( $prefix ); ?>[label]" value="<?php echo esc_attr( $config['label'] ); ?>" placeholder="<?php echo esc_attr( $catalog_item['label'] ); ?>" aria-label="Display label">
				<div class="das-mb-card__meta">
					<?php if ( ! empty( $url ) ) : ?>
						<span class="das-mb-card__url" title="<?php echo esc_attr( $url ); ?>"><?php echo esc_html( self::shorten_admin_url( $url ) ); ?></span>
					<?php endif; ?>
					<?php if ( $child_count ) : ?>
						<span class="das-mb-card__children"><?php echo (int) $child_count; ?> sub-items</span>
					<?php endif; ?>
					<span class="das-mb-card__slug"><code><?php echo esc_html( $slug ); ?></code></span>
				</div>
			</div>

			<div class="das-mb-card__controls">
				<select class="das-mb-placement" name="<?php echo esc_attr( $prefix ); ?>[placement]" data-das-placement-select>
					<option value="keep" <?php selected( $placement, 'keep' ); ?>>Main</option>
					<option value="bottom" <?php selected( $placement, 'bottom' ); ?>>Bottom</option>
					<option value="admin" <?php selected( $placement, 'admin' ); ?>>Admin</option>
					<option value="hide" <?php selected( $placement, 'hide' ); ?>>Hidden</option>
				</select>
				<select class="das-mb-group" name="<?php echo esc_attr( $prefix ); ?>[group]" data-das-group-select <?php echo 'admin' === $placement ? '' : 'hidden'; ?>>
					<?php foreach ( $groups as $g_slug => $g_label ) : ?>
						<option value="<?php echo esc_attr( $g_slug ); ?>" <?php selected( $group_value, $g_slug ); ?>><?php echo esc_html( $g_label ); ?></option>
					<?php endforeach; ?>
				</select>
				<label class="das-mb-card__divider" title="Show a divider line above this item">
					<input type="checkbox" name="<?php echo esc_attr( $prefix ); ?>[divider_before]" value="1" <?php checked( $divider_on ); ?>>
					<span>Divider</span>
				</label>
			</div>

			<input type="hidden" class="das-mb-order" name="<?php echo esc_attr( $prefix ); ?>[order]" value="<?php echo esc_attr( (string) (int) ( $config['order'] ?? 500 ) ); ?>">
		</div>
		<?php
	}

	/** Custom-link card variant (label + URL + icon + group + drag). */
	private static function render_custom_link_card( $index, $link, $groups ) {
		$prefix      = self::OPTION_NAME . '[custom_links][' . $index . ']';
		$icon_key    = self::valid_icon_key( $link['icon'] ?? 'pin' );
		$icon_class  = self::icon_dashicon( $icon_key );
		$group_value = isset( $link['group'] ) ? $link['group'] : self::default_group_slug( $groups );
		$search_text = strtolower( ( $link['label'] ?? '' ) . ' ' . ( $link['url'] ?? '' ) );
		?>
		<div class="das-mb-card das-mb-card--custom" draggable="true" data-das-card data-das-custom data-das-search="<?php echo esc_attr( $search_text ); ?>">
			<button type="button" class="das-mb-card__handle" aria-label="Drag link" tabindex="-1"><span class="dashicons dashicons-menu" aria-hidden="true"></span></button>

			<button type="button" class="das-mb-card__icon" data-das-icon-trigger aria-label="Change icon">
				<span class="dashicons <?php echo esc_attr( $icon_class ); ?>" aria-hidden="true"></span>
			</button>
			<input type="hidden" class="das-mb-icon-value" name="<?php echo esc_attr( $prefix ); ?>[icon]" value="<?php echo esc_attr( $icon_key ); ?>">

			<div class="das-mb-card__body">
				<input type="text" class="das-mb-card__label" name="<?php echo esc_attr( $prefix ); ?>[label]" value="<?php echo esc_attr( $link['label'] ?? '' ); ?>" placeholder="Label (e.g. Site Health)" aria-label="Link label">
				<input type="text" class="das-mb-card__url-input" name="<?php echo esc_attr( $prefix ); ?>[url]" value="<?php echo esc_attr( $link['url'] ?? '' ); ?>" placeholder="plugins.php or https://…" aria-label="Link URL">
			</div>

			<div class="das-mb-card__controls">
				<select class="das-mb-group" name="<?php echo esc_attr( $prefix ); ?>[group]">
					<?php foreach ( $groups as $g_slug => $g_label ) : ?>
						<option value="<?php echo esc_attr( $g_slug ); ?>" <?php selected( $group_value, $g_slug ); ?>><?php echo esc_html( $g_label ); ?></option>
					<?php endforeach; ?>
				</select>
				<button type="button" class="button-link-delete das-remove-custom-link" aria-label="Remove link">Remove</button>
			</div>

			<input type="hidden" class="das-mb-order" name="<?php echo esc_attr( $prefix ); ?>[order]" value="<?php echo esc_attr( (string) (int) ( $link['order'] ?? 500 ) ); ?>">
		</div>
		<?php
	}

	/**
	 * Visual icon picker popover. Rendered once at the bottom of the page;
	 * JS positions and shows it next to the card whose icon button was
	 * clicked. Replaces the dropdown <select> with glyphs you can see.
	 */
	private static function render_icon_popover_grid() {
		?>
		<div class="das-icon-popover" id="das-icon-popover" hidden role="dialog" aria-label="Choose icon">
			<div class="das-icon-popover__grid">
				<?php foreach ( self::icon_choices() as $icon_key => $label ) : ?>
					<button type="button" class="das-icon-popover__btn" data-das-icon-key="<?php echo esc_attr( $icon_key ); ?>" title="<?php echo esc_attr( $label ); ?>" aria-label="<?php echo esc_attr( $label ); ?>">
						<span class="dashicons <?php echo esc_attr( self::icon_dashicon( $icon_key ) ); ?>" aria-hidden="true"></span>
					</button>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/** Trim an admin URL to a short caption like "admin.php?page=foo". */
	private static function shorten_admin_url( $url ) {
		$url = (string) $url;
		$admin_base = admin_url();
		if ( 0 === strpos( $url, $admin_base ) ) {
			$short = substr( $url, strlen( $admin_base ) );
			return '/' . ltrim( $short, '/' );
		}
		return $url;
	}

	public static function render_updates_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Not allowed.' );
		}

		$core_updates   = get_core_updates( [ 'dismissed' => false ] );
		$plugin_updates = get_plugin_updates();
		$theme_updates  = get_theme_updates();
		?>
		<div class="wrap davenham-admin-suite">
			<h1>Updates</h1>
			<p>Update information stays here so other users do not accidentally apply changes from notice banners.</p>
			<div class="das-overview-grid">
				<div class="das-card">
					<h2>Core</h2>
					<p><?php echo ! empty( $core_updates ) ? esc_html( (string) count( $core_updates ) ) . ' update item(s) available.' : 'WordPress core is up to date.'; ?></p>
				</div>
				<div class="das-card">
					<h2>Plugins</h2>
					<p><?php echo ! empty( $plugin_updates ) ? esc_html( (string) count( $plugin_updates ) ) . ' plugin update(s) available.' : 'Plugins are up to date.'; ?></p>
				</div>
				<div class="das-card">
					<h2>Themes</h2>
					<p><?php echo ! empty( $theme_updates ) ? esc_html( (string) count( $theme_updates ) ) . ' theme update(s) available.' : 'Themes are up to date.'; ?></p>
				</div>
			</div>

			<?php if ( ! empty( $plugin_updates ) ) : ?>
				<h2>Plugin Updates</h2>
				<table class="widefat striped">
					<thead><tr><th>Plugin</th><th>Version</th><th>Next step</th></tr></thead>
					<tbody>
						<?php foreach ( $plugin_updates as $plugin_file => $plugin ) : ?>
							<tr>
								<td><?php echo esc_html( $plugin['Name'] ?? $plugin_file ); ?></td>
								<td><?php echo esc_html( (string) ( $plugin['Version'] ?? '' ) ); ?> -> <?php echo esc_html( (string) ( $plugin['update']->new_version ?? '' ) ); ?></td>
								<td><a class="button button-secondary" href="<?php echo esc_url( admin_url( 'plugins.php' ) ); ?>">Review in Plugins</a></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<?php if ( ! empty( $theme_updates ) ) : ?>
				<h2>Theme Updates</h2>
				<table class="widefat striped">
					<thead><tr><th>Theme</th><th>Version</th></tr></thead>
					<tbody>
						<?php foreach ( $theme_updates as $theme_slug => $theme ) : ?>
							<tr>
								<td><?php echo esc_html( $theme['Name'] ?? $theme_slug ); ?></td>
								<td><?php echo esc_html( (string) ( $theme['Version'] ?? '' ) ); ?> -> <?php echo esc_html( (string) ( $theme['update']['new_version'] ?? '' ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<p><a class="button button-primary" href="<?php echo esc_url( admin_url( 'update-core.php' ) ); ?>">Open WordPress update screen</a></p>
		</div>
		<?php
	}

	public static function render_group_page( $group_slug, $group_label ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Not allowed.' );
		}

		$groups = self::grouped_admin_links( self::settings() );
		$group  = $groups[ $group_slug ] ?? [ 'label' => $group_label, 'links' => [] ];
		?>
		<div class="wrap davenham-admin-suite">
			<h1><?php echo esc_html( $group['label'] ); ?></h1>
			<?php if ( empty( $group['links'] ) ) : ?>
				<div class="das-card"><p>No links are currently assigned to this folder.</p></div>
			<?php else : ?>
				<ul class="das-admin-links das-admin-links--folders">
					<?php foreach ( $group['links'] as $link ) : ?>
						<li><a href="<?php echo esc_url( $link['url'] ); ?>"><span class="dashicons dashicons-admin-generic" aria-hidden="true"></span><?php echo esc_html( $link['label'] ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php
	}

	private static function render_updated_notice() {
		if ( isset( $_GET['updated'] ) ) {
			echo '<div class="notice notice-success"><p>Admin settings saved.</p></div>';
		}
	}

	private static function grouped_admin_links( $settings ) {
		$groups = [];
		foreach ( $settings['menu_groups'] as $group_slug => $group_label ) {
			$groups[ $group_slug ] = [
				'label' => $group_label,
				'links' => [],
			];
		}

		foreach ( $settings['menu_items'] as $slug => $item ) {
			if ( 'admin' !== $item['placement'] ) {
				continue;
			}

			if ( ! isset( $groups[ $item['group'] ] ) ) {
				$groups[ $item['group'] ] = [
					'label' => $item['group'],
					'links' => [],
				];
			}

			$groups[ $item['group'] ]['links'][] = [
				'label' => $item['label'],
				'url'   => self::menu_slug_url( $slug ),
			];
		}

		foreach ( $settings['custom_links'] as $link ) {
			if ( empty( $link['label'] ) || empty( $link['url'] ) ) {
				continue;
			}

			if ( ! isset( $groups[ $link['group'] ] ) ) {
				$groups[ $link['group'] ] = [
					'label' => $link['group'],
					'links' => [],
				];
			}

			$groups[ $link['group'] ]['links'][] = [
				'label' => $link['label'],
				'url'   => self::normalise_link_url( $link['url'] ),
			];
		}

		return $groups;
	}

	private static function captured_submenu_items( $parent_slug, $submenu ) {
		$children = [];
		if ( ! is_array( $submenu ) || empty( $submenu[ $parent_slug ] ) || ! is_array( $submenu[ $parent_slug ] ) ) {
			return $children;
		}

		foreach ( $submenu[ $parent_slug ] as $child ) {
			if ( empty( $child[2] ) ) {
				continue;
			}

			$child_slug = (string) $child[2];
			$label      = self::clean_menu_label( $child[0] ?? $child_slug );
			if ( '' === $label ) {
				continue;
			}

			$children[] = [
				'label' => $label,
				'url'   => self::menu_slug_url( $child_slug ),
			];
		}

		return $children;
	}

	private static function app_nav_items( $settings ) {
		$items        = [];
		$catalog      = self::available_menu_catalog();
		$visible_keys = [];

		// Known-dead admin pages that get captured by capture_menu_items
		// but fail their own internal checks when clicked. Filter at the
		// nav-building stage so they don't appear in the sidebar at all.
		$dead_slugs = array(
			'link-manager.php',                          // Legacy Link Manager (removed in WP 3.5)
			'edit-tags.php?taxonomy=link_category',      // Legacy link taxonomy
			'edit.php?post_type=link',                   // Some plugins register this dead variant
		);

		foreach ( $settings['menu_items'] as $slug => $item ) {
			$placement = $item['placement'] ?? 'keep';
			if ( ! in_array( $placement, [ 'keep', 'bottom' ], true ) ) {
				continue;
			}

			if ( 'edit.php?post_type=event' === $slug && isset( $settings['menu_items']['davenham-events-funds'] ) && in_array( $settings['menu_items']['davenham-events-funds']['placement'] ?? '', [ 'keep', 'bottom' ], true ) ) {
				continue;
			}

			// Skip slugs that are known to error even with the right cap
			// (their pages do their own internal checks that the captured
			// cap alone doesn't reflect — e.g. the Link Manager pages
			// require the legacy plugin to be active).
			if ( in_array( $slug, $dead_slugs, true ) ) {
				continue;
			}
			// Also catch any variant that contains link_category or
			// link-manager — broad enough to block other plugin slugs
			// that pile onto the same dead surface.
			if ( false !== stripos( $slug, 'link_category' ) || false !== stripos( $slug, 'link-manager' ) ) {
				continue;
			}

			// Skip items the current user can't access — captured menus
			// can include plugin pages with elevated capability checks
			// (Links / Backup tools) that show "higher permission required"
			// even to admins when the cap isn't a WP-default one.
			$cap = isset( $catalog[ $slug ]['capability'] ) ? $catalog[ $slug ]['capability'] : '';
			if ( $cap && ! current_user_can( $cap ) ) {
				continue;
			}

			$visible_keys[] = $slug;
			$children       = isset( $catalog[ $slug ]['children'] ) && is_array( $catalog[ $slug ]['children'] ) ? $catalog[ $slug ]['children'] : [];
			$kind           = 'davenham-admin-suite' === $slug ? 'admin-tools' : 'link';

			// A stored icon of 'pin' (or empty) is the generic fallback, not a
			// deliberate choice — re-derive a real dashicon from the slug/label
			// so captured menus (Products, Payments, Posts, Users, …) don't all
			// render as the same blank circle. An explicitly chosen icon wins.
			$stored_icon = isset( $item['icon'] ) ? (string) $item['icon'] : '';
			$icon_key    = ( '' === $stored_icon || 'pin' === $stored_icon )
				? self::menu_icon_key( $slug, $item['label'] )
				: $stored_icon;

			$items[] = [
				'slug'          => $slug,
				'label'         => $item['label'],
				'url'           => self::menu_slug_url( $slug ),
				'icon'          => self::valid_icon_key( $icon_key ),
				'placement'     => $placement,
				'order'         => (int) ( $item['order'] ?? 500 ),
				'dividerBefore' => ! empty( $item['divider_before'] ),
				'kind'          => $kind,
				'children'      => $children,
			];
		}

		if ( ! in_array( 'davenham-admin-suite', $visible_keys, true ) ) {
			$admin_item = $settings['menu_items']['davenham-admin-suite'] ?? [
				'label'     => 'Admin',
				'placement' => 'bottom',
				'icon'      => 'admin',
				'order'     => 900,
			];

			$items[] = [
				'slug'          => 'davenham-admin-suite',
				'label'         => $admin_item['label'] ?? 'Admin',
				'url'           => admin_url( 'admin.php?page=davenham-admin-suite' ),
				'icon'          => self::valid_icon_key( $admin_item['icon'] ?? 'admin' ),
				'placement'     => 'bottom',
				'order'         => (int) ( $admin_item['order'] ?? 900 ),
				'dividerBefore' => true,
				'kind'          => 'admin-tools',
				'children'      => [],
			];
		}

		usort(
			$items,
			function ( $left, $right ) {
				if ( (int) $left['order'] === (int) $right['order'] ) {
					return strcasecmp( $left['label'], $right['label'] );
				}

				return (int) $left['order'] <=> (int) $right['order'];
			}
		);

		return $items;
	}

	private static function app_admin_groups( $settings ) {
		$groups = [];

		foreach ( self::grouped_admin_links( $settings ) as $group ) {
			$links = [];
			foreach ( $group['links'] as $link ) {
				$links[] = [
					'label' => $link['label'],
					'url'   => $link['url'],
				];
			}

			$groups[] = [
				'label' => $group['label'],
				'links' => $links,
			];
		}

		return $groups;
	}

	public static function menu_slug_url( $slug ) {
		$slug = (string) $slug;

		if ( '' === $slug ) {
			return admin_url();
		}

		if ( preg_match( '#^https?://#i', $slug ) ) {
			return $slug;
		}

		// Known-broken slugs from older plugin versions — map to the
		// canonical modern URL. WooCommerce Marketing was previously
		// registered as the page slug "woocommerce-marketing" but
		// WC-Admin moved it under wc-admin's React router; the legacy
		// slug now returns "Cannot load woocommerce-marketing".
		$known_remaps = array(
			'woocommerce-marketing'  => 'admin.php?page=wc-admin&path=/marketing',
			'wc-marketing'           => 'admin.php?page=wc-admin&path=/marketing',
			'wc-analytics'           => 'admin.php?page=wc-admin&path=/analytics/overview',
			'woocommerce-analytics'  => 'admin.php?page=wc-admin&path=/analytics/overview',
			'wc-admin&path=/payments' => 'admin.php?page=wc-settings&tab=checkout',
			'wc-payments'            => 'admin.php?page=wc-settings&tab=checkout',
			'woocommerce-payments'   => 'admin.php?page=wc-settings&tab=checkout',
			'woocommerce'            => 'admin.php?page=wc-admin',
		);
		if ( isset( $known_remaps[ $slug ] ) ) {
			return admin_url( $known_remaps[ $slug ] );
		}

		// A page slug carrying extra query args but no base file — e.g.
		// WooPayments registers "wc-admin&path=/payments/overview". The `/`
		// inside that query must NOT be treated as a file path, or we build
		// /wp-admin/wc-admin&path=… which 404s to the front end. Prefix with
		// admin.php?page= so it resolves to the real screen.
		if ( false === strpos( $slug, '.php' ) && false === strpos( $slug, '?' ) && false !== strpos( $slug, '&' ) ) {
			return admin_url( 'admin.php?page=' . $slug );
		}

		if ( false !== strpos( $slug, '.php' ) || false !== strpos( $slug, '?' ) || false !== strpos( $slug, '/' ) ) {
			return admin_url( ltrim( $slug, '/' ) );
		}

		// Prefer WP's own URL resolution — handles plugin-registered
		// menu pages correctly, including ones that use non-standard
		// patterns. Falls back to the manual admin.php?page=<slug>
		// construction if WP can't resolve it.
		if ( function_exists( 'menu_page_url' ) ) {
			$resolved = menu_page_url( $slug, false );
			if ( ! empty( $resolved ) ) {
				return $resolved;
			}
		}

		return admin_url( 'admin.php?page=' . rawurlencode( $slug ) );
	}

	private static function menu_icon_key( $slug, $label = '' ) {
		$haystack = strtolower( $slug . ' ' . $label );

		// Ordered keyword rules — most specific first. Ordering matters:
		// `products`/`pages` are checked before `posts` because the
		// edit.php?post_type=… slugs all contain the substring "post".
		// Semantic content-type rules come BEFORE the generic `post` rule,
		// because every custom-post-type menu slug (edit.php?post_type=…)
		// contains the substring "post" and would otherwise all grab the
		// Posts icon.
		$rules = array(
			'tickets'   => array( 'event', 'ticket' ),
			'products'  => array( 'post_type=product', 'product' ),
			'payments'  => array( 'payment', 'gateway', 'wc-settings', 'checkout' ),
			'analytics' => array( 'analytic', 'wc-admin', 'stats', 'report', 'insight' ),
			'orders'    => array( 'woocommerce', 'order', 'shop', 'store' ),
			'folder'    => array( 'document', 'file', 'download', 'resource', 'library' ),
			'forms'     => array( 'contact', 'form', 'feedback', 'enquir', 'consent', 'submission' ),
			'users'     => array( 'user', 'member', 'people', 'parent', 'application', 'volunteer' ),
			'builder'   => array( 'builder', 'layout', 'block' ),
			'marketing' => array( 'market', 'promo', 'campaign', 'newsletter' ),
			'media'     => array( 'upload', 'media', 'image', 'gallery' ),
			'pages'     => array( 'post_type=page', 'page' ),
			'posts'     => array( 'post' ),
			'links'     => array( 'link' ),
			'security'  => array( 'security', 'firewall' ),
			'backup'    => array( 'backup' ),
			'health'    => array( 'health' ),
			'speed'     => array( 'speed', 'cache', 'performance' ),
			'updates'   => array( 'update' ),
			'admin'     => array( 'plugin', 'tool', 'setting', 'option', 'config' ),
			'dashboard' => array( 'dashboard' ),
		);

		foreach ( $rules as $key => $words ) {
			foreach ( $words as $word ) {
				if ( false !== strpos( $haystack, $word ) ) {
					return $key;
				}
			}
		}

		if ( 'index.php' === $slug ) {
			return 'dashboard';
		}

		return 'pin';
	}

	private static function icon_choices() {
		return [
			'dashboard' => 'Dashboard',
			'tickets'   => 'Tickets',
			'calendar'  => 'Calendar',
			'pages'     => 'Pages',
			'media'     => 'Media',
			'cart'      => 'Cart',
			'orders'    => 'Orders',
			'products'  => 'Products',
			'users'     => 'Users',
			'posts'     => 'Posts',
			'forms'     => 'Forms',
			'links'     => 'Links',
			'payments'  => 'Payments',
			'marketing' => 'Marketing',
			'builder'   => 'Builder',
			'appearance'=> 'Appearance',
			'analytics' => 'Analytics',
			'folder'    => 'Folder',
			'admin'     => 'Admin',
			'plugins'   => 'Plugins',
			'tools'     => 'Tools',
			'updates'   => 'Updates',
			'security'  => 'Security',
			'backup'    => 'Backup',
			'health'    => 'Health',
			'speed'     => 'Speed',
			'pin'       => 'Circle',
		];
	}

	private static function valid_icon_key( $icon ) {
		$icon = sanitize_key( (string) $icon );
		$choices = self::icon_choices();

		return isset( $choices[ $icon ] ) ? $icon : 'pin';
	}

	/**
	 * Map an internal icon key to its WP dashicon CSS class. Public so the
	 * Menu Builder UI can preview the same glyphs the sidebar actually
	 * renders. Kept in sync with the same table in templates/admin-shell.php.
	 */
	public static function icon_dashicon( $icon ) {
		$map = [
			'dashboard'  => 'dashicons-dashboard',
			'tickets'    => 'dashicons-tickets-alt',
			'calendar'   => 'dashicons-calendar-alt',
			'pages'      => 'dashicons-admin-page',
			'media'      => 'dashicons-format-image',
			'cart'       => 'dashicons-cart',
			'orders'     => 'dashicons-cart',
			'products'   => 'dashicons-products',
			'users'      => 'dashicons-admin-users',
			'posts'      => 'dashicons-admin-post',
			'forms'      => 'dashicons-feedback',
			'links'      => 'dashicons-admin-links',
			'payments'   => 'dashicons-money-alt',
			'marketing'  => 'dashicons-megaphone',
			'builder'    => 'dashicons-layout',
			'appearance' => 'dashicons-admin-appearance',
			'analytics'  => 'dashicons-chart-bar',
			'folder'     => 'dashicons-portfolio',
			'admin'      => 'dashicons-admin-tools',
			'plugins'    => 'dashicons-admin-plugins',
			'tools'      => 'dashicons-admin-tools',
			'updates'    => 'dashicons-update',
			'security'   => 'dashicons-shield',
			'backup'     => 'dashicons-database',
			'health'     => 'dashicons-heart',
			'speed'      => 'dashicons-performance',
			'pin'        => 'dashicons-marker',
		];

		$icon = (string) $icon;
		return isset( $map[ $icon ] ) ? $map[ $icon ] : 'dashicons-marker';
	}

	private static function update_summary() {
		return [
			'core'    => count( get_core_updates( [ 'dismissed' => false ] ) ),
			'plugins' => count( get_plugin_updates() ),
			'themes'  => count( get_theme_updates() ),
		];
	}

	private static function logo_url() {
		$settings = self::settings();

		if ( ! empty( $settings['admin_logo_id'] ) ) {
			$logo_url = wp_get_attachment_image_url( absint( $settings['admin_logo_id'] ), 'full' );
			if ( $logo_url ) {
				return $logo_url;
			}
		}

		if ( ! empty( $settings['admin_logo_url'] ) ) {
			return $settings['admin_logo_url'];
		}

		if ( function_exists( 'get_site_icon_url' ) && get_site_icon_url( 128 ) ) {
			return get_site_icon_url( 128 );
		}

		// Fall back to the bundled Scouts mark in the active theme so the
		// sidebar always has a brand logo rather than a tiny Unicode fleur.
		$theme_logo = get_template_directory_uri() . '/images/scouts-logo-standard.svg';
		if ( file_exists( get_template_directory() . '/images/scouts-logo-standard.svg' ) ) {
			return $theme_logo;
		}

		return '';
	}

	private static function inline_css( $settings ) {
		$logo_url = self::logo_url();

		return sprintf(
			':root{--das-primary:%1$s;--das-accent:%2$s;--das-menu-bg:%3$s;--das-menu-text:%4$s;--das-logo:url("%5$s");}',
			esc_html( $settings['primary_color'] ),
			esc_html( $settings['accent_color'] ),
			esc_html( $settings['menu_bg_color'] ),
			esc_html( $settings['menu_text_color'] ),
			esc_url_raw( $logo_url )
		);
	}

	private static function clean_menu_label( $label ) {
		$label = wp_strip_all_tags( (string) $label );
		$label = preg_replace( '/\s+\d+$/', '', $label );
		return trim( $label );
	}

	private static function available_menu_catalog() {
		$catalog = self::$captured_menu_items;
		if ( empty( $catalog ) ) {
			$catalog = [];
			foreach ( self::$fallback_menu_links as $slug => $label ) {
				$catalog[ $slug ] = [
					'label' => $label,
					'slug'  => $slug,
				];
			}
		}

		uksort(
			$catalog,
			function ( $left, $right ) use ( $catalog ) {
				return strcasecmp( $catalog[ $left ]['label'], $catalog[ $right ]['label'] );
			}
		);

		return $catalog;
	}

	private static function default_group_slug( $groups ) {
		$keys = array_keys( $groups );
		return ! empty( $keys ) ? $keys[0] : 'technical';
	}

	private static function sanitize_group_textarea( $text, $fallback ) {
		$lines  = preg_split( '/\r\n|\r|\n/', (string) $text );
		$groups = [];

		foreach ( $lines as $line ) {
			$label = trim( sanitize_text_field( $line ) );
			if ( '' === $label ) {
				continue;
			}

			$slug = sanitize_title( $label );
			if ( '' === $slug ) {
				continue;
			}

			$groups[ $slug ] = $label;
		}

		if ( empty( $groups ) ) {
			return self::sanitize_group_values( $fallback );
		}

		return $groups;
	}

	private static function sanitize_group_values( $groups ) {
		$clean = [];
		foreach ( (array) $groups as $slug => $label ) {
			$label = trim( sanitize_text_field( $label ) );
			$slug  = sanitize_title( is_string( $slug ) ? $slug : $label );

			if ( '' === $slug || '' === $label ) {
				continue;
			}

			$clean[ $slug ] = $label;
		}

		if ( empty( $clean ) ) {
			return self::default_groups();
		}

		return $clean;
	}

	private static function normalise_menu_items( $items, $groups ) {
		$defaults = self::default_menu_items();
		$catalog  = self::available_menu_catalog();
		$utility  = self::utility_links();
		$normal   = [];

		$slugs = array_unique(
			array_merge(
				array_keys( (array) $items ),
				array_keys( $catalog ),
				array_keys( array_intersect_key( (array) $items, $utility ) )
			)
		);

		foreach ( $slugs as $slug ) {
			$catalog_item = $catalog[ $slug ] ?? [
				'label' => $utility[ $slug ] ?? ( $defaults[ $slug ]['label'] ?? $slug ),
				'slug'  => $slug,
			];

			$base = $defaults[ $slug ] ?? [
				'label'     => $catalog_item['label'],
				'group'     => self::default_group_slug( $groups ),
				'placement' => 'keep',
				'icon'      => self::menu_icon_key( $slug, $catalog_item['label'] ),
				'order'     => 500,
				'divider_before' => '0',
			];

			$saved = $items[ $slug ] ?? [];

			$group = sanitize_title( $saved['group'] ?? $base['group'] );
			if ( ! isset( $groups[ $group ] ) ) {
				$group = self::default_group_slug( $groups );
			}

			$placement = $saved['placement'] ?? $base['placement'];
			if ( ! in_array( $placement, [ 'keep', 'bottom', 'admin', 'hide' ], true ) ) {
				$placement = $base['placement'];
			}

			$normal[ $slug ] = [
				'label'     => sanitize_text_field( $saved['label'] ?? $base['label'] ),
				'group'     => $group,
				'placement' => $placement,
				'icon'      => self::valid_icon_key( $saved['icon'] ?? $base['icon'] ?? self::menu_icon_key( $slug, $catalog_item['label'] ) ),
				'order'     => isset( $saved['order'] ) ? (int) $saved['order'] : (int) ( $base['order'] ?? 500 ),
				'divider_before' => ! empty( $saved['divider_before'] ) || ! empty( $base['divider_before'] ) ? '1' : '0',
			];
		}

		uasort(
			$normal,
			function ( $left, $right ) {
				if ( (int) $left['order'] === (int) $right['order'] ) {
					return strcasecmp( $left['label'], $right['label'] );
				}

				return (int) $left['order'] <=> (int) $right['order'];
			}
		);

		return $normal;
	}

	private static function sanitize_menu_items( $items, $groups, $fallback_items ) {
		$catalog = self::available_menu_catalog();
		$utility = self::utility_links();
		$clean   = [];

		$slugs = array_unique(
			array_merge(
				array_keys( is_array( $items ) ? $items : [] ),
				array_keys( $catalog ),
				array_keys( $utility )
			)
		);

		foreach ( $slugs as $slug ) {
			$catalog_item = $catalog[ $slug ] ?? [
				'label' => $utility[ $slug ] ?? ( $fallback_items[ $slug ]['label'] ?? $slug ),
				'slug'  => $slug,
			];

			$current = $fallback_items[ $slug ] ?? [
				'label'     => $catalog_item['label'] ?? $slug,
				'group'     => self::default_group_slug( $groups ),
				'placement' => 'keep',
				'icon'      => self::menu_icon_key( $slug, $catalog_item['label'] ?? $slug ),
				'order'     => 500,
				'divider_before' => '0',
			];

			$item = isset( $items[ $slug ] ) && is_array( $items[ $slug ] ) ? $items[ $slug ] : [];

			$group = sanitize_title( $item['group'] ?? $current['group'] );
			if ( ! isset( $groups[ $group ] ) ) {
				$group = self::default_group_slug( $groups );
			}

			$placement = sanitize_text_field( $item['placement'] ?? $current['placement'] );
			if ( ! in_array( $placement, [ 'keep', 'bottom', 'admin', 'hide' ], true ) ) {
				$placement = $current['placement'];
			}

			$label = sanitize_text_field( $item['label'] ?? $current['label'] );
			if ( '' === $label ) {
				$label = $catalog_item['label'];
			}

			$clean[ $slug ] = [
				'label'     => $label,
				'group'     => $group,
				'placement' => $placement,
				'icon'      => self::valid_icon_key( $item['icon'] ?? $current['icon'] ?? self::menu_icon_key( $slug, $label ) ),
				'order'     => isset( $item['order'] ) ? (int) $item['order'] : (int) ( $current['order'] ?? 500 ),
				'divider_before' => ! empty( $item['divider_before'] ) ? '1' : '0',
			];
		}

		uasort(
			$clean,
			function ( $left, $right ) {
				if ( (int) $left['order'] === (int) $right['order'] ) {
					return strcasecmp( $left['label'], $right['label'] );
				}

				return (int) $left['order'] <=> (int) $right['order'];
			}
		);

		return $clean;
	}

	private static function sanitize_custom_links( $links ) {
		$clean = [];

		foreach ( (array) $links as $link ) {
			if ( ! is_array( $link ) ) {
				continue;
			}

			$label = sanitize_text_field( $link['label'] ?? '' );
			$url   = sanitize_text_field( $link['url'] ?? '' );
			$group = sanitize_title( $link['group'] ?? '' );
			$icon  = self::valid_icon_key( $link['icon'] ?? 'pin' );
			$order = isset( $link['order'] ) ? (int) $link['order'] : 500;

			if ( '' === $label && '' === $url ) {
				continue;
			}

			if ( '' === $label || '' === $url ) {
				continue;
			}

			$clean[] = [
				'label' => $label,
				'url'   => $url,
				'group' => '' !== $group ? $group : 'technical',
				'icon'  => $icon,
				'order' => $order,
			];
		}

		// Preserve user-defined order across saves.
		usort(
			$clean,
			function ( $left, $right ) {
				return (int) $left['order'] <=> (int) $right['order'];
			}
		);

		return array_values( $clean );
	}

	private static function normalise_link_url( $url ) {
		$url = trim( (string) $url );
		if ( '' === $url ) {
			return admin_url();
		}

		if ( preg_match( '#^https?://#i', $url ) ) {
			return $url;
		}

		return admin_url( ltrim( $url, '/' ) );
	}

	private static function groups_textarea_value( $groups ) {
		return implode( "\n", array_values( $groups ) );
	}

}

Davenham_Admin_Suite::init();
