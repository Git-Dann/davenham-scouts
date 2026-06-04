<?php
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'SCOUTS_THEME_VERSION', '1.0.6' );
define( 'SCOUTS_THEME_URI', get_template_directory_uri() );
define( 'SCOUTS_THEME_DIR', get_template_directory() );

/**
 * Cache-busting version for theme assets.
 *
 * Returns the file's modification time as a version string so the URL
 * changes every time the asset is updated on disk. Falls back to the
 * theme version if the file is missing (avoids fatal errors in edge cases).
 */
function scouts_asset_version( $relative_path ) {
    $abs = SCOUTS_THEME_DIR . '/' . ltrim( $relative_path, '/' );
    return file_exists( $abs ) ? (string) filemtime( $abs ) : SCOUTS_THEME_VERSION;
}

function scouts_site_settings_defaults(): array {
    return [
        'logo_url' => '',
        'header_primary_cta_text' => 'Volunteer with Scouts',
        'header_primary_cta_url' => home_url( '/volunteer/' ),
        'header_secondary_cta_text' => 'Join Scouts',
        'header_secondary_cta_url' => home_url( '/join/' ),
        'footer_title' => 'Skills for Life for young people across Davenham through Beavers, Cubs and Scouts.',
        'footer_intro' => 'We are a volunteer-led Scout Group, rooted in the local community and based at Peckmill Scout Wood.',
        'footer_address' => "Davenham\nCheshire\nCW9 8LH",
        'footer_contact_text' => 'Click here',
        'footer_contact_url' => home_url( '/contact/' ),
        'footer_social_facebook' => 'https://www.facebook.com/DavenhamScouts/',
        'footer_social_x' => 'https://twitter.com/1stDavenham',
        'charity_number' => '1029781',
        'cookie_enabled' => '1',
        'cookie_text' => 'We use cookies to keep the website working well and to understand what content is most useful.',
        'cookie_accept_label' => 'Accept',
        'cookie_reject_label' => 'Reject',
        'cookie_policy_label' => 'Read cookies policy',
        'cookie_policy_url' => home_url( '/cookies/' ),
        'popup_enabled' => '0',
        'popup_title' => 'Stay in the loop',
        'popup_content' => '<p>Use this space for a seasonal message, event promo, or newsletter signup.</p>',
        'popup_button_text' => 'Find out more',
        'popup_button_url' => home_url( '/contact/' ),
        'popup_delay' => 4,
        'newsletter_title' => 'Newsletter signup',
        'newsletter_text' => 'Paste your newsletter embed or use a simple contact CTA until your mailing setup is ready.',
        'newsletter_embed' => '',
        'newsletter_button_text' => '',
        'newsletter_button_url' => '',
    ];
}

function scouts_get_site_settings(): array {
    $saved = get_option( 'davenham_builder_site_settings', [] );
    return wp_parse_args( is_array( $saved ) ? $saved : [], scouts_site_settings_defaults() );
}

function scouts_get_site_logo_url(): string {
    $settings = scouts_get_site_settings();
    if ( ! empty( $settings['logo_url'] ) ) {
        return esc_url( $settings['logo_url'] );
    }
    return esc_url( get_template_directory_uri() . '/images/scouts-logo-standard.svg' );
}

function scouts_render_newsletter_strip(): void {
    $settings = scouts_get_site_settings();
    $has_embed  = ! empty( $settings['newsletter_embed'] );
    $has_button = ! empty( $settings['newsletter_button_text'] ) && ! empty( $settings['newsletter_button_url'] );
    if ( ! $has_embed && ! $has_button ) {
        return;
    }
    ?>
    <section class="site-newsletter-strip">
        <div class="wrapper site-newsletter-strip__inner">
            <div class="site-newsletter-strip__copy">
                <?php if ( ! empty( $settings['newsletter_title'] ) ) : ?>
                    <h3><?php echo esc_html( $settings['newsletter_title'] ); ?></h3>
                <?php endif; ?>
                <?php if ( ! empty( $settings['newsletter_text'] ) ) : ?>
                    <p><?php echo esc_html( $settings['newsletter_text'] ); ?></p>
                <?php endif; ?>
            </div>
            <div class="site-newsletter-strip__action">
                <?php if ( ! empty( $settings['newsletter_embed'] ) ) : ?>
                    <?php echo do_shortcode( wp_kses_post( $settings['newsletter_embed'] ) ); ?>
                <?php elseif ( ! empty( $settings['newsletter_button_text'] ) && ! empty( $settings['newsletter_button_url'] ) ) : ?>
                    <a class="site-newsletter-strip__button" href="<?php echo esc_url( $settings['newsletter_button_url'] ); ?>"><?php echo esc_html( $settings['newsletter_button_text'] ); ?></a>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <?php
}

