<?php $scouts_site_settings = function_exists( 'scouts_get_site_settings' ) ? scouts_get_site_settings() : []; ?>
<?php if ( function_exists( 'scouts_render_newsletter_strip' ) ) { scouts_render_newsletter_strip(); } ?>
<footer class="footer hi cf">
    <div class="top cf">
        <div class="wrapper footer-grid">
            <div class="footer-brand">
                <div class="footer-logo-wrap">
                    <a href="<?php echo esc_url( home_url( '/' ) ); ?>">
                        <img src="<?php echo esc_url( function_exists( 'scouts_get_site_logo_url' ) ? scouts_get_site_logo_url() : get_template_directory_uri() . '/images/scouts-logo-standard.svg' ); ?>" alt="<?php bloginfo( 'name' ); ?>" width="100" />
                    </a>
                </div>
                <h6><?php bloginfo( 'name' ); ?></h6>
                <p><?php echo esc_html( $scouts_site_settings['footer_title'] ?? 'Skills for Life for young people across Davenham through Beavers, Cubs and Scouts.' ); ?></p>
                <p><?php echo esc_html( $scouts_site_settings['footer_intro'] ?? 'We are a volunteer-led Scout Group, rooted in the local community and based at Peckmill Scout Wood.' ); ?></p>
                <div class="footer-actions">
                    <a href="<?php echo esc_url( home_url( '/join/' ) ); ?>">Join Scouts</a>
                    <a href="<?php echo esc_url( home_url( '/volunteer/' ) ); ?>">Volunteer</a>
                </div>
            </div>

            <div class="col">
                <h6>Site Links</h6>
                <?php wp_nav_menu( [
                    'theme_location' => 'footer',
                    'menu_class'     => 'menu footer_menu cf',
                    'menu_id'        => 'menu-useful-links',
                    'fallback_cb'    => false,
                ] ); ?>
            </div>

            <div class="col blue">
                <h6>Sections</h6>
                <ul class="menu footer_menu cf">
                    <li><a href="<?php echo esc_url( home_url( '/about-us/beavers/' ) ); ?>">Beavers</a></li>
                    <li><a href="<?php echo esc_url( home_url( '/about-us/cubs/' ) ); ?>">Cubs</a></li>
                    <li><a href="<?php echo esc_url( home_url( '/about-us/scouts/' ) ); ?>">Scouts</a></li>
                    <li><a href="<?php echo esc_url( home_url( '/about-us/general-information/' ) ); ?>">General Information</a></li>
                    <li><a href="<?php echo esc_url( home_url( '/about-us/fundraising/' ) ); ?>">Fundraising</a></li>
                </ul>
            </div>

            <div class="col green">
                <h6>Contact us</h6>
                <div class="wrap cf">
                    <p><?php echo nl2br( esc_html( $scouts_site_settings['footer_address'] ?? "Davenham\nCheshire\nCW9 8LH" ) ); ?></p>
                </div>
                <p><strong>Contact us:</strong> <a href="<?php echo esc_url( $scouts_site_settings['footer_contact_url'] ?? home_url( '/contact/' ) ); ?>"><?php echo esc_html( $scouts_site_settings['footer_contact_text'] ?? 'Click here' ); ?></a></p>
                <ul class="menu footer_menu cf footer-social">
                    <?php if ( ! empty( $scouts_site_settings['footer_social_facebook'] ) ) : ?>
                        <li><a href="<?php echo esc_url( $scouts_site_settings['footer_social_facebook'] ); ?>" target="_blank" rel="noopener">Facebook</a></li>
                    <?php endif; ?>
                    <?php if ( ! empty( $scouts_site_settings['footer_social_x'] ) ) : ?>
                        <li><a href="<?php echo esc_url( $scouts_site_settings['footer_social_x'] ); ?>" target="_blank" rel="noopener">X / Twitter</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

    <div class="bottom cf">
        <div class="wrapper">
            <div class="wrap cf">
                <p>&copy; Copyright <?php echo date( 'Y' ); ?> <?php bloginfo( 'name' ); ?>. All rights reserved.</p>
                <p>Registered charity number: <?php echo esc_html( $scouts_site_settings['charity_number'] ?? '1029781' ); ?></p>
            </div>
        </div>
    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
