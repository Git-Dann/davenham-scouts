<?php get_header(); ?>

<?php while ( have_posts() ) : the_post();
    $raw_content      = get_the_content();
    $hero_img         = get_the_post_thumbnail_url( get_the_ID(), 'full' );
    $current_id       = get_the_ID();
    $ancestors        = get_post_ancestors( $current_id );

    // Detect whether the page content uses Davenham Builder blocks.
    // When it does we render the page content full-width since each
    // block manages its own padding/section width. Otherwise we wrap
    // the content in a centred article column for easy reading.
    $has_builder_blocks = false !== strpos( $raw_content, '<!-- wp:davenham/' );

    // Derive a hero intro: explicit excerpt → first <h6>/<p> → empty.
    $hero_intro = '';
    if ( has_excerpt() ) {
        $hero_intro = get_the_excerpt();
    } elseif ( ! $has_builder_blocks && preg_match( '/<(h6|p)[^>]*>(.*?)<\/\1>/is', apply_filters( 'the_content', $raw_content ), $m ) ) {
        // Only auto-extract from classic content — on builder-block pages the
        // first <p> is a block's internal text (e.g. a leader's role), which
        // makes a misleading hero subtitle.
        $hero_intro = wp_trim_words( wp_strip_all_tags( html_entity_decode( $m[2] ) ), 26 );
    }

    // Strip a leading image from classic content if it would duplicate the hero image.
    $content_to_render = apply_filters( 'the_content', $raw_content );
    if ( ! has_post_thumbnail() ) {
        $content_to_render = preg_replace(
            '/<p>\s*(?:<a[^>]*>)?\s*<img[^>]+>\s*(?:<\/a>)?\s*<\/p>/i',
            '',
            $content_to_render,
            1
        );
    }
?>
<main id="main-content" tabindex="-1">

<section class="page_hero<?php echo $hero_img ? ' page_hero--with-image' : ''; ?>">
    <?php if ( $hero_img ) : ?>
        <img src="<?php echo esc_url( $hero_img ); ?>" class="page_hero__bg" alt="" decoding="async" />
    <?php endif; ?>
    <div class="page_hero__overlay" aria-hidden="true"></div>
    <div class="wrapper">
        <div class="page_hero__inner">
            <?php if ( ! empty( $ancestors ) ) : ?>
            <nav class="page_hero__crumbs" aria-label="Breadcrumb">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'the-scouts-skills-for-life' ); ?></a>
                <?php foreach ( array_reverse( $ancestors ) as $ancestor_id ) : ?>
                <span aria-hidden="true">/</span>
                <a href="<?php echo esc_url( get_permalink( $ancestor_id ) ); ?>"><?php echo esc_html( get_the_title( $ancestor_id ) ); ?></a>
                <?php endforeach; ?>
                <span aria-hidden="true">/</span>
                <span><?php the_title(); ?></span>
            </nav>
            <?php endif; ?>
            <h1 class="page_hero__title"><?php the_title(); ?></h1>
            <?php if ( $hero_intro ) : ?>
                <p class="page_hero__intro"><?php echo esc_html( $hero_intro ); ?></p>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php // Secondary/section navigation removed — the main header nav (with
      // dropdowns) is the single source of site navigation. ?>

<?php if ( $has_builder_blocks ) : ?>
<div class="page_content page_content--blocks">
    <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
        <?php echo $content_to_render; ?>
    </article>
</div>
<?php else : ?>
<div class="page_content page_content--article">
    <div class="wrapper">
        <article id="post-<?php the_ID(); ?>" <?php post_class( 'page_article' ); ?>>
            <div class="page_article__body">
                <?php echo $content_to_render; ?>
            </div>
        </article>
    </div>
</div>
<?php endif; ?>

</main>
<?php endwhile; ?>

<?php get_footer(); ?>
