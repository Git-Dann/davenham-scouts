<?php
/**
 * Plugin Name: Davenham Builder
 * Plugin URI:  https://davenhamscouts.org.uk
 * Description: Visual page builder + all custom Gutenberg blocks for Davenham Scout Group. One plugin, no faff.
 * Version:     1.4.3
 * Author:      Davenham Scout Group
 * Text Domain: davenham-builder
 * Requires at least: 6.0
 * Requires PHP: 7.0
 */

defined( 'ABSPATH' ) || exit;

define( 'DB_VERSION', '1.4.3' );
define( 'DB_DIR',     plugin_dir_path( __FILE__ ) );
define( 'DB_URL',     plugin_dir_url( __FILE__ ) );

// One-time page migration helpers (Builder → Page Migration admin screen).
require_once DB_DIR . 'migrations.php';

// Sample shop products seeder (Builder → Sample Shop Products admin screen).
require_once DB_DIR . 'shop-seed.php';

// ─── Shared helper — declared once here so render.php files can call it safely ─
if ( ! function_exists( 'davenham_video_to_embed' ) ) {
	function davenham_video_to_embed( $url ) {
		if ( empty( $url ) ) {
			return '';
		}
		if ( preg_match( '/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $url, $m ) ) {
			return 'https://www.youtube.com/embed/' . $m[1] . '?rel=0';
		}
		if ( preg_match( '/vimeo\.com\/(\d+)/', $url, $m ) ) {
			return 'https://player.vimeo.com/video/' . $m[1];
		}
		return $url;
	}
}

// ─── Block category ───────────────────────────────────────────────────────────
if ( ! function_exists( 'db_register_block_category' ) ) {
	add_filter( 'block_categories_all', 'db_register_block_category', 10, 2 );
	function db_register_block_category( $categories ) {
		return array_merge(
			array( array( 'slug' => 'davenham', 'title' => 'Davenham Blocks', 'icon' => 'flag' ) ),
			$categories
		);
	}
}

// ─── Register blocks + shared editor script ───────────────────────────────────
add_action( 'init', 'db_register_blocks' );
function db_register_blocks() {
	if ( did_action( 'db_blocks_registered' ) ) {
		return;
	}
	do_action( 'db_blocks_registered' );

	wp_register_script(
		'davenham-blocks-editor',
		DB_URL . 'src/index.js',
		array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-i18n', 'wp-block-editor' ),
		DB_VERSION,
		true
	);

	$blocks = array(
		'hero', 'page-hero', 'site-notice',
		'welcome-section', 'text-image', 'rich-text',
		'faq', 'icon-feature-row', 'cta-button-row',
		'leaders', 'sponsors', 'contact-info',
		'age-section', 'news-feed', 'events-list',
		'gallery', 'video-embed', 'section-divider',
		'stats-grid', 'testimonial-grid', 'quote-banner',
		'card-grid', 'downloads-list', 'timeline',
		'steps', 'split-cta', 'newsletter-signup',
		'tabs', 'logo-strip', 'key-facts',
		'promo-banner', 'donation-cards', 'popup-promo',
		'session-times', 'product-grid', 'featured-product',
		'event-ticket-card', 'category-grid',
		'section-shop-grid', 'shop-hero',
	);

	foreach ( $blocks as $block ) {
		$dir = DB_DIR . 'blocks/' . $block . '/';
		if ( is_dir( $dir ) && file_exists( $dir . 'block.json' ) ) {
			register_block_type( $dir );
		}
	}
}

// ─── Admin menu ───────────────────────────────────────────────────────────────
add_action( 'admin_menu', 'db_register_admin_menu' );
function db_register_admin_menu() {
	add_menu_page(
		__( 'Davenham Builder', 'davenham-builder' ),
		__( 'Builder', 'davenham-builder' ),
		'edit_pages',
		'davenham-builder',
		'db_render_builder_page',
		'dashicons-layout',
		3
	);

	// First submenu duplicates the parent slug — WordPress shows it as the
	// default sub-item label. "Page Builder" reads accurately (the screen IS
	// the page builder), unlike the previous misleading "Starter Presets"
	// (the screen does not open straight onto presets).
	add_submenu_page(
		'davenham-builder',
		__( 'Page Builder', 'davenham-builder' ),
		__( 'Page Builder', 'davenham-builder' ),
		'edit_pages',
		'davenham-builder',
		'db_render_builder_page'
	);

	add_submenu_page(
		'davenham-builder',
		__( 'Site Settings', 'davenham-builder' ),
		__( 'Site Settings', 'davenham-builder' ),
		'manage_options',
		'davenham-builder-site-settings',
		'db_render_site_settings_page'
	);

	add_submenu_page(
		'davenham-builder',
		__( 'Page Migration', 'davenham-builder' ),
		__( 'Page Migration', 'davenham-builder' ),
		'manage_options',
		'davenham-builder-migrate',
		function () {
			if ( function_exists( 'db_render_migration_page' ) ) {
				db_render_migration_page();
			} else {
				echo '<div class="wrap"><h1>' . esc_html__( 'Page Migration', 'davenham-builder' ) . '</h1><p>' . esc_html__( 'The migration module could not be loaded.', 'davenham-builder' ) . '</p></div>';
			}
		}
	);

	add_submenu_page(
		'davenham-builder',
		__( 'Sample Shop Products', 'davenham-builder' ),
		__( 'Sample Shop Products', 'davenham-builder' ),
		'manage_options',
		'davenham-builder-shop-seed',
		function () {
			if ( function_exists( 'db_render_shop_seed_page' ) ) {
				db_render_shop_seed_page();
			} else {
				echo '<div class="wrap"><h1>' . esc_html__( 'Sample Shop Products', 'davenham-builder' ) . '</h1><p>' . esc_html__( 'The shop-seed module could not be loaded.', 'davenham-builder' ) . '</p></div>';
			}
		}
	);
}

function db_render_builder_page() {
	echo '<div id="davenham-builder-root"></div>';
}

function db_site_settings_defaults() {
	return array(
		'logo_id'                  => 0,
		'logo_url'                 => '',
		'header_primary_cta_text'  => 'Volunteer with Scouts',
		'header_primary_cta_url'   => home_url( '/volunteer/' ),
		'header_secondary_cta_text'=> 'Join Scouts',
		'header_secondary_cta_url' => home_url( '/join/' ),
		'footer_title'             => 'Skills for Life for young people across Davenham through Beavers, Cubs and Scouts.',
		'footer_intro'             => 'We are a volunteer-led Scout Group, rooted in the local community and based at Peckmill Scout Wood.',
		'footer_address'           => "Davenham\nCheshire\nCW9 8LH",
		'footer_contact_text'      => 'Click here',
		'footer_contact_url'       => home_url( '/contact/' ),
		'footer_social_facebook'   => 'https://www.facebook.com/DavenhamScouts/',
		'footer_social_x'          => 'https://twitter.com/1stDavenham',
		'charity_number'           => '1029781',
		'form_notifications_email' => '1stdavenhamscouts@gmail.com',
		'cookie_enabled'           => '1',
		'cookie_text'              => 'We use cookies to keep the website working well and to understand what content is most useful.',
		'cookie_accept_label'      => 'Accept',
		'cookie_reject_label'      => 'Reject',
		'cookie_policy_label'      => 'Read cookies policy',
		'cookie_policy_url'        => home_url( '/cookies/' ),
		'popup_enabled'            => '0',
		'popup_title'              => 'Stay in the loop',
		'popup_content'            => '<p>Use this space for a seasonal message, event promo, or newsletter signup.</p>',
		'popup_button_text'        => 'Find out more',
		'popup_button_url'         => home_url( '/contact/' ),
		'popup_delay'              => 4,
		'newsletter_title'         => 'Newsletter signup',
		'newsletter_text'          => 'Paste your newsletter embed or use a simple contact CTA until your mailing setup is ready.',
		'newsletter_embed'         => '',
		'newsletter_button_text'   => '',
		'newsletter_button_url'    => '',
	);
}

function db_get_site_settings() {
	$saved = get_option( 'davenham_builder_site_settings', array() );
	return wp_parse_args( is_array( $saved ) ? $saved : array(), db_site_settings_defaults() );
}

function db_sanitize_site_settings( $input ) {
	$current = db_get_site_settings();
	$clean   = array();
	$clean['logo_id']                   = absint( $input['logo_id'] ?? $current['logo_id'] );
	$clean['logo_url']                  = esc_url_raw( $input['logo_url'] ?? $current['logo_url'] );
	$clean['header_primary_cta_text']   = sanitize_text_field( $input['header_primary_cta_text'] ?? $current['header_primary_cta_text'] );
	$clean['header_primary_cta_url']    = esc_url_raw( $input['header_primary_cta_url'] ?? $current['header_primary_cta_url'] );
	$clean['header_secondary_cta_text'] = sanitize_text_field( $input['header_secondary_cta_text'] ?? $current['header_secondary_cta_text'] );
	$clean['header_secondary_cta_url']  = esc_url_raw( $input['header_secondary_cta_url'] ?? $current['header_secondary_cta_url'] );
	$clean['footer_title']              = sanitize_text_field( $input['footer_title'] ?? $current['footer_title'] );
	$clean['footer_intro']              = sanitize_textarea_field( $input['footer_intro'] ?? $current['footer_intro'] );
	$clean['footer_address']            = sanitize_textarea_field( $input['footer_address'] ?? $current['footer_address'] );
	$clean['footer_contact_text']       = sanitize_text_field( $input['footer_contact_text'] ?? $current['footer_contact_text'] );
	$clean['footer_contact_url']        = esc_url_raw( $input['footer_contact_url'] ?? $current['footer_contact_url'] );
	$clean['footer_social_facebook']    = esc_url_raw( $input['footer_social_facebook'] ?? $current['footer_social_facebook'] );
	$clean['footer_social_x']           = esc_url_raw( $input['footer_social_x'] ?? $current['footer_social_x'] );
	$clean['charity_number']            = sanitize_text_field( $input['charity_number'] ?? $current['charity_number'] );
	$clean['form_notifications_email']  = sanitize_email( $input['form_notifications_email'] ?? $current['form_notifications_email'] );
	if ( ! is_email( $clean['form_notifications_email'] ) ) {
		$clean['form_notifications_email'] = $current['form_notifications_email'];
	}
	$clean['cookie_enabled']            = ! empty( $input['cookie_enabled'] ) ? '1' : '0';
	$clean['cookie_text']               = sanitize_textarea_field( $input['cookie_text'] ?? $current['cookie_text'] );
	$clean['cookie_accept_label']       = sanitize_text_field( $input['cookie_accept_label'] ?? $current['cookie_accept_label'] );
	$clean['cookie_reject_label']       = sanitize_text_field( $input['cookie_reject_label'] ?? $current['cookie_reject_label'] );
	$clean['cookie_policy_label']       = sanitize_text_field( $input['cookie_policy_label'] ?? $current['cookie_policy_label'] );
	$clean['cookie_policy_url']         = esc_url_raw( $input['cookie_policy_url'] ?? $current['cookie_policy_url'] );
	$clean['popup_enabled']             = ! empty( $input['popup_enabled'] ) ? '1' : '0';
	$clean['popup_title']               = sanitize_text_field( $input['popup_title'] ?? $current['popup_title'] );
	$clean['popup_content']             = wp_kses_post( $input['popup_content'] ?? $current['popup_content'] );
	$clean['popup_button_text']         = sanitize_text_field( $input['popup_button_text'] ?? $current['popup_button_text'] );
	$clean['popup_button_url']          = esc_url_raw( $input['popup_button_url'] ?? $current['popup_button_url'] );
	$clean['popup_delay']               = max( 0, min( 60, absint( $input['popup_delay'] ?? $current['popup_delay'] ) ) );
	$clean['newsletter_title']          = sanitize_text_field( $input['newsletter_title'] ?? $current['newsletter_title'] );
	$clean['newsletter_text']           = sanitize_textarea_field( $input['newsletter_text'] ?? $current['newsletter_text'] );
	$clean['newsletter_embed']          = wp_kses_post( $input['newsletter_embed'] ?? $current['newsletter_embed'] );
	$clean['newsletter_button_text']    = sanitize_text_field( $input['newsletter_button_text'] ?? $current['newsletter_button_text'] );
	$clean['newsletter_button_url']     = esc_url_raw( $input['newsletter_button_url'] ?? $current['newsletter_button_url'] );

	if ( $clean['logo_id'] > 0 ) {
		$logo_url = wp_get_attachment_image_url( $clean['logo_id'], 'full' );
		if ( $logo_url ) {
			$clean['logo_url'] = $logo_url;
		}
	}

	return $clean;
}

add_action( 'admin_init', 'db_register_site_settings' );
function db_register_site_settings() {
	register_setting(
		'davenham_builder_site_settings',
		'davenham_builder_site_settings',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'db_sanitize_site_settings',
			'default'           => db_site_settings_defaults(),
		)
	);
}