function scouts_render_site_chrome(): void {
    $settings = scouts_get_site_settings();
    ?>
    <?php if ( '1' === (string) $settings['cookie_enabled'] ) : ?>
        <div class="site-cookie-banner" data-cookie-banner hidden>
            <div class="site-cookie-banner__inner wrapper">
                <p><?php echo esc_html( $settings['cookie_text'] ); ?></p>
                <div class="site-cookie-banner__actions">
                    <?php if ( ! empty( $settings['cookie_policy_url'] ) ) : ?>
                        <a href="<?php echo esc_url( $settings['cookie_policy_url'] ); ?>" class="site-cookie-banner__link"><?php echo esc_html( $settings['cookie_policy_label'] ); ?></a>
                    <?php endif; ?>
                    <button type="button" class="site-cookie-banner__button site-cookie-banner__button--muted" data-cookie-reject><?php echo esc_html( $settings['cookie_reject_label'] ); ?></button>
                    <button type="button" class="site-cookie-banner__button" data-cookie-accept><?php echo esc_html( $settings['cookie_accept_label'] ); ?></button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ( '1' === (string) $settings['popup_enabled'] ) : ?>
        <div class="site-promo-modal" data-site-promo hidden data-delay="<?php echo esc_attr( (string) absint( $settings['popup_delay'] ) ); ?>">
            <div class="site-promo-modal__backdrop" data-site-promo-close></div>
            <div class="site-promo-modal__dialog" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr( $settings['popup_title'] ); ?>">
                <button type="button" class="site-promo-modal__close" data-site-promo-close aria-label="Close popup">×</button>
                <?php if ( ! empty( $settings['popup_title'] ) ) : ?>
                    <h3><?php echo esc_html( $settings['popup_title'] ); ?></h3>
                <?php endif; ?>
                <div class="site-promo-modal__content"><?php echo wp_kses_post( $settings['popup_content'] ); ?></div>
                <?php if ( ! empty( $settings['popup_button_text'] ) && ! empty( $settings['popup_button_url'] ) ) : ?>
                    <a class="site-promo-modal__button" href="<?php echo esc_url( $settings['popup_button_url'] ); ?>"><?php echo esc_html( $settings['popup_button_text'] ); ?></a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <script>
        (function () {
            var cookieKey = 'davenhamCookieChoice';
            var popupKey = 'davenhamPromoSeen';
            var banner = document.querySelector('[data-cookie-banner]');
            var modal = document.querySelector('[data-site-promo]');

            if (banner && !window.localStorage.getItem(cookieKey)) {
                banner.hidden = false;
                var accept = banner.querySelector('[data-cookie-accept]');
                var reject = banner.querySelector('[data-cookie-reject]');
                var closeBanner = function (choice) {
                    window.localStorage.setItem(cookieKey, choice);
                    banner.hidden = true;
                };
                if (accept) accept.addEventListener('click', function () { closeBanner('accepted'); });
                if (reject) reject.addEventListener('click', function () { closeBanner('rejected'); });
            }

            if (modal && !window.localStorage.getItem(popupKey)) {
                var delay = parseInt(modal.getAttribute('data-delay') || '0', 10) * 1000;
                var closeModal = function () {
                    modal.hidden = true;
                    window.localStorage.setItem(popupKey, 'seen');
                };
                window.setTimeout(function () {
                    modal.hidden = false;
                }, delay);
                modal.querySelectorAll('[data-site-promo-close]').forEach(function (node) {
                    node.addEventListener('click', closeModal);
                });
            }

            // Keep aria-expanded in sync on the hamburger when the mobile menu opens/closes.
            // The existing theme JS toggles a class on body/overlay — we observe that.
            var overlay = document.getElementById('mobile-menu');
            var hamburgers = document.querySelectorAll('.new-header .hamburger');
            if (overlay && hamburgers.length) {
                var syncAria = function () {
                    var isOpen = document.body.classList.contains('menu-open') ||
                                 overlay.classList.contains('open') ||
                                 overlay.getAttribute('aria-hidden') === 'false';
                    hamburgers.forEach(function (h) {
                        h.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                        h.setAttribute('aria-label', isOpen ? 'Close menu' : 'Open menu');
                    });
                    overlay.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
                };
                var observer = new MutationObserver(syncAria);
                observer.observe(document.body, { attributes: true, attributeFilter: ['class'] });
                observer.observe(overlay, { attributes: true, attributeFilter: ['class', 'style'] });
                // Fallback: also sync on click
                hamburgers.forEach(function (h) { h.addEventListener('click', function () { setTimeout(syncAria, 50); }); });
            }
        }());
    </script>
    <?php
}
add_action( 'wp_footer', 'scouts_render_site_chrome', 25 );

function scouts_enqueue_assets() {
    wp_enqueue_style(
        'nunito-sans',
        'https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;600;700;800&display=swap',
        [],
        null
    );
    wp_enqueue_style(
        'scouts-production',
        SCOUTS_THEME_URI . '/production/production.css',
        [ 'nunito-sans' ],
        scouts_asset_version( 'production/production.css' )
    );
    wp_enqueue_style(
        'scouts-theme',
        get_stylesheet_uri(),
        [ 'scouts-production' ],
        scouts_asset_version( 'style.css' )
    );
    wp_enqueue_script(
        'scouts-production',
        SCOUTS_THEME_URI . '/production/production.min.js',
        [ 'jquery' ],
        scouts_asset_version( 'production/production.min.js' ),
        true
    );
    wp_localize_script( 'scouts-production', 'template_url', SCOUTS_THEME_URI );
    wp_localize_script( 'scouts-production', 'website_url', home_url() );
}
add_action( 'wp_enqueue_scripts', 'scouts_enqueue_assets' );

function scouts_admin_assets() {
    wp_enqueue_style(
        'scouts-editor-style',
        SCOUTS_THEME_URI . '/editor-style.css',
        [],
        scouts_asset_version( 'editor-style.css' )
    );
}
add_action( 'enqueue_block_editor_assets', 'scouts_admin_assets' );

function scouts_setup() {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'html5', [ 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption' ] );
    add_theme_support( 'woocommerce' );
    add_theme_support( 'wc-product-gallery-zoom' );
    add_theme_support( 'wc-product-gallery-lightbox' );
    add_theme_support( 'wc-product-gallery-slider' );
    add_theme_support( 'editor-styles' );
    add_theme_support( 'responsive-embeds' );
    add_theme_support( 'align-wide' );
    add_theme_support( 'editor-color-palette', [
        [ 'name' => __( 'Scouts Blue', 'the-scouts-skills-for-life' ), 'slug' => 'scouts-blue', 'color' => '#003982' ],
        [ 'name' => __( 'Scouts Teal', 'the-scouts-skills-for-life' ), 'slug' => 'scouts-teal', 'color' => '#088486' ],
        [ 'name' => __( 'Scouts Orange', 'the-scouts-skills-for-life' ), 'slug' => 'scouts-orange', 'color' => '#f36d00' ],
        [ 'name' => __( 'Scouts Pink', 'the-scouts-skills-for-life' ), 'slug' => 'scouts-pink', 'color' => '#e5007d' ],
        [ 'name' => __( 'Scouts Navy', 'the-scouts-skills-for-life' ), 'slug' => 'scouts-navy', 'color' => '#1c2f45' ],
        [ 'name' => __( 'Soft Grey', 'the-scouts-skills-for-life' ), 'slug' => 'soft-grey', 'color' => '#eff3f7' ],
        [ 'name' => __( 'White', 'the-scouts-skills-for-life' ), 'slug' => 'white', 'color' => '#ffffff' ],
    ] );
    add_theme_support( 'editor-font-sizes', [
        [ 'name' => __( 'Small', 'the-scouts-skills-for-life' ), 'slug' => 'small', 'size' => 14 ],
        [ 'name' => __( 'Body', 'the-scouts-skills-for-life' ), 'slug' => 'body', 'size' => 18 ],
        [ 'name' => __( 'Lead', 'the-scouts-skills-for-life' ), 'slug' => 'lead', 'size' => 24 ],
        [ 'name' => __( 'Headline', 'the-scouts-skills-for-life' ), 'slug' => 'headline', 'size' => 40 ],
    ] );
    add_editor_style( 'editor-style.css' );

    register_nav_menus( [
        'primary'   => __( 'Primary Navigation', 'the-scouts-skills-for-life' ),
        'secondary' => __( 'Top Navigation', 'the-scouts-skills-for-life' ),
        'footer'    => __( 'Footer Links', 'the-scouts-skills-for-life' ),
    ] );
}
add_action( 'after_setup_theme', 'scouts_setup' );

function scouts_editor_setup() {
    add_post_type_support( 'page', 'excerpt' );
    remove_post_type_support( 'post', 'comments' );
    remove_post_type_support( 'page', 'comments' );
}
add_action( 'init', 'scouts_editor_setup' );

function scouts_disable_comments_everywhere() {
    foreach ( get_post_types_by_support( 'comments' ) as $post_type ) {
        remove_post_type_support( $post_type, 'comments' );
        remove_post_type_support( $post_type, 'trackbacks' );
    }
}
add_action( 'admin_init', 'scouts_disable_comments_everywhere' );

add_filter( 'comments_open', '__return_false', 20, 2 );
add_filter( 'pings_open', '__return_false', 20, 2 );
add_filter( 'comments_array', '__return_empty_array', 10, 2 );

function scouts_remove_comment_admin_ui() {
    remove_menu_page( 'edit-comments.php' );
}
add_action( 'admin_menu', 'scouts_remove_comment_admin_ui' );

function scouts_cleanup_admin_bar( WP_Admin_Bar $wp_admin_bar ) {
    $wp_admin_bar->remove_node( 'comments' );
}
add_action( 'admin_bar_menu', 'scouts_cleanup_admin_bar', 999 );

function scouts_dashboard_widget() {
    echo '<p><strong>How editing works on this site</strong></p>';
    echo '<ul style="list-style:disc; padding-left:18px;">';
    echo '<li>Use the page <strong>Excerpt</strong> as the short intro under the hero title.</li>';
    echo '<li>Use the <strong>Featured image</strong> for the hero image instead of placing the first image at the top of the page.</li>';
    echo '<li>Use <strong>Pages</strong> for permanent site sections and <strong>Posts</strong> for news updates.</li>';
    echo '<li>Use Kadence blocks and the built-in <strong>Scouts</strong> block patterns to add structured sections quickly.</li>';
    echo '<li>Keep the main page title, hero image, and intro simple. Build the rest of the layout inside the editor.</li>';
    echo '</ul>';
}

function scouts_register_dashboard_widget() {
    wp_add_dashboard_widget(
        'scouts_editing_guide',
        'Scouts Editing Guide',
        'scouts_dashboard_widget'
    );
}
add_action( 'wp_dashboard_setup', 'scouts_register_dashboard_widget' );

function scouts_cleanup_dashboard() {
    remove_meta_box( 'dashboard_site_health', 'dashboard', 'normal' );
    remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );
    remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );
    remove_meta_box( 'dashboard_activity', 'dashboard', 'normal' );
}
add_action( 'wp_dashboard_setup', 'scouts_cleanup_dashboard', 20 );

