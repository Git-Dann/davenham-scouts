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
<?php
// Single primary CTA (Join). Volunteer removed — it duplicated Join, which is
// in the nav. Uses the saved CTA setting, with a sensible fallback.
$dvh_join_url  = ! empty( $scouts_site_settings['header_secondary_cta_url'] ) ? $scouts_site_settings['header_secondary_cta_url'] : home_url( '/join/' );
$dvh_join_text = ! empty( $scouts_site_settings['header_secondary_cta_text'] ) ? $scouts_site_settings['header_secondary_cta_text'] : __( 'Join Us', 'the-scouts-skills-for-life' );
$dvh_logo_url  = function_exists( 'scouts_get_site_logo_url' ) ? scouts_get_site_logo_url() : get_template_directory_uri() . '/images/scouts-logo-standard.svg';
?>

<header class="site-header" role="banner">
    <div class="site-header__inner">
        <a class="site-header__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php bloginfo( 'name' ); ?>">
            <img src="<?php echo esc_url( $dvh_logo_url ); ?>" alt="<?php bloginfo( 'name' ); ?>" />
        </a>

        <nav class="site-header__nav" aria-label="<?php esc_attr_e( 'Primary', 'the-scouts-skills-for-life' ); ?>">
            <?php wp_nav_menu( [
                'theme_location' => 'primary',
                'menu_class'     => 'site-header__menu',
                'menu_id'        => 'primary-menu',
                'fallback_cb'    => false,
                'container'      => false,
                'depth'          => 2,
            ] ); ?>
        </nav>

        <div class="site-header__actions">
            <a class="site-header__cta" href="<?php echo esc_url( $dvh_join_url ); ?>"><?php echo esc_html( $dvh_join_text ); ?></a>
            <button type="button" class="site-header__burger" aria-label="<?php esc_attr_e( 'Open menu', 'the-scouts-skills-for-life' ); ?>" aria-expanded="false" aria-controls="site-mobile-nav">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>

    <div class="site-header__mobile" id="site-mobile-nav" hidden>
        <?php wp_nav_menu( [
            'theme_location' => 'primary',
            'menu_class'     => 'site-header__mobile-menu',
            'menu_id'        => 'primary-menu-mobile',
            'fallback_cb'    => false,
            'container'      => false,
            'depth'          => 2,
        ] ); ?>
        <a class="site-header__cta site-header__cta--block" href="<?php echo esc_url( $dvh_join_url ); ?>"><?php echo esc_html( $dvh_join_text ); ?></a>
    </div>
</header><!-- /site-header -->
