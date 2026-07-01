<!DOCTYPE html>
<html class="no-js" <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="scouts-skip-link" href="#main-content"><?php esc_html_e( 'Skip to main content', 'the-scouts-skills-for-life' ); ?></a>
<?php $scouts_site_settings = function_exists( 'scouts_get_site_settings' ) ? scouts_get_site_settings() : []; ?>

<header class="new-header" role="banner">
    <nav class="secondary_navold sec-menu cf" aria-label="<?php esc_attr_e( 'Secondary', 'the-scouts-skills-for-life' ); ?>">
        <div class="wrapper">
            <?php wp_nav_menu( [
                'theme_location' => 'secondary',
                'menu_id'        => 'top-navigation',
                'fallback_cb'    => false,
                'container'      => false,
            ] ); ?>
        </div>
    </nav>

    <div class="header-main-content">
        <div class="wrapper">
            <div class="logo-cta-container">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="logo">
                    <img src="<?php echo esc_url( function_exists( 'scouts_get_site_logo_url' ) ? scouts_get_site_logo_url() : get_template_directory_uri() . '/images/scouts-logo-standard.svg' ); ?>" alt="<?php bloginfo( 'name' ); ?>" />
                </a>

                <div class="icon_wrap">
                    <div class="block-icon cta-btn">
                        <?php if ( ! empty( $scouts_site_settings['header_primary_cta_text'] ) && ! empty( $scouts_site_settings['header_primary_cta_url'] ) ) : ?>
                            <a href="<?php echo esc_url( $scouts_site_settings['header_primary_cta_url'] ); ?>"><?php echo esc_html( $scouts_site_settings['header_primary_cta_text'] ); ?></a>
                        <?php endif; ?>
                        <?php if ( ! empty( $scouts_site_settings['header_secondary_cta_text'] ) && ! empty( $scouts_site_settings['header_secondary_cta_url'] ) ) : ?>
                            <a href="<?php echo esc_url( $scouts_site_settings['header_secondary_cta_url'] ); ?>"><?php echo esc_html( $scouts_site_settings['header_secondary_cta_text'] ); ?></a>
                        <?php endif; ?>
                    </div>

                    <div class="block_icon search-form desktop">
                        <form class="cf" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get" role="search" autocomplete="off">
                            <label class="screen-reader-text" for="header-search-desktop"><?php esc_html_e( 'Search', 'the-scouts-skills-for-life' ); ?></label>
                            <input type="search" id="header-search-desktop" class="text" placeholder="<?php esc_attr_e( 'Search', 'the-scouts-skills-for-life' ); ?>" name="s" value="<?php echo esc_attr( get_search_query() ); ?>">
                            <button type="submit" class="submit" aria-label="<?php esc_attr_e( 'Submit search', 'the-scouts-skills-for-life' ); ?>">
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"><circle cx="6.5" cy="6.5" r="5" stroke="currentColor" stroke-width="2"/><line x1="10.5" y1="10.5" x2="15" y2="15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                            </button>
                        </form>
                    </div>
                </div>

                <div class="icon_wrap-small">
                    <div class="key_links">
                        <button type="button" class="block_icon hamburger" aria-label="<?php esc_attr_e( 'Open menu', 'the-scouts-skills-for-life' ); ?>" aria-expanded="false" aria-controls="mobile-menu">
                            <span></span><span></span><span></span>
                        </button>
                        <button type="button" class="block_icon search mobile" aria-label="<?php esc_attr_e( 'Toggle search', 'the-scouts-skills-for-life' ); ?>">
                            <img src="<?php echo esc_url( get_template_directory_uri() . '/images/search_icon.png' ); ?>" alt="" aria-hidden="true" />
                        </button>
                    </div>
                    <div class="block_icon search-form mobile">
                        <form class="cf" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get" role="search" autocomplete="off">
                            <label class="screen-reader-text" for="header-search-mobile"><?php esc_html_e( 'Search', 'the-scouts-skills-for-life' ); ?></label>
                            <input type="search" id="header-search-mobile" class="text" placeholder="<?php esc_attr_e( 'Search', 'the-scouts-skills-for-life' ); ?>" name="s" value="<?php echo esc_attr( get_search_query() ); ?>">
                            <button type="submit" class="submit" aria-label="<?php esc_attr_e( 'Submit search', 'the-scouts-skills-for-life' ); ?>"></button>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <nav class="main-menu main-menu-scouts" aria-label="<?php esc_attr_e( 'Primary', 'the-scouts-skills-for-life' ); ?>">
        <div class="bottom wrapper">
            <?php wp_nav_menu( [
                'theme_location' => 'primary',
                'menu_class'     => 'menu main',
                'menu_id'        => 'menu-main-menu',
                'fallback_cb'    => false,
                'container'      => false,
            ] ); ?>
        </div>
    </nav>

    <div class="mobile_overlay cf" id="mobile-menu" aria-label="<?php esc_attr_e( 'Mobile menu', 'the-scouts-skills-for-life' ); ?>" aria-hidden="true">
        <div class="wrapper">
            <div class="wrap">
                <span class="menu_title"><?php esc_html_e( 'Menu', 'the-scouts-skills-for-life' ); ?></span>
                <button type="button" class="block_icon hamburger closed" aria-label="<?php esc_attr_e( 'Close menu', 'the-scouts-skills-for-life' ); ?>">
                    <span></span><span></span><span></span>
                </button>
                <?php wp_nav_menu( [
                    'theme_location' => 'primary',
                    'menu_class'     => 'menu cf',
                    'menu_id'        => 'menu-main-menu-mobile',
                    'fallback_cb'    => false,
                    'container'      => false,
                ] ); ?>
            </div>
        </div>
    </div>
</header><!-- header -->