function scouts_remove_plugin_clutter_for_editors() {
    if ( current_user_can( 'manage_options' ) ) {
        return;
    }

    remove_menu_page( 'backuply' );
    remove_menu_page( 'loginizer' );
    remove_menu_page( 'site-seo-dashboard' );
    remove_menu_page( 'tools.php?page=site-seo-dashboard' );
}
add_action( 'admin_menu', 'scouts_remove_plugin_clutter_for_editors', 999 );

function scouts_register_block_patterns() {
    if ( ! function_exists( 'register_block_pattern' ) ) {
        return;
    }

    if ( function_exists( 'register_block_pattern_category' ) ) {
        register_block_pattern_category(
            'scouts',
            [ 'label' => __( 'Scouts', 'the-scouts-skills-for-life' ) ]
        );
    }

    register_block_pattern(
        'the-scouts-skills-for-life/scouts-callout',
        [
            'title'       => __( 'Scouts Callout', 'the-scouts-skills-for-life' ),
            'categories'  => [ 'scouts' ],
            'description' => __( 'A branded callout box for important information or guidance.', 'the-scouts-skills-for-life' ),
            'content'     => '<!-- wp:group {"style":{"spacing":{"padding":{"top":"24px","right":"24px","bottom":"24px","left":"24px"}},"border":{"left":{"color":"#088486","width":"4px"},"radius":"8px"}},"backgroundColor":"cyan-bluish-gray","layout":{"type":"constrained"}} --><div class="wp-block-group has-cyan-bluish-gray-background-color has-background" style="border-radius:8px;border-left-color:#088486;border-left-width:4px;padding-top:24px;padding-right:24px;padding-bottom:24px;padding-left:24px"><!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"24px"}},"textColor":"black"} --><h3 class="wp-block-heading has-black-color has-text-color" style="font-size:24px">Important information</h3><!-- /wp:heading --><!-- wp:paragraph --><p>Add the key update, instruction or note here so parents, volunteers or supporters can spot it quickly.</p><!-- /wp:paragraph --></div><!-- /wp:group -->',
        ]
    );

    register_block_pattern(
        'the-scouts-skills-for-life/scouts-two-column-links',
        [
            'title'       => __( 'Scouts Two-Column Links', 'the-scouts-skills-for-life' ),
            'categories'  => [ 'scouts' ],
            'description' => __( 'Two simple linked cards for common section or action links.', 'the-scouts-skills-for-life' ),
            'content'     => '<!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"24px"}}}} --><div class="wp-block-columns"><!-- wp:column --><div class="wp-block-column"><!-- wp:group {"style":{"spacing":{"padding":{"top":"24px","right":"24px","bottom":"24px","left":"24px"}},"border":{"radius":"8px","width":"1px","color":"#d7dfeb"}},"layout":{"type":"constrained"}} --><div class="wp-block-group has-border-color" style="border-color:#d7dfeb;border-width:1px;border-radius:8px;padding-top:24px;padding-right:24px;padding-bottom:24px;padding-left:24px"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Section or resource</h3><!-- /wp:heading --><!-- wp:paragraph --><p>Summarise the destination or resource in one short sentence.</p><!-- /wp:paragraph --><!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button {"backgroundColor":"vivid-cyan-blue"} --><div class="wp-block-button"><a class="wp-block-button__link has-vivid-cyan-blue-background-color has-background wp-element-button">Add link</a></div><!-- /wp:button --></div><!-- /wp:buttons --></div><!-- /wp:group --></div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column"><!-- wp:group {"style":{"spacing":{"padding":{"top":"24px","right":"24px","bottom":"24px","left":"24px"}},"border":{"radius":"8px","width":"1px","color":"#d7dfeb"}},"layout":{"type":"constrained"}} --><div class="wp-block-group has-border-color" style="border-color:#d7dfeb;border-width:1px;border-radius:8px;padding-top:24px;padding-right:24px;padding-bottom:24px;padding-left:24px"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Another useful link</h3><!-- /wp:heading --><!-- wp:paragraph --><p>Use this for a second action, download or contact route.</p><!-- /wp:paragraph --><!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button {"backgroundColor":"vivid-cyan-blue"} --><div class="wp-block-button"><a class="wp-block-button__link has-vivid-cyan-blue-background-color has-background wp-element-button">Add link</a></div><!-- /wp:button --></div><!-- /wp:buttons --></div><!-- /wp:group --></div><!-- /wp:column --></div><!-- /wp:columns -->',
        ]
    );

    register_block_pattern(
        'the-scouts-skills-for-life/scouts-shop-notice',
        [
            'title'       => __( 'Scouts Shop Notice', 'the-scouts-skills-for-life' ),
            'categories'  => [ 'scouts' ],
            'description' => __( 'A branded notice for shop, tickets, collection details or stock updates.', 'the-scouts-skills-for-life' ),
            'content'     => '<!-- wp:group {"style":{"spacing":{"padding":{"top":"28px","right":"28px","bottom":"28px","left":"28px"}},"border":{"radius":"12px","width":"1px","color":"#d7dfeb"}},"backgroundColor":"white","layout":{"type":"constrained"}} --><div class="wp-block-group has-white-background-color has-background has-border-color" style="border-color:#d7dfeb;border-width:1px;border-radius:12px;padding-top:28px;padding-right:28px;padding-bottom:28px;padding-left:28px"><!-- wp:paragraph {"style":{"typography":{"textTransform":"uppercase","letterSpacing":"1px","fontStyle":"normal","fontWeight":"700","fontSize":"12px"}},"textColor":"vivid-cyan-blue"} --><p class="has-vivid-cyan-blue-color has-text-color" style="font-size:12px;font-style:normal;font-weight:700;letter-spacing:1px;text-transform:uppercase">Shop update</p><!-- /wp:paragraph --><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Important ordering information</h3><!-- /wp:heading --><!-- wp:paragraph --><p>Use this area for collection instructions, stock updates, event ticket guidance, or links to the official Scout Store.</p><!-- /wp:paragraph --><!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button {"backgroundColor":"black"} --><div class="wp-block-button"><a class="wp-block-button__link has-black-background-color has-background wp-element-button">Add action</a></div><!-- /wp:button --></div><!-- /wp:buttons --></div><!-- /wp:group -->',
        ]
    );

    register_block_pattern(
        'the-scouts-skills-for-life/scouts-three-cards',
        [
            'title'       => __( 'Scouts Three Cards', 'the-scouts-skills-for-life' ),
            'categories'  => [ 'scouts' ],
            'description' => __( 'Three linked cards for key actions, sections, or parent resources.', 'the-scouts-skills-for-life' ),
            'content'     => '<!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"24px"}}}} --><div class="wp-block-columns"><!-- wp:column --><div class="wp-block-column"><!-- wp:group {"style":{"spacing":{"padding":{"top":"24px","right":"24px","bottom":"24px","left":"24px"}},"border":{"radius":"12px","width":"1px","color":"#d7dfeb"}},"backgroundColor":"white"} --><div class="wp-block-group has-white-background-color has-background has-border-color" style="border-color:#d7dfeb;border-width:1px;border-radius:12px;padding-top:24px;padding-right:24px;padding-bottom:24px;padding-left:24px"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Card one</h3><!-- /wp:heading --><!-- wp:paragraph --><p>Summarise the page or action in a short sentence.</p><!-- /wp:paragraph --><!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button {"backgroundColor":"scouts-blue"} --><div class="wp-block-button"><a class="wp-block-button__link has-scouts-blue-background-color has-background wp-element-button">Add link</a></div><!-- /wp:button --></div><!-- /wp:buttons --></div><!-- /wp:group --></div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column"><!-- wp:group {"style":{"spacing":{"padding":{"top":"24px","right":"24px","bottom":"24px","left":"24px"}},"border":{"radius":"12px","width":"1px","color":"#d7dfeb"}},"backgroundColor":"white"} --><div class="wp-block-group has-white-background-color has-background has-border-color" style="border-color:#d7dfeb;border-width:1px;border-radius:12px;padding-top:24px;padding-right:24px;padding-bottom:24px;padding-left:24px"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Card two</h3><!-- /wp:heading --><!-- wp:paragraph --><p>Use this for a second important route or section.</p><!-- /wp:paragraph --><!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button {"backgroundColor":"scouts-teal"} --><div class="wp-block-button"><a class="wp-block-button__link has-scouts-teal-background-color has-background wp-element-button">Add link</a></div><!-- /wp:button --></div><!-- /wp:buttons --></div><!-- /wp:group --></div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column"><!-- wp:group {"style":{"spacing":{"padding":{"top":"24px","right":"24px","bottom":"24px","left":"24px"}},"border":{"radius":"12px","width":"1px","color":"#d7dfeb"}},"backgroundColor":"white"} --><div class="wp-block-group has-white-background-color has-background has-border-color" style="border-color:#d7dfeb;border-width:1px;border-radius:12px;padding-top:24px;padding-right:24px;padding-bottom:24px;padding-left:24px"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Card three</h3><!-- /wp:heading --><!-- wp:paragraph --><p>Add another helpful destination, download, or contact route.</p><!-- /wp:paragraph --><!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button {"backgroundColor":"scouts-orange"} --><div class="wp-block-button"><a class="wp-block-button__link has-scouts-orange-background-color has-background wp-element-button">Add link</a></div><!-- /wp:button --></div><!-- /wp:buttons --></div><!-- /wp:group --></div><!-- /wp:column --></div><!-- /wp:columns -->',
        ]
    );

    register_block_pattern(
        'the-scouts-skills-for-life/scouts-contact-panel',
        [
            'title'       => __( 'Scouts Contact Panel', 'the-scouts-skills-for-life' ),
            'categories'  => [ 'scouts' ],
            'description' => __( 'A simple contact and response-time panel for parents or volunteers.', 'the-scouts-skills-for-life' ),
            'content'     => '<!-- wp:group {"style":{"spacing":{"padding":{"top":"28px","right":"28px","bottom":"28px","left":"28px"}},"border":{"radius":"12px"},"color":{"gradient":"linear-gradient(135deg,rgb(0,57,130) 0%,rgb(8,132,134) 100%)"}},"layout":{"type":"constrained"}} --><div class="wp-block-group has-background" style="border-radius:12px;background:linear-gradient(135deg,rgb(0,57,130) 0%,rgb(8,132,134) 100%);padding-top:28px;padding-right:28px;padding-bottom:28px;padding-left:28px"><!-- wp:paragraph {"style":{"typography":{"textTransform":"uppercase","letterSpacing":"1px","fontStyle":"normal","fontWeight":"700","fontSize":"12px"}},"textColor":"white"} --><p class="has-white-color has-text-color" style="font-size:12px;font-style:normal;font-weight:700;letter-spacing:1px;text-transform:uppercase">Get in touch</p><!-- /wp:paragraph --><!-- wp:heading {"level":3,"textColor":"white"} --><h3 class="wp-block-heading has-white-color has-text-color">We are happy to help</h3><!-- /wp:heading --><!-- wp:paragraph {"textColor":"white"} --><p class="has-white-color has-text-color">Add the best contact route, who should use it, and how quickly you usually reply.</p><!-- /wp:paragraph --><!-- wp:list {"textColor":"white"} --><ul class="has-white-color has-text-color"><li>General enquiries</li><li>Joining questions</li><li>Section-specific help</li></ul><!-- /wp:list --><!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button {"backgroundColor":"white","textColor":"scouts-blue"} --><div class="wp-block-button"><a class="wp-block-button__link has-scouts-blue-color has-white-background-color has-text-color has-background wp-element-button">Add contact action</a></div><!-- /wp:button --></div><!-- /wp:buttons --></div><!-- /wp:group -->',
        ]
    );
}
add_action( 'init', 'scouts_register_block_patterns' );

