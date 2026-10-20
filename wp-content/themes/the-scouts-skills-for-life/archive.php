<?php get_header(); ?>

<main id="main-content" tabindex="-1">

<section class="news_archive_hero">
    <div class="news_archive_hero__overlay" aria-hidden="true"></div>
    <div class="wrapper">
        <span class="news_archive_hero__eyebrow"><?php esc_html_e( 'News & stories', 'the-scouts-skills-for-life' ); ?></span>
        <h1 class="news_archive_hero__title"><?php echo esc_html( wp_strip_all_tags( get_the_archive_title() ) ); ?></h1>
        <p class="news_archive_hero__desc"><?php echo esc_html( wp_strip_all_tags( get_the_archive_description() ?: 'Updates, stories and highlights from 1st Davenham Scouts — section adventures, fundraising wins and community events.' ) ); ?></p>
    </div>
</section>

<?php get_template_part( 'template-parts/news-list' ); ?>

</main>

<?php get_footer(); ?>
