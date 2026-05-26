<!DOCTYPE html>
<html class="no-js" <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<?php $scouts_site_settings = function_exists( 'scouts_get_site_settings' ) ? scouts_get_site_settings() : []; ?>

<header class="new-header">
    <div class="secondary_navold sec-menu cf">
        <div class="wrapper">
            <?php wp_nav_menu( [
                'theme_location' => 'secondary',
                'menu_id'        => 'top-navigation',
                'fallback_cb'    => false,
            ] ); ?>
        </div>
    </div>

    <div class="header-main-content">
        <div class="wrapper">
            <div class="logo-cta-container">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="logo">
                    <img src="<?php echo esc_url( function_exists( 'scouts_get_site_logo_url' ) ? scouts_get_site_logo_url() : get_template_directory_uri() . '/images/scouts-logo-standard.svg' ); ?>" alt="<?php bloginfo( 'name' ); ?>" />
                    <span class="logo__text logo__text--england logo__text--medium"><?php bloginfo( 'name' ); ?></span>
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
                        <form class="cf" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get" autocomplete="off">
                            <input type="text" class="text" placeholder="Search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>">
                            <button type="submit" class="submit">
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="6.5" cy="6.5" r="5" stroke="currentColor" stroke-width="2"/><line x1="10.5" y1="10.5" x2="15" y2="15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                            </button>
                        </form>
                    </div>
                </div>

                <div class="icon_wrap-small">
                    <div class="key_links">
                        <a href="#" class="block_icon hamburger">
                            <span></span><span></span><span></span>
                        </a>
                        <div class="block_icon search mobile">
                            <img src="<?php echo esc_url( get_template_directory_uri() . '/images/search_icon.png' ); ?>" alt="Search" />
                        </div>
                    </div>
                    <div class="block_icon search-form mobile">
                        <form class="cf" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get" autocomplete="off">
                            <input type="text" class="text" placeholder="Search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>">
                            <button type="submit" class="submit"></button>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <div class="main-menu main-menu-scouts">
        <div class="bottom wrapper">
            <?php wp_nav_menu( [
                'theme_location' => 'primary',
                'menu_class'     => 'menu main',
                'menu_id'        => 'menu-main-menu',
                'fallback_cb'    => false,
            ] ); ?>
        </div>
    </div>

    <div class="mobile_overlay cf">
        <div class="wrapper">
            <div class="wrap">
                <span class="menu_title">Menu</span>
                <a href="#" class="block_icon hamburger closed"><span></span><span></span><span></span></a>
                <?php wp_nav_menu( [
                    'theme_location' => 'primary',
                    'menu_class'     => 'menu cf',
                    'menu_id'        => 'menu-main-menu-mobile',
                    'fallback_cb'    => false,
                ] ); ?>
            </div>
        </div>
    </div>
</header><!-- header -->