function scouts_woocommerce_support_copy(): array {
    return [
        'title'   => 'Need help with an order?',
        'content' => 'Use the contact form if you need group-specific uniform guidance, event ticket help, or support with an order before checkout.',
        'cta'     => home_url( '/contact/' ),
        'label'   => 'Contact the team',
    ];
}

function scouts_is_woocommerce_page(): bool {
    return function_exists( 'is_woocommerce' ) && ( is_woocommerce() || is_cart() || is_checkout() || is_account_page() );
}

function scouts_get_woocommerce_hero_data(): array {
    $title = 'Shop';
    $intro = 'Support Davenham Scouts with tickets, fundraising items, and group-specific resources when they are available.';
    $image = '';

    if ( function_exists( 'is_product' ) && is_product() ) {
        $title = get_the_title();
        $intro = has_excerpt() ? get_the_excerpt() : 'Order online or get in touch if you need help before checking out.';
        $image = get_the_post_thumbnail_url( get_the_ID(), 'large' ) ?: '';
    } elseif ( function_exists( 'is_product_category' ) && is_product_category() ) {
        $term = get_queried_object();
        if ( $term && ! is_wp_error( $term ) ) {
            $title = single_term_title( '', false );
            $intro = term_description( $term, 'product_cat' ) ?: 'Browse products in this category.';
        }
    } elseif ( function_exists( 'is_shop' ) && is_shop() ) {
        $shop_id = wc_get_page_id( 'shop' );
        if ( $shop_id > 0 ) {
            $title = get_the_title( $shop_id );
            $intro = get_post_field( 'post_excerpt', $shop_id ) ?: $intro;
            $image = get_the_post_thumbnail_url( $shop_id, 'large' ) ?: '';
        }
    } elseif ( function_exists( 'is_cart' ) && is_cart() ) {
        $title = 'Basket';
        $intro = 'Check the items you have selected before you continue to checkout.';
    } elseif ( function_exists( 'is_checkout' ) && is_checkout() ) {
        $title = 'Checkout';
        $intro = 'Complete your order securely and review your details carefully before placing it.';
    } elseif ( function_exists( 'is_account_page' ) && is_account_page() ) {
        $title = 'My Account';
        $intro = 'View your orders and account details in one place.';
    }

    return [
        'title' => wp_strip_all_tags( $title ),
        'intro' => wp_strip_all_tags( $intro ),
        'image' => $image,
    ];
}

