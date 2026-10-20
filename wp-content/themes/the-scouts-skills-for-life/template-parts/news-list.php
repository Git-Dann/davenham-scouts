<?php
/**
 * News listing — magazine-style grid of post cards + pagination.
 * Shared by index.php and archive.php (previously duplicated verbatim in both).
 * Expects the main query loop (have_posts()/the_post()).
 *
 * @package the-scouts-skills-for-life
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="news_archive">
    <div class="wrapper">
        <?php if ( have_posts() ) : ?>
            <div class="news_archive__grid">
                <?php
                $loop_index = 0;
                while ( have_posts() ) : the_post();
                    $loop_index++;
                    $is_lead = ( 1 === $loop_index );
                    $cats    = get_the_category();
                ?>
                    <article id="post-<?php the_ID(); ?>" <?php post_class( $is_lead ? 'news_card news_card--lead' : 'news_card' ); ?>>
                        <a href="<?php the_permalink(); ?>" class="news_card__image" aria-hidden="true" tabindex="-1">
                            <?php if ( has_post_thumbnail() ) :
                                the_post_thumbnail( $is_lead ? 'full' : 'large', array( 'alt' => '', 'loading' => 'lazy' ) );
                            else : ?>
                                <span class="news_card__placeholder" aria-hidden="true">⚜</span>
                            <?php endif; ?>
                        </a>
                        <div class="news_card__body">
                            <div class="news_card__meta">
                                <?php if ( ! empty( $cats ) ) : ?>
                                    <span class="news_card__tag"><?php echo esc_html( $cats[0]->name ); ?></span>
                                <?php endif; ?>
                                <time class="news_card__time" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
                            </div>
                            <h2 class="news_card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                            <div class="news_card__excerpt"><?php the_excerpt(); ?></div>
                            <a href="<?php the_permalink(); ?>" class="news_card__cta">Read story →</a>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>
            <?php the_posts_pagination( array(
                'class'              => 'news_archive__pagination',
                'prev_text'          => __( '&larr; Previous', 'the-scouts-skills-for-life' ),
                'next_text'          => __( 'Next &rarr;', 'the-scouts-skills-for-life' ),
                'before_page_number' => '<span class="screen-reader-text">' . __( 'Page', 'the-scouts-skills-for-life' ) . ' </span>',
            ) ); ?>
        <?php else : ?>
            <div class="news_archive__empty">
                <p><?php esc_html_e( 'No news posts yet — check back soon.', 'the-scouts-skills-for-life' ); ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>
