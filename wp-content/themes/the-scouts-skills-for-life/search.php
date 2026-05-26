<?php
/**
 * Search results — dedicated template so search results don't fall back to
 * the news archive layout (which would be misleading).
 */
get_header();

$search_query  = get_search_query();
$result_count  = (int) $wp_query->found_posts;
?>

<main id="main-content" tabindex="-1">

<section class="page_hero">
    <div class="page_hero__overlay" aria-hidden="true"></div>
    <div class="wrapper">
        <div class="page_hero__inner">
            <span class="page_hero__eyebrow" style="display:inline-block;background:rgba(255,255,255,0.16);color:#fff;padding:5px 14px;border-radius:999px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;margin-bottom:16px;"><?php esc_html_e( 'Search results', 'the-scouts-skills-for-life' ); ?></span>
            <h1 class="page_hero__title">
                <?php if ( $search_query ) : ?>
                    <?php printf( esc_html__( 'Results for "%s"', 'the-scouts-skills-for-life' ), esc_html( $search_query ) ); ?>
                <?php else : ?>
                    <?php esc_html_e( 'Search the site', 'the-scouts-skills-for-life' ); ?>
                <?php endif; ?>
            </h1>
            <?php if ( $search_query ) : ?>
                <p class="page_hero__intro">
                    <?php
                    /* translators: %s: number of search results */
                    printf( esc_html( _n( '%s result found', '%s results found', $result_count, 'the-scouts-skills-for-life' ) ), esc_html( number_format_i18n( $result_count ) ) );
                    ?>
                </p>
            <?php endif; ?>
        </div>
    </div>
</section>

<div class="search_results">
    <div class="wrapper">
        <form role="search" method="get" class="search_form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
            <label for="search-input" class="screen-reader-text"><?php esc_html_e( 'Search', 'the-scouts-skills-for-life' ); ?></label>
            <input type="search" id="search-input" class="search_form__input" name="s" value="<?php echo esc_attr( $search_query ); ?>" placeholder="<?php esc_attr_e( 'Search news, pages, products…', 'the-scouts-skills-for-life' ); ?>" />
            <button type="submit" class="search_form__submit"><?php esc_html_e( 'Search', 'the-scouts-skills-for-life' ); ?></button>
        </form>

        <?php if ( have_posts() ) : ?>
            <ul class="search_list">
                <?php while ( have_posts() ) : the_post();
                    $post_type_obj = get_post_type_object( get_post_type() );
                    $post_type_label = $post_type_obj ? $post_type_obj->labels->singular_name : __( 'Page', 'the-scouts-skills-for-life' );
                ?>
                    <li class="search_result">
                        <a href="<?php the_permalink(); ?>" class="search_result__link">
                            <div class="search_result__body">
                                <span class="search_result__type"><?php echo esc_html( $post_type_label ); ?></span>
                                <h2 class="search_result__title"><?php the_title(); ?></h2>
                                <div class="search_result__excerpt">
                                    <?php echo esc_html( wp_trim_words( get_the_excerpt(), 30 ) ); ?>
                                </div>
                                <span class="search_result__url"><?php echo esc_html( wp_make_link_relative( get_permalink() ) ); ?></span>
                            </div>
                            <span class="search_result__arrow" aria-hidden="true">→</span>
                        </a>
                    </li>
                <?php endwhile; ?>
            </ul>
            <?php the_posts_pagination( array(
                'class'     => 'news_archive__pagination',
                'prev_text' => __( '&larr; Previous', 'the-scouts-skills-for-life' ),
                'next_text' => __( 'Next &rarr;', 'the-scouts-skills-for-life' ),
            ) ); ?>
        <?php else : ?>
            <div class="search_empty">
                <h2><?php esc_html_e( 'No results found', 'the-scouts-skills-for-life' ); ?></h2>
                <p>
                    <?php if ( $search_query ) : ?>
                        <?php printf( esc_html__( 'We couldn\'t find anything matching "%s". Try a broader term or browse the sections below.', 'the-scouts-skills-for-life' ), esc_html( $search_query ) ); ?>
                    <?php else : ?>
                        <?php esc_html_e( 'Type a search term above to find news, pages or products.', 'the-scouts-skills-for-life' ); ?>
                    <?php endif; ?>
                </p>
                <div class="search_empty__suggestions">
                    <a class="search_empty__chip" href="<?php echo esc_url( home_url( '/news/' ) ); ?>"><?php esc_html_e( 'News', 'the-scouts-skills-for-life' ); ?></a>
                    <a class="search_empty__chip" href="<?php echo esc_url( home_url( '/shop/' ) ); ?>"><?php esc_html_e( 'Shop', 'the-scouts-skills-for-life' ); ?></a>
                    <a class="search_empty__chip" href="<?php echo esc_url( home_url( '/about-us/' ) ); ?>"><?php esc_html_e( 'About', 'the-scouts-skills-for-life' ); ?></a>
                    <a class="search_empty__chip" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><?php esc_html_e( 'Contact', 'the-scouts-skills-for-life' ); ?></a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

</main>

<?php get_footer(); ?>