function scouts_customize_woocommerce() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        return;
    }

    remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
    add_filter( 'woocommerce_enqueue_styles', '__return_empty_array' );
}
add_action( 'wp', 'scouts_customize_woocommerce' );

/**
 * Brand WooCommerce transactional emails with Scouts purple.
 * Filters apply at plugin load — no need for is_woocommerce() guard.
 */
function scouts_woocommerce_email_options( $value, $option = '' ) {
    // Used as a single-arg filter; we just override values directly via add_filter below.
    return $value;
}

add_filter( 'pre_option_woocommerce_email_base_color',         function () { return '#590FA9'; } );
add_filter( 'pre_option_woocommerce_email_background_color',   function () { return '#F1F1F1'; } );
add_filter( 'pre_option_woocommerce_email_body_background_color', function () { return '#FFFFFF'; } );
add_filter( 'pre_option_woocommerce_email_text_color',         function () { return '#404040'; } );
add_filter( 'pre_option_woocommerce_email_footer_text',        function () { return '1st Davenham Scout Group · Registered charity 1029781 · davenhamscouts.org.uk'; } );
add_filter( 'pre_option_woocommerce_email_from_name',          function ( $value ) {
    return $value && $value !== '' ? $value : '1st Davenham Scouts';
}, 10, 1 );

/**
 * Helper: returns the four canonical shop product categories.
 * Used by the seeder and by the Product Grid block to suggest filters.
 */
function scouts_shop_canonical_categories(): array {
    return array(
        'event-tickets'      => array( 'name' => 'Event Tickets',      'description' => 'Camps, trips, fairs, and ticketed group events.' ),
        'group-merchandise'  => array( 'name' => 'Group Merchandise',  'description' => 'Davenham-branded clothing and accessories — neckers, hoodies, polos.' ),
        'fundraising'        => array( 'name' => 'Fundraising',        'description' => 'Calendars, raffles, and one-off items that fund our adventures.' ),
        'equipment-kit'      => array( 'name' => 'Equipment & Kit',    'description' => 'Section-specific kit, badge packs, and resources we sell directly.' ),
    );
}

function scouts_register_submission_cpt() {
    register_post_type( 'scouts_submission', [
        'labels' => [
            'name'          => __( 'Form Submissions', 'the-scouts-skills-for-life' ),
            'singular_name' => __( 'Form Submission', 'the-scouts-skills-for-life' ),
        ],
        'public'              => false,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'menu_position'       => 26,
        'menu_icon'           => 'dashicons-email-alt',
        'supports'            => [ 'title', 'editor', 'custom-fields' ],
        'capability_type'     => 'post',
        'exclude_from_search' => true,
    ] );
}
add_action( 'init', 'scouts_register_submission_cpt' );

function scouts_head_vars() {
    echo '<script>var template_url = "' . esc_js( SCOUTS_THEME_URI ) . '";</script>' . "\n";
    echo '<script>var website_url = "' . esc_js( home_url() ) . '";</script>' . "\n";
}
add_action( 'wp_head', 'scouts_head_vars', 1 );

function scouts_favicon() {
    echo '<link rel="icon" href="' . esc_url( SCOUTS_THEME_URI . '/images/favicon.png' ) . '" />' . "\n";
}
add_action( 'wp_head', 'scouts_favicon' );

add_filter( 'body_class', function( $classes ) {
    $classes[] = 'no-js';
    return $classes;
} );

function scouts_form_flash_key( string $form_type ): string {
    return 'scouts_form_flash_' . $form_type . '_' . wp_generate_password( 12, false, false );
}

function scouts_store_form_flash( string $form_type, array $data ): string {
    $key = scouts_form_flash_key( $form_type );
    set_transient( $key, $data, 10 * MINUTE_IN_SECONDS );
    return $key;
}

function scouts_get_form_flash( string $form_type ): array {
    static $cache = [];

    if ( array_key_exists( $form_type, $cache ) ) {
        return $cache[ $form_type ];
    }

    $token = isset( $_GET['form_token'] ) ? sanitize_text_field( wp_unslash( $_GET['form_token'] ) ) : '';
    if ( ! $token || strpos( $token, 'scouts_form_flash_' . $form_type . '_' ) !== 0 ) {
        $cache[ $form_type ] = [];
        return $cache[ $form_type ];
    }

    $data = get_transient( $token );
    if ( $data ) {
        delete_transient( $token );
    }

    $cache[ $form_type ] = is_array( $data ) ? $data : [];
    return $cache[ $form_type ];
}

function scouts_form_redirect_target(): string {
    $posted_redirect = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : '';
    if ( $posted_redirect ) {
        return $posted_redirect;
    }

    return wp_get_referer() ?: home_url( '/' );
}

