<?php get_header(); ?>

<main id="main-content" tabindex="-1">
<section class="hero standard cf">
    <div class="wrapper alt">
        <div class="inner">
            <h2><?php echo esc_html( wp_strip_all_tags( get_the_archive_title() ) ); ?></h2>
            <p><?php echo esc_html( wp_strip_all_tags( get_the_archive_description() ?: 'Updates, stories and highlights from 1st Davenham Scouts.' ) ); ?></p>
        </div>
    </div>
</section>

<div class="white_container cf news-archive">
    <div class="wrapper">
        <?php if ( have_posts() ) : ?>
            <div class="news-archive-grid">
                <?php while ( have_posts() ) : the_post(); ?>
                    <article id="post-<?php the_ID(); ?>" <?php post_class( 'news-archive-card' ); ?>>
                        <?php if ( has_post_thumbnail() ) : ?>
                            <a href="<?php the_permalink(); ?>" class="news-archive-card__image"><?php the_post_thumbnail( 'large' ); ?></a>
                        <?php endif; ?>
                        <div class="news-archive-card__body">
                            <time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
                            <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                            <div class="entry-summary"><?php the_excerpt(); ?></div>
                            <a href="<?php the_permalink(); ?>" class="btn green">Read more</a>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>
            <?php the_posts_pagination(); ?>
        <?php else : ?>
            <p><?php esc_html_e( 'No posts found.', 'the-scouts-skills-for-life' ); ?></p>
        <?php endif; ?>
    </div>
</div>
</main>

<?php get_footer(); ?>
