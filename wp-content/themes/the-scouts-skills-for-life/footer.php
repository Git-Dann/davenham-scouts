<?php
$scouts_site_settings = function_exists( 'scouts_get_site_settings' ) ? scouts_get_site_settings() : [];
$dvh_logo_url = function_exists( 'scouts_get_site_logo_url' ) ? scouts_get_site_logo_url() : get_template_directory_uri() . '/images/scouts-logo-standard.svg';
$dvh_tagline  = $scouts_site_settings['footer_title'] ?? 'Skills for life for young people across Davenham — through Beavers, Cubs and Scouts.';
$dvh_contact  = $scouts_site_settings['footer_contact_url'] ?? home_url( '/contact/' );
$dvh_fb       = $scouts_site_settings['footer_social_facebook'] ?? '';
$dvh_x        = $scouts_site_settings['footer_social_x'] ?? '';
?>
<?php if ( function_exists( 'scouts_render_newsletter_strip' ) ) { scouts_render_newsletter_strip(); } ?>

<footer class="site-footer" role="contentinfo">
    <div class="site-footer__inner">

        <div class="site-footer__brand">
            <a class="site-footer__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php bloginfo( 'name' ); ?>">
                <img src="<?php echo esc_url( $dvh_logo_url ); ?>" alt="<?php bloginfo( 'name' ); ?>" />
            </a>
            <p class="site-footer__tagline"><?php echo esc_html( $dvh_tagline ); ?></p>
            <div class="site-footer__cta-row">
                <a class="site-footer__cta" href="<?php echo esc_url( home_url( '/join/' ) ); ?>">Join Scouts</a>
            </div>
        </div>

        <nav class="site-footer__col" aria-label="<?php esc_attr_e( 'Site links', 'the-scouts-skills-for-life' ); ?>">
            <h2 class="site-footer__heading">Explore</h2>
            <?php wp_nav_menu( [
                'theme_location' => 'footer',
                'menu_class'     => 'site-footer__menu',
                'menu_id'        => 'menu-useful-links',
                'container'      => false,
                'depth'          => 1,
                'fallback_cb'    => false,
            ] ); ?>
        </nav>

        <nav class="site-footer__col" aria-label="<?php esc_attr_e( 'Our sections', 'the-scouts-skills-for-life' ); ?>">
            <h2 class="site-footer__heading">Our Sections</h2>
            <ul class="site-footer__menu">
                <li><a href="<?php echo esc_url( home_url( '/about-us/beavers/' ) ); ?>">Beavers</a></li>
                <li><a href="<?php echo esc_url( home_url( '/about-us/cubs/' ) ); ?>">Cubs</a></li>
                <li><a href="<?php echo esc_url( home_url( '/about-us/scouts/' ) ); ?>">Scouts</a></li>
                <li><a href="<?php echo esc_url( home_url( '/about-us/general-information/' ) ); ?>">General Information</a></li>
                <li><a href="<?php echo esc_url( home_url( '/about-us/fundraising/' ) ); ?>">Fundraising</a></li>
            </ul>
        </nav>

        <div class="site-footer__col">
            <h2 class="site-footer__heading">Get in Touch</h2>
            <address class="site-footer__address"><?php echo nl2br( esc_html( $scouts_site_settings['footer_address'] ?? "Peckmill Scout Wood\nDavenham, Cheshire\nCW9 8LH" ) ); ?></address>
            <a class="site-footer__contact-link" href="<?php echo esc_url( $dvh_contact ); ?>">
                <?php echo esc_html( $scouts_site_settings['footer_contact_text'] ?? 'Send us a message' ); ?>
                <span aria-hidden="true">&rarr;</span>
            </a>
            <?php if ( $dvh_fb || $dvh_x ) : ?>
            <ul class="site-footer__social">
                <?php if ( $dvh_fb ) : ?>
                    <li><a href="<?php echo esc_url( $dvh_fb ); ?>" target="_blank" rel="noopener" aria-label="Facebook">Facebook</a></li>
                <?php endif; ?>
                <?php if ( $dvh_x ) : ?>
                    <li><a href="<?php echo esc_url( $dvh_x ); ?>" target="_blank" rel="noopener" aria-label="X / Twitter">X</a></li>
                <?php endif; ?>
            </ul>
            <?php endif; ?>
        </div>

    </div>

    <div class="site-footer__bar">
        <div class="site-footer__bar-inner">
            <p>&copy; <?php echo esc_html( date( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>. All rights reserved.</p>
            <p>Registered charity no. <?php echo esc_html( $scouts_site_settings['charity_number'] ?? '1029781' ); ?></p>
        </div>
    </div>
</footer><!-- /site-footer -->

<?php wp_footer(); ?>
</body>
</html>