function scouts_redirect_with_flash( string $form_type, array $payload, string $status ): void {
    $token = scouts_store_form_flash( $form_type, $payload );
    $redirect = add_query_arg(
        [
            'form_status' => $status,
            'form_token'  => $token,
        ],
        scouts_form_redirect_target()
    );
    wp_safe_redirect( $redirect );
    exit;
}

function scouts_create_submission( string $form_type, array $fields ): int {
    $title_bits = [ ucfirst( $form_type ) . ' submission' ];

    if ( ! empty( $fields['name'] ) ) {
        $title_bits[] = $fields['name'];
    } elseif ( ! empty( $fields['young_person_name'] ) ) {
        $title_bits[] = $fields['young_person_name'];
    }

    $body = '';
    foreach ( $fields as $label => $value ) {
        if ( $value === '' || $value === [] || $value === null ) {
            continue;
        }

        $pretty_label = ucwords( str_replace( '_', ' ', $label ) );
        $pretty_value = is_array( $value ) ? implode( ', ', $value ) : $value;
        $body        .= $pretty_label . ": " . $pretty_value . "\n";
    }

    return wp_insert_post( [
        'post_type'    => 'scouts_submission',
        'post_status'  => 'private',
        'post_title'   => implode( ' - ', $title_bits ),
        'post_content' => $body,
    ] );
}

/**
 * Send a form submission notification.
 *
 * Issues that caused this to silently fail on Krystal shared hosting:
 *   1. Recipient was hard-coded to admin_email; the user needs a different
 *      monitored inbox (now configurable via Site Settings).
 *   2. No From: header — wp_mail defaults to wordpress@<host>, which many
 *      shared-hosting MTAs reject (no matching SPF record). We now set
 *      From to a no-reply at the site domain so SPF passes.
 *   3. No Reply-To — replies went into the void. Now points at the
 *      visitor's own email.
 *   4. No failure handling — silent failures were impossible to diagnose.
 *      We now log to scouts_form_email_failures option for an admin notice.
 *
 * @param string                       $subject     Email subject.
 * @param string                       $body        Plain-text body.
 * @param array<string,string|null>    $reply_to    Optional [name, email] for Reply-To.
 */
function scouts_send_submission_email( string $subject, string $body, array $reply_to = array() ): void {
    // Resolve recipient — prefer the configured notifications inbox, fall
    // back to admin_email so installs without configured settings still work.
    $to = '';
    if ( function_exists( 'db_get_site_settings' ) ) {
        $settings = db_get_site_settings();
        if ( ! empty( $settings['form_notifications_email'] ) ) {
            $to = $settings['form_notifications_email'];
        }
    }
    if ( '' === $to ) {
        $to = (string) get_option( 'admin_email', '' );
    }
    if ( '' === $to || ! is_email( $to ) ) {
        return;
    }

    $site_host = wp_parse_url( home_url( '/' ), PHP_URL_HOST ) ?: 'davenhamscouts.org.uk';
    $from_name = wp_specialchars_decode( (string) get_bloginfo( 'name' ), ENT_QUOTES ) ?: '1st Davenham Scouts';
    $from_addr = 'no-reply@' . preg_replace( '/^www\./', '', $site_host );

    $headers = array(
        sprintf( 'From: %s <%s>', $from_name, $from_addr ),
    );

    if ( ! empty( $reply_to['email'] ) && is_email( $reply_to['email'] ) ) {
        $reply_name = ! empty( $reply_to['name'] ) ? $reply_to['name'] : '';
        $headers[] = $reply_name
            ? sprintf( 'Reply-To: %s <%s>', $reply_name, $reply_to['email'] )
            : sprintf( 'Reply-To: %s', $reply_to['email'] );
    }

    $ok = wp_mail( $to, $subject, $body, $headers );

    if ( ! $ok ) {
        $log = get_option( 'scouts_form_email_failures', array() );
        if ( ! is_array( $log ) ) { $log = array(); }
        array_unshift( $log, array(
            'when'    => current_time( 'mysql' ),
            'to'      => $to,
            'subject' => $subject,
        ) );
        // Keep only the last 20 failures.
        $log = array_slice( $log, 0, 20 );
        update_option( 'scouts_form_email_failures', $log, false );
    }
}

/**
 * Capture wp_mail PHPMailer errors into a transient so we can show them
 * on the admin Site Settings screen when there's an SMTP-level issue
 * (host blocking, bad headers, etc.).
 */
add_action( 'wp_mail_failed', 'scouts_capture_wp_mail_failure' );
function scouts_capture_wp_mail_failure( $error ) {
    if ( $error instanceof WP_Error ) {
        set_transient(
            'scouts_form_email_last_error',
            $error->get_error_message(),
            DAY_IN_SECONDS
        );
    }
}

/**
 * Admin notice on the site settings page when there's a recent wp_mail
 * failure (so the admin knows to investigate rather than wondering why
 * no emails are arriving).
 */
add_action( 'admin_notices', 'scouts_render_form_email_failure_notice' );
function scouts_render_form_email_failure_notice() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
    if ( ! $screen || false === strpos( (string) $screen->id, 'davenham-builder' ) ) {
        return;
    }
    $last_error = get_transient( 'scouts_form_email_last_error' );
    if ( ! $last_error ) {
        return;
    }
    ?>
    <div class="notice notice-error">
        <p><strong>Form notifications are failing.</strong> The last wp_mail error was: <code><?php echo esc_html( (string) $last_error ); ?></code></p>
        <p>Check your hosting provider's email policy — many shared hosts (including Krystal) reject mail from PHP unless From matches the site domain. The fix is usually to use an SMTP plugin (WP Mail SMTP) with your Google Workspace / Gmail account.</p>
    </div>
    <?php
}

function scouts_handle_contact_submission(): void {
    $values = [
        'first_name' => sanitize_text_field( wp_unslash( $_POST['first_name'] ?? '' ) ),
        'surname'    => sanitize_text_field( wp_unslash( $_POST['surname'] ?? '' ) ),
        'email'      => sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ),
        'phone'      => sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) ),
        'message'    => sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) ),
    ];

    $errors = [];

    if ( $values['first_name'] === '' ) {
        $errors['first_name'] = 'Please enter a first name.';
    }
    if ( $values['surname'] === '' ) {
        $errors['surname'] = 'Please enter a surname.';
    }
    if ( ! is_email( $values['email'] ) ) {
        $errors['email'] = 'Please enter a valid email address.';
    }
    if ( $values['message'] === '' ) {
        $errors['message'] = 'Please enter a message.';
    }

    if ( $errors ) {
        scouts_redirect_with_flash( 'contact', [ 'errors' => $errors, 'values' => $values ], 'error' );
    }

    $full_name = trim( $values['first_name'] . ' ' . $values['surname'] );
    $submission_fields = [
        'name'    => $full_name,
        'email'   => $values['email'],
        'phone'   => $values['phone'],
        'message' => $values['message'],
    ];
    scouts_create_submission( 'contact', $submission_fields );
    scouts_send_submission_email(
        'New contact enquiry from Davenham Scouts website',
        "Name: {$full_name}\nEmail: {$values['email']}\nPhone: {$values['phone']}\n\nMessage:\n{$values['message']}\n",
        array( 'name' => $full_name, 'email' => $values['email'] )
    );

    scouts_redirect_with_flash( 'contact', [ 'values' => [] ], 'success' );
}