function db_render_site_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'davenham-builder' ) );
	}

	$settings = db_get_site_settings();
	?>
	<div class="wrap db-settings-wrap">
		<h1><?php esc_html_e( 'Site Settings', 'davenham-builder' ); ?></h1>
		<p class="db-settings-lede">
			<?php esc_html_e( 'These are the site-wide pieces that wrap around your pages — header, footer, the cookie banner, an optional welcome popup, and your newsletter defaults. Changes save together.', 'davenham-builder' ); ?>
		</p>

		<form method="post" action="options.php" class="db-site-settings">
			<?php settings_fields( 'davenham_builder_site_settings' ); ?>

			<section class="db-settings-card">
				<h2><?php esc_html_e( 'Header', 'davenham-builder' ); ?></h2>
				<p class="db-settings-card__desc"><?php esc_html_e( 'The logo and call-to-action buttons that appear at the top of every page.', 'davenham-builder' ); ?></p>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="db_logo_url"><?php esc_html_e( 'Logo', 'davenham-builder' ); ?></label></th>
						<td>
							<input type="hidden" id="db_logo_id" name="davenham_builder_site_settings[logo_id]" value="<?php echo esc_attr( (string) $settings['logo_id'] ); ?>" />
							<input type="url" class="regular-text" id="db_logo_url" name="davenham_builder_site_settings[logo_url]" value="<?php echo esc_attr( $settings['logo_url'] ); ?>" />
							<button type="button" class="button db-media-open" data-target="#db_logo_url" data-id-target="#db_logo_id"><?php esc_html_e( 'Choose image', 'davenham-builder' ); ?></button>
							<button type="button" class="button db-media-clear" data-target="#db_logo_url" data-id-target="#db_logo_id"><?php esc_html_e( 'Remove', 'davenham-builder' ); ?></button>
							<p class="description"><?php esc_html_e( 'Upload your group logo (SVG or PNG, ideally white-on-transparent for the header).', 'davenham-builder' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label><?php esc_html_e( 'Primary CTA', 'davenham-builder' ); ?></label></th>
						<td>
							<input type="text" class="regular-text" name="davenham_builder_site_settings[header_primary_cta_text]" placeholder="<?php esc_attr_e( 'Button label', 'davenham-builder' ); ?>" value="<?php echo esc_attr( $settings['header_primary_cta_text'] ); ?>" />
							<input type="url" class="regular-text" name="davenham_builder_site_settings[header_primary_cta_url]" placeholder="https://…" value="<?php echo esc_attr( $settings['header_primary_cta_url'] ); ?>" />
							<p class="description"><?php esc_html_e( 'Main call-to-action in the header (e.g. "Volunteer").', 'davenham-builder' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label><?php esc_html_e( 'Secondary CTA', 'davenham-builder' ); ?></label></th>
						<td>
							<input type="text" class="regular-text" name="davenham_builder_site_settings[header_secondary_cta_text]" placeholder="<?php esc_attr_e( 'Button label', 'davenham-builder' ); ?>" value="<?php echo esc_attr( $settings['header_secondary_cta_text'] ); ?>" />
							<input type="url" class="regular-text" name="davenham_builder_site_settings[header_secondary_cta_url]" placeholder="https://…" value="<?php echo esc_attr( $settings['header_secondary_cta_url'] ); ?>" />
							<p class="description"><?php esc_html_e( 'Secondary call-to-action (e.g. "Join Scouts").', 'davenham-builder' ); ?></p>
						</td>
					</tr>
				</table>
			</section>

			<section class="db-settings-card">
				<h2><?php esc_html_e( 'Footer', 'davenham-builder' ); ?></h2>
				<p class="db-settings-card__desc"><?php esc_html_e( 'Group identity, address, social links and the registered charity number shown at the bottom of every page.', 'davenham-builder' ); ?></p>
				<table class="form-table" role="presentation">
					<tr><th scope="row"><label><?php esc_html_e( 'Title line', 'davenham-builder' ); ?></label></th><td><input type="text" class="large-text" name="davenham_builder_site_settings[footer_title]" value="<?php echo esc_attr( $settings['footer_title'] ); ?>" /><p class="description"><?php esc_html_e( 'Short tagline shown next to your logo in the footer.', 'davenham-builder' ); ?></p></td></tr>
					<tr><th scope="row"><label><?php esc_html_e( 'Intro', 'davenham-builder' ); ?></label></th><td><textarea class="large-text" rows="3" name="davenham_builder_site_settings[footer_intro]"><?php echo esc_textarea( $settings['footer_intro'] ); ?></textarea></td></tr>
					<tr><th scope="row"><label><?php esc_html_e( 'Address', 'davenham-builder' ); ?></label></th><td><textarea class="large-text" rows="3" name="davenham_builder_site_settings[footer_address]"><?php echo esc_textarea( $settings['footer_address'] ); ?></textarea><p class="description"><?php esc_html_e( 'One line per row — appears in the Contact column.', 'davenham-builder' ); ?></p></td></tr>
					<tr><th scope="row"><label><?php esc_html_e( 'Contact link', 'davenham-builder' ); ?></label></th><td><input type="text" class="regular-text" name="davenham_builder_site_settings[footer_contact_text]" placeholder="<?php esc_attr_e( 'Link label', 'davenham-builder' ); ?>" value="<?php echo esc_attr( $settings['footer_contact_text'] ); ?>" /> <input type="url" class="regular-text" name="davenham_builder_site_settings[footer_contact_url]" placeholder="https://…" value="<?php echo esc_attr( $settings['footer_contact_url'] ); ?>" /></td></tr>
					<tr><th scope="row"><label><?php esc_html_e( 'Facebook', 'davenham-builder' ); ?></label></th><td><input type="url" class="regular-text" name="davenham_builder_site_settings[footer_social_facebook]" placeholder="https://www.facebook.com/…" value="<?php echo esc_attr( $settings['footer_social_facebook'] ); ?>" /></td></tr>
					<tr><th scope="row"><label><?php esc_html_e( 'X / Twitter', 'davenham-builder' ); ?></label></th><td><input type="url" class="regular-text" name="davenham_builder_site_settings[footer_social_x]" placeholder="https://twitter.com/…" value="<?php echo esc_attr( $settings['footer_social_x'] ); ?>" /></td></tr>
					<tr><th scope="row"><label><?php esc_html_e( 'Charity number', 'davenham-builder' ); ?></label></th><td><input type="text" class="regular-text" name="davenham_builder_site_settings[charity_number]" value="<?php echo esc_attr( $settings['charity_number'] ); ?>" /><p class="description"><?php esc_html_e( 'Registered charity number — appears next to copyright in the footer.', 'davenham-builder' ); ?></p></td></tr>
				</table>
			</section>

			<section class="db-settings-card">
				<h2><?php esc_html_e( 'Form notifications', 'davenham-builder' ); ?></h2>
				<p class="db-settings-card__desc"><?php esc_html_e( 'Where Contact and Join form submissions are emailed to. Use a real, monitored inbox.', 'davenham-builder' ); ?></p>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="db_form_notifications_email"><?php esc_html_e( 'Notifications email', 'davenham-builder' ); ?></label></th>
						<td>
							<input type="email" class="regular-text" id="db_form_notifications_email" name="davenham_builder_site_settings[form_notifications_email]" value="<?php echo esc_attr( $settings['form_notifications_email'] ); ?>" placeholder="hello@example.org" />
							<p class="description"><?php esc_html_e( 'Where Contact and Join form submissions are emailed. Make sure this inbox is checked regularly.', 'davenham-builder' ); ?></p>
						</td>
					</tr>
				</table>
			</section>

			<section class="db-settings-card">
				<h2><?php esc_html_e( 'Cookie Banner', 'davenham-builder' ); ?></h2>
				<p class="db-settings-card__desc"><?php esc_html_e( 'The GDPR-friendly cookie notice that appears once per visitor. Disable if your hosting handles this another way.', 'davenham-builder' ); ?></p>
				<table class="form-table" role="presentation">
					<tr><th scope="row"><label><?php esc_html_e( 'Enable cookie banner', 'davenham-builder' ); ?></label></th><td><label><input type="checkbox" name="davenham_builder_site_settings[cookie_enabled]" value="1" <?php checked( $settings['cookie_enabled'], '1' ); ?> /> <?php esc_html_e( 'Show the site-wide cookie banner.', 'davenham-builder' ); ?></label></td></tr>
					<tr><th scope="row"><label><?php esc_html_e( 'Cookie text', 'davenham-builder' ); ?></label></th><td><textarea class="large-text" rows="3" name="davenham_builder_site_settings[cookie_text]"><?php echo esc_textarea( $settings['cookie_text'] ); ?></textarea></td></tr>
					<tr><th scope="row"><label><?php esc_html_e( 'Buttons', 'davenham-builder' ); ?></label></th><td><input type="text" class="regular-text" name="davenham_builder_site_settings[cookie_accept_label]" placeholder="<?php esc_attr_e( 'Accept label', 'davenham-builder' ); ?>" value="<?php echo esc_attr( $settings['cookie_accept_label'] ); ?>" /> <input type="text" class="regular-text" name="davenham_builder_site_settings[cookie_reject_label]" placeholder="<?php esc_attr_e( 'Reject label', 'davenham-builder' ); ?>" value="<?php echo esc_attr( $settings['cookie_reject_label'] ); ?>" /></td></tr>
					<tr><th scope="row"><label><?php esc_html_e( 'Policy link', 'davenham-builder' ); ?></label></th><td><input type="text" class="regular-text" name="davenham_builder_site_settings[cookie_policy_label]" placeholder="<?php esc_attr_e( 'Link label', 'davenham-builder' ); ?>" value="<?php echo esc_attr( $settings['cookie_policy_label'] ); ?>" /> <input type="url" class="regular-text" name="davenham_builder_site_settings[cookie_policy_url]" placeholder="https://…" value="<?php echo esc_attr( $settings['cookie_policy_url'] ); ?>" /></td></tr>
				</table>
			</section>

			<section class="db-settings-card">
				<h2><?php esc_html_e( 'Popup Promo', 'davenham-builder' ); ?></h2>
				<p class="db-settings-card__desc"><?php esc_html_e( 'A one-time popup shown to each visitor (handy for event announcements). Stays dismissed in their browser after they close it.', 'davenham-builder' ); ?></p>
				<table class="form-table" role="presentation">
					<tr><th scope="row"><label><?php esc_html_e( 'Enable popup', 'davenham-builder' ); ?></label></th><td><label><input type="checkbox" name="davenham_builder_site_settings[popup_enabled]" value="1" <?php checked( $settings['popup_enabled'], '1' ); ?> /> <?php esc_html_e( 'Show a site-wide popup once per browser.', 'davenham-builder' ); ?></label></td></tr>
					<tr><th scope="row"><label><?php esc_html_e( 'Popup title', 'davenham-builder' ); ?></label></th><td><input type="text" class="large-text" name="davenham_builder_site_settings[popup_title]" value="<?php echo esc_attr( $settings['popup_title'] ); ?>" /></td></tr>
					<tr><th scope="row"><label><?php esc_html_e( 'Popup content', 'davenham-builder' ); ?></label></th><td><textarea class="large-text" rows="5" name="davenham_builder_site_settings[popup_content]"><?php echo esc_textarea( $settings['popup_content'] ); ?></textarea><p class="description"><?php esc_html_e( 'Basic HTML is allowed (paragraphs, links, line breaks).', 'davenham-builder' ); ?></p></td></tr>
					<tr><th scope="row"><label><?php esc_html_e( 'Popup CTA', 'davenham-builder' ); ?></label></th><td><input type="text" class="regular-text" name="davenham_builder_site_settings[popup_button_text]" placeholder="<?php esc_attr_e( 'Button label', 'davenham-builder' ); ?>" value="<?php echo esc_attr( $settings['popup_button_text'] ); ?>" /> <input type="url" class="regular-text" name="davenham_builder_site_settings[popup_button_url]" placeholder="https://…" value="<?php echo esc_attr( $settings['popup_button_url'] ); ?>" /></td></tr>
					<tr><th scope="row"><label><?php esc_html_e( 'Delay', 'davenham-builder' ); ?></label></th><td><input type="number" min="0" max="60" class="small-text" name="davenham_builder_site_settings[popup_delay]" value="<?php echo esc_attr( (string) $settings['popup_delay'] ); ?>" /> <?php esc_html_e( 'seconds after page load', 'davenham-builder' ); ?></td></tr>
				</table>
			</section>

			<section class="db-settings-card">
				<h2><?php esc_html_e( 'Newsletter Defaults', 'davenham-builder' ); ?></h2>
				<p class="db-settings-card__desc"><?php esc_html_e( 'Default copy used by the newsletter strip and Newsletter Signup block when they have no per-instance content set.', 'davenham-builder' ); ?></p>
				<table class="form-table" role="presentation">
					<tr><th scope="row"><label><?php esc_html_e( 'Newsletter title', 'davenham-builder' ); ?></label></th><td><input type="text" class="large-text" name="davenham_builder_site_settings[newsletter_title]" value="<?php echo esc_attr( $settings['newsletter_title'] ); ?>" /></td></tr>
					<tr><th scope="row"><label><?php esc_html_e( 'Newsletter text', 'davenham-builder' ); ?></label></th><td><textarea class="large-text" rows="3" name="davenham_builder_site_settings[newsletter_text]"><?php echo esc_textarea( $settings['newsletter_text'] ); ?></textarea></td></tr>
					<tr><th scope="row"><label><?php esc_html_e( 'Embed / shortcode', 'davenham-builder' ); ?></label></th><td><textarea class="large-text code" rows="5" name="davenham_builder_site_settings[newsletter_embed]"><?php echo esc_textarea( $settings['newsletter_embed'] ); ?></textarea><p class="description"><?php esc_html_e( 'Paste a Mailchimp / ConvertKit / etc. embed code, or a WordPress shortcode. Leave blank to use the fallback CTA below.', 'davenham-builder' ); ?></p></td></tr>
					<tr><th scope="row"><label><?php esc_html_e( 'Fallback CTA', 'davenham-builder' ); ?></label></th><td><input type="text" class="regular-text" name="davenham_builder_site_settings[newsletter_button_text]" placeholder="<?php esc_attr_e( 'Button label', 'davenham-builder' ); ?>" value="<?php echo esc_attr( $settings['newsletter_button_text'] ); ?>" /> <input type="url" class="regular-text" name="davenham_builder_site_settings[newsletter_button_url]" placeholder="https://…" value="<?php echo esc_attr( $settings['newsletter_button_url'] ); ?>" /></td></tr>
				</table>
			</section>

			<?php submit_button( __( 'Save Site Settings', 'davenham-builder' ) ); ?>
		</form>
	</div>
	<?php
}

// ─── Enqueue assets on the builder admin page only ────────────────────────────
add_action( 'admin_enqueue_scripts', 'db_enqueue_builder_assets' );
function db_enqueue_builder_assets( $hook ) {
	if ( false === strpos( $hook, 'davenham-builder' ) ) {
		return;
	}

	wp_enqueue_media();

	wp_enqueue_style(
		'davenham-builder-css',
		DB_URL . 'assets/builder.css',
		array(),
		DB_VERSION
	);

	wp_enqueue_script(
		'davenham-builder-js',
		DB_URL . 'assets/builder.js',
		array( 'wp-element', 'wp-api-fetch', 'wp-components', 'wp-i18n', 'media-editor', 'media-models', 'media-views' ),
		DB_VERSION,
		true
	);

	wp_enqueue_script(
		'davenham-builder-site-settings',
		DB_URL . 'assets/site-settings.js',
		array( 'jquery', 'media-editor', 'media-models', 'media-views' ),
		DB_VERSION,
		true
	);

	// Pass the REST root and a nonce to JS.
	// The builder uses the standard /wp/v2/pages REST API — no custom routes.
	wp_localize_script( 'davenham-builder-js', 'dbConfig', array(
		'nonce'    => wp_create_nonce( 'wp_rest' ),
		'adminUrl' => admin_url(),
		'siteUrl'  => get_site_url(),
		'restUrl'  => get_rest_url(),   // e.g. https://example.com/wp-json/
		'version'  => DB_VERSION,
	) );
}

// ─── "⚜ Builder" row action on Pages list ────────────────────────────────────
add_filter( 'page_row_actions', 'db_page_row_action', 10, 2 );
function db_page_row_action( $actions, $post ) {
	$url = admin_url( 'admin.php?page=davenham-builder&post_id=' . $post->ID );
	$actions['davenham_builder'] = '<a href="' . esc_url( $url ) . '" style="color:#590FA9;font-weight:600;">⚜ Builder</a>';
	return $actions;
}

// ─── Enqueue frontend block styles on public pages ───────────────────────────
add_action( 'wp_enqueue_scripts', 'db_enqueue_frontend_styles' );
function db_enqueue_frontend_styles() {
	wp_enqueue_style(
		'davenham-blocks-css',
		DB_URL . 'assets/blocks.css',
		array(),
		DB_VERSION
	);

	wp_enqueue_script(
		'davenham-blocks-js',
		DB_URL . 'assets/blocks.js',
		array(),
		DB_VERSION,
		true
	);
}

// ─── Shared frontend wrapper for advanced builder controls ──────────────────
if ( ! function_exists( 'db_wrap_davenham_block_output' ) ) {
	add_filter( 'render_block', 'db_wrap_davenham_block_output', 20, 2 );
	function db_wrap_davenham_block_output( $block_content, $block ) {
		if ( empty( $block_content ) || empty( $block['blockName'] ) || 0 !== strpos( $block['blockName'], 'davenham/' ) ) {
			return $block_content;
		}

		$attrs = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();
		$scope = 'db-scope-' . substr( md5( wp_json_encode( $attrs ) . $block['blockName'] ), 0, 10 );
		$slug  = sanitize_html_class( str_replace( 'davenham/', '', $block['blockName'] ) );
		$id    = '';

		if ( ! empty( $attrs['anchorId'] ) ) {
			$id = sanitize_title( $attrs['anchorId'] );
		}

		$classes = array(
			'db-block-shell',
			'db-block-shell--' . $slug,
			$scope,
		);

		if ( ! empty( $attrs['customClassName'] ) ) {
			foreach ( preg_split( '/\s+/', (string) $attrs['customClassName'] ) as $class_name ) {
				$class_name = sanitize_html_class( $class_name );
				if ( '' !== $class_name ) {
					$classes[] = $class_name;
				}
			}
		}

		$styles = array();

		if ( ! empty( $attrs['backgroundColor'] ) ) {
			$styles[] = '--db-bg-color:' . sanitize_hex_color( $attrs['backgroundColor'] );
		}
		if ( ! empty( $attrs['textColor'] ) ) {
			$styles[] = '--db-text-color:' . sanitize_hex_color( $attrs['textColor'] );
		}
		if ( ! empty( $attrs['headingColor'] ) ) {
			$styles[] = '--db-heading-color:' . sanitize_hex_color( $attrs['headingColor'] );
		}
		if ( ! empty( $attrs['linkColor'] ) ) {
			$styles[] = '--db-link-color:' . sanitize_hex_color( $attrs['linkColor'] );
		}
		if ( ! empty( $attrs['textAlign'] ) && in_array( $attrs['textAlign'], array( 'left', 'center', 'right' ), true ) ) {
			$styles[] = '--db-text-align:' . $attrs['textAlign'];
		}
		if ( isset( $attrs['paddingTop'] ) && '' !== $attrs['paddingTop'] ) {
			$styles[] = '--db-padding-top:' . intval( $attrs['paddingTop'] ) . 'px';
		}
		if ( isset( $attrs['paddingBottom'] ) && '' !== $attrs['paddingBottom'] ) {
			$styles[] = '--db-padding-bottom:' . intval( $attrs['paddingBottom'] ) . 'px';
		}
		if ( isset( $attrs['maxWidth'] ) && '' !== $attrs['maxWidth'] ) {
			$styles[] = '--db-max-width:' . max( 320, intval( $attrs['maxWidth'] ) ) . 'px';
		}
		if ( isset( $attrs['minWidth'] ) && '' !== $attrs['minWidth'] ) {
			$styles[] = '--db-min-width:' . max( 0, intval( $attrs['minWidth'] ) ) . 'px';
		}

		$style_attr = ! empty( $styles ) ? ' style="' . esc_attr( implode( ';', $styles ) ) . '"' : '';
		$id_attr    = '' !== $id ? ' id="' . esc_attr( $id ) . '"' : '';
		$css_markup = db_scoped_custom_css_markup( $attrs['customCss'] ?? '', '.' . $scope );

		return sprintf(
			'<div%1$s class="%2$s"%3$s>%4$s</div>%5$s',
			$id_attr,
			esc_attr( implode( ' ', array_unique( $classes ) ) ),
			$style_attr,
			$block_content,
			$css_markup
		);
	}
}

if ( ! function_exists( 'db_scoped_custom_css_markup' ) ) {
	function db_scoped_custom_css_markup( $raw_css, $scope_selector ) {
		$raw_css = trim( (string) $raw_css );
		if ( '' === $raw_css ) {
			return '';
		}

		$raw_css = wp_strip_all_tags( $raw_css );
		if ( false !== strpos( $raw_css, '{' ) ) {
			$scoped_css = str_replace( '&', $scope_selector, $raw_css );
		} else {
			$scoped_css = $scope_selector . '{' . $raw_css . '}';
		}

		return '<style>' . $scoped_css . '</style>';
	}
}

// ─── Notice if old blocks plugin is still active ──────────────────────────────
add_action( 'admin_notices', 'db_old_plugin_notice' );
function db_old_plugin_notice() {
	if ( ! function_exists( 'is_plugin_active' ) ) {
		return;
	}
	if ( is_plugin_active( 'davenham-blocks/davenham-blocks.php' ) ) {
		echo '<div class="notice notice-warning is-dismissible"><p>'
			. '<strong>Davenham Builder:</strong> Please deactivate and delete the old <em>Davenham Blocks</em> plugin — its functionality is now built in.'
			. '</p></div>';
	}
}
