<?php
/**
 * 404 — page not found.
 */
get_header();
?>

<main id="main-content" tabindex="-1">

<section class="page_hero">
    <div class="page_hero__overlay" aria-hidden="true"></div>
    <div class="wrapper">
        <div class="page_hero__inner">
            <span class="page_hero__eyebrow" style="display:inline-block;background:rgba(255,255,255,0.16);color:#fff;padding:5px 14px;border-radius:999px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;margin-bottom:16px;"><?php esc_html_e( 'Error 404', 'the-scouts-skills-for-life' ); ?></span>
            <h1 class="page_hero__title"><?php esc_html_e( 'This page took a detour', 'the-scouts-skills-for-life' ); ?></h1>
            <p class="page_hero__intro"><?php esc_html_e( 'The page you\'re looking for can\'t be found. It may have moved, or the link might be old. Have a look around using the suggestions below.', 'the-scouts-skills-for-life' ); ?></p>
        </div>
    </div>
</section>

<div class="page_404">
    <div class="wrapper">
        <div class="page_404__grid">
            <a class="page_404__card" href="<?php echo esc_url( home_url( '/' ) ); ?>">
                <span class="page_404__icon" aria-hidden="true">🏠</span>
                <h2 class="page_404__title"><?php esc_html_e( 'Home', 'the-scouts-skills-for-life' ); ?></h2>
                <p class="page_404__desc"><?php esc_html_e( 'Back to the start.', 'the-scouts-skills-for-life' ); ?></p>
            </a>
            <a class="page_404__card" href="<?php echo esc_url( home_url( '/news/' ) ); ?>">
                <span class="page_404__icon" aria-hidden="true">📰</span>
                <h2 class="page_404__title"><?php esc_html_e( 'News', 'the-scouts-skills-for-life' ); ?></h2>
                <p class="page_404__desc"><?php esc_html_e( 'Latest stories and updates.', 'the-scouts-skills-for-life' ); ?></p>
            </a>
            <a class="page_404__card" href="<?php echo esc_url( home_url( '/shop/' ) ); ?>">
                <span class="page_404__icon" aria-hidden="true">🛍</span>
                <h2 class="page_404__title"><?php esc_html_e( 'Shop', 'the-scouts-skills-for-life' ); ?></h2>
                <p class="page_404__desc"><?php esc_html_e( 'Tickets, merchandise and fundraising.', 'the-scouts-skills-for-life' ); ?></p>
            </a>
            <a class="page_404__card" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">
                <span class="page_404__icon" aria-hidden="true">💬</span>
                <h2 class="page_404__title"><?php esc_html_e( 'Contact', 'the-scouts-skills-for-life' ); ?></h2>
                <p class="page_404__desc"><?php esc_html_e( 'Get in touch with the team.', 'the-scouts-skills-for-life' ); ?></p>
            </a>
        </div>

        <div class="page_404__search">
            <h2><?php esc_html_e( 'Or try a search', 'the-scouts-skills-for-life' ); ?></h2>
            <form role="search" method="get" class="search_form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
                <label for="search-input-404" class="screen-reader-text"><?php esc_html_e( 'Search', 'the-scouts-skills-for-life' ); ?></label>
                <input type="search" id="search-input-404" class="search_form__input" name="s" placeholder="<?php esc_attr_e( 'Search the site…', 'the-scouts-skills-for-life' ); ?>" />
                <button type="submit" class="search_form__submit"><?php esc_html_e( 'Search', 'the-scouts-skills-for-life' ); ?></button>
            </form>
        </div>
    </div>
</div>

</main>

<?php get_footer(); ?>