function scouts_handle_join_submission(): void {
    $values = [
        'join_type'           => sanitize_text_field( wp_unslash( $_POST['join_type'] ?? '' ) ),
        'young_person_name'   => sanitize_text_field( wp_unslash( $_POST['young_person_name'] ?? '' ) ),
        'date_of_birth'       => sanitize_text_field( wp_unslash( $_POST['date_of_birth'] ?? '' ) ),
        'parent_carer_name'   => sanitize_text_field( wp_unslash( $_POST['parent_carer_name'] ?? '' ) ),
        'adult_name'          => sanitize_text_field( wp_unslash( $_POST['adult_name'] ?? '' ) ),
        'email'               => sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ),
        'phone'               => sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) ),
        'postcode'            => sanitize_text_field( wp_unslash( $_POST['postcode'] ?? '' ) ),
        'preferred_section'   => sanitize_text_field( wp_unslash( $_POST['preferred_section'] ?? '' ) ),
        'volunteer_interest'  => array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['volunteer_interest'] ?? [] ) ),
        'message'             => sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) ),
    ];

    $errors = [];

    if ( ! in_array( $values['join_type'], [ 'Young Person', 'Adult Volunteer' ], true ) ) {
        $errors['join_type'] = 'Please choose what you are enquiring about.';
    }
    if ( ! is_email( $values['email'] ) ) {
        $errors['email'] = 'Please enter a valid email address.';
    }

    if ( $values['join_type'] === 'Young Person' ) {
        if ( $values['young_person_name'] === '' ) {
            $errors['young_person_name'] = 'Please enter the young person’s name.';
        }
        if ( $values['parent_carer_name'] === '' ) {
            $errors['parent_carer_name'] = 'Please enter a parent or carer name.';
        }
    }

    if ( $values['join_type'] === 'Adult Volunteer' && $values['adult_name'] === '' ) {
        $errors['adult_name'] = 'Please enter your name.';
    }

    if ( $errors ) {
        scouts_redirect_with_flash( 'join', [ 'errors' => $errors, 'values' => $values ], 'error' );
    }

    $submission_fields = [
        'join_type'          => $values['join_type'],
        'young_person_name'  => $values['young_person_name'],
        'date_of_birth'      => $values['date_of_birth'],
        'parent_carer_name'  => $values['parent_carer_name'],
        'adult_name'         => $values['adult_name'],
        'email'              => $values['email'],
        'phone'              => $values['phone'],
        'postcode'           => $values['postcode'],
        'preferred_section'  => $values['preferred_section'],
        'volunteer_interest' => $values['volunteer_interest'],
        'message'            => $values['message'],
        'name'               => $values['join_type'] === 'Adult Volunteer' ? $values['adult_name'] : $values['young_person_name'],
    ];

    scouts_create_submission( 'join', $submission_fields );
    $contact_name = $values['join_type'] === 'Adult Volunteer'
        ? ( $values['adult_name'] ?: $values['parent_carer_name'] )
        : ( $values['parent_carer_name'] ?: $values['adult_name'] );
    scouts_send_submission_email(
        'New join enquiry from Davenham Scouts website',
        "Join type: {$values['join_type']}\nYoung person: {$values['young_person_name']}\nAdult: {$values['adult_name']}\nParent/Carer: {$values['parent_carer_name']}\nDate of birth: {$values['date_of_birth']}\nEmail: {$values['email']}\nPhone: {$values['phone']}\nPostcode: {$values['postcode']}\nPreferred section: {$values['preferred_section']}\nVolunteer interests: " . implode( ', ', $values['volunteer_interest'] ) . "\n\nMessage:\n{$values['message']}\n",
        array( 'name' => $contact_name, 'email' => $values['email'] )
    );

    scouts_redirect_with_flash( 'join', [ 'values' => [] ], 'success' );
}

function scouts_handle_forms(): void {
    if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
        return;
    }

    $form_type = sanitize_text_field( wp_unslash( $_POST['scouts_form_type'] ?? '' ) );
    if ( ! in_array( $form_type, [ 'contact', 'join' ], true ) ) {
        return;
    }

    if ( ! empty( $_POST['website'] ) ) {
        scouts_redirect_with_flash( $form_type, [ 'values' => [] ], 'success' );
    }

    $nonce_action = 'scouts_form_' . $form_type;
    $nonce = sanitize_text_field( wp_unslash( $_POST['scouts_form_nonce'] ?? '' ) );
    if ( ! wp_verify_nonce( $nonce, $nonce_action ) ) {
        scouts_redirect_with_flash( $form_type, [ 'errors' => [ 'form' => 'Security check failed. Please try again.' ] ], 'error' );
    }

    if ( 'contact' === $form_type ) {
        scouts_handle_contact_submission();
    }

    if ( 'join' === $form_type ) {
        scouts_handle_join_submission();
    }
}
add_action( 'template_redirect', 'scouts_handle_forms' );

function scouts_render_form_message( string $form_type ): string {
    $status = isset( $_GET['form_status'] ) ? sanitize_text_field( wp_unslash( $_GET['form_status'] ) ) : '';
    if ( ! in_array( $status, [ 'success', 'error' ], true ) ) {
        return '';
    }

    if ( ! isset( $_GET['form_token'] ) ) {
        return '';
    }

    $messages = scouts_get_form_flash( $form_type );

    if ( 'success' === $status ) {
        return '<div class="scouts-form-message scouts-form-message--success">Thanks. Your message has been sent and saved in the site admin.</div>';
    }

    $error_text = '';
    if ( ! empty( $messages['errors'] ) ) {
        $error_text = implode( ' ', array_values( $messages['errors'] ) );
    }

    return '<div class="scouts-form-message scouts-form-message--error">' . esc_html( $error_text ?: 'Please check the form and try again.' ) . '</div>';
}

function scouts_get_form_state( string $form_type ): array {
    $status = isset( $_GET['form_status'] ) ? sanitize_text_field( wp_unslash( $_GET['form_status'] ) ) : '';
    if ( 'error' !== $status || ! isset( $_GET['form_token'] ) ) {
        return [ 'errors' => [], 'values' => [] ];
    }

    $messages = scouts_get_form_flash( $form_type );
    return [
        'errors' => $messages['errors'] ?? [],
        'values' => $messages['values'] ?? [],
    ];
}

function scouts_shortcode_contact_form(): string {
    $state = scouts_get_form_state( 'contact' );
    $values = $state['values'];

    ob_start();
    echo scouts_render_form_message( 'contact' );
    ?>
    <div class="form_wrap scouts-form">
        <div class="form-head">
            <h2>Contact us today</h2>
            <p>You can get in touch with us directly using the form below.</p>
        </div>
        <form method="post" action="">
            <input type="hidden" name="scouts_form_type" value="contact" />
            <input type="hidden" name="scouts_form_nonce" value="<?php echo esc_attr( wp_create_nonce( 'scouts_form_contact' ) ); ?>" />
            <input type="hidden" name="redirect_to" value="<?php echo esc_url( get_permalink() ); ?>" />
            <input type="text" name="website" value="" class="scouts-honeypot" tabindex="-1" autocomplete="off" />
            <div class="scouts-form-grid scouts-form-grid--2">
                <div class="scouts-form-field">
                    <label for="contact-first-name">First Name</label>
                    <input id="contact-first-name" type="text" name="first_name" value="<?php echo esc_attr( $values['first_name'] ?? '' ); ?>" />
                </div>
                <div class="scouts-form-field">
                    <label for="contact-surname">Surname</label>
                    <input id="contact-surname" type="text" name="surname" value="<?php echo esc_attr( $values['surname'] ?? '' ); ?>" />
                </div>
            </div>
            <div class="scouts-form-grid scouts-form-grid--2">
                <div class="scouts-form-field">
                    <label for="contact-email">Email Address</label>
                    <input id="contact-email" type="email" name="email" value="<?php echo esc_attr( $values['email'] ?? '' ); ?>" />
                </div>
                <div class="scouts-form-field">
                    <label for="contact-phone">Telephone Number</label>
                    <input id="contact-phone" type="text" name="phone" value="<?php echo esc_attr( $values['phone'] ?? '' ); ?>" />
                </div>
            </div>
            <div class="scouts-form-field">
                <label for="contact-message">Message</label>
                <textarea id="contact-message" name="message" rows="8"><?php echo esc_textarea( $values['message'] ?? '' ); ?></textarea>
            </div>
            <button type="submit" class="btn blue">Send message</button>
        </form>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'scouts_contact_form', 'scouts_shortcode_contact_form' );

function scouts_shortcode_join_form(): string {
    $state = scouts_get_form_state( 'join' );
    $values = $state['values'];
    $join_type = $values['join_type'] ?? 'Young Person';

    ob_start();
    echo scouts_render_form_message( 'join' );
    ?>
    <div class="form_wrap scouts-form">
        <div class="form-head">
            <h2>Join</h2>
            <p>Let us know some details and we’ll connect you with the right section leaders.</p>
        </div>
        <form method="post" action="">
            <input type="hidden" name="scouts_form_type" value="join" />
            <input type="hidden" name="scouts_form_nonce" value="<?php echo esc_attr( wp_create_nonce( 'scouts_form_join' ) ); ?>" />
            <input type="hidden" name="redirect_to" value="<?php echo esc_url( get_permalink() ); ?>" />
            <input type="text" name="website" value="" class="scouts-honeypot" tabindex="-1" autocomplete="off" />

            <div class="scouts-form-field">
                <label>Join type</label>
                <div class="scouts-choice-row">
                    <label><input type="radio" name="join_type" value="Young Person" <?php checked( $join_type, 'Young Person' ); ?> /> Young Person</label>
                    <label><input type="radio" name="join_type" value="Adult Volunteer" <?php checked( $join_type, 'Adult Volunteer' ); ?> /> Adult Volunteer</label>
                </div>
            </div>

            <div class="scouts-form-grid scouts-form-grid--2">
                <div class="scouts-form-field">
                    <label for="join-young-person-name">Young Person's Name</label>
                    <input id="join-young-person-name" type="text" name="young_person_name" value="<?php echo esc_attr( $values['young_person_name'] ?? '' ); ?>" />
                </div>
                <div class="scouts-form-field">
                    <label for="join-adult-name">Your Name</label>
                    <input id="join-adult-name" type="text" name="adult_name" value="<?php echo esc_attr( $values['adult_name'] ?? '' ); ?>" />
                </div>
            </div>

            <div class="scouts-form-grid scouts-form-grid--2">
                <div class="scouts-form-field">
                    <label for="join-parent-name">Parent / Carer Name</label>
                    <input id="join-parent-name" type="text" name="parent_carer_name" value="<?php echo esc_attr( $values['parent_carer_name'] ?? '' ); ?>" />
                </div>
                <div class="scouts-form-field">
                    <label for="join-dob">Date of Birth</label>
                    <input id="join-dob" type="date" name="date_of_birth" value="<?php echo esc_attr( $values['date_of_birth'] ?? '' ); ?>" />
                </div>
            </div>

            <div class="scouts-form-grid scouts-form-grid--2">
                <div class="scouts-form-field">
                    <label for="join-email">Email Address</label>
                    <input id="join-email" type="email" name="email" value="<?php echo esc_attr( $values['email'] ?? '' ); ?>" />
                </div>
                <div class="scouts-form-field">
                    <label for="join-phone">Telephone Number</label>
                    <input id="join-phone" type="text" name="phone" value="<?php echo esc_attr( $values['phone'] ?? '' ); ?>" />
                </div>
            </div>

            <div class="scouts-form-grid scouts-form-grid--2">
                <div class="scouts-form-field">
                    <label for="join-postcode">Postcode</label>
                    <input id="join-postcode" type="text" name="postcode" value="<?php echo esc_attr( $values['postcode'] ?? '' ); ?>" />
                </div>
                <div class="scouts-form-field">
                    <label for="join-section">Preferred Section</label>
                    <select id="join-section" name="preferred_section">
                        <?php
                        $options = [ '' => 'Not sure yet', 'Beavers' => 'Beavers', 'Cubs' => 'Cubs', 'Scouts' => 'Scouts', 'Volunteer' => 'Volunteer' ];
                        foreach ( $options as $option_value => $option_label ) : ?>
                            <option value="<?php echo esc_attr( $option_value ); ?>" <?php selected( $values['preferred_section'] ?? '', $option_value ); ?>><?php echo esc_html( $option_label ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="scouts-form-field">
                <label>Volunteer interests</label>
                <div class="scouts-choice-grid">
                    <?php
                    $selected = $values['volunteer_interest'] ?? [];
                    foreach ( [ 'Sections', 'Support Roles', 'Skills Specialist Roles' ] as $option ) : ?>
                        <label><input type="checkbox" name="volunteer_interest[]" value="<?php echo esc_attr( $option ); ?>" <?php checked( in_array( $option, $selected, true ) ); ?> /> <?php echo esc_html( $option ); ?></label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="scouts-form-field">
                <label for="join-message">Anything else we should know?</label>
                <textarea id="join-message" name="message" rows="6"><?php echo esc_textarea( $values['message'] ?? '' ); ?></textarea>
            </div>

            <button type="submit" class="btn blue">Send join enquiry</button>
        </form>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'scouts_join_form', 'scouts_shortcode_join_form' );
