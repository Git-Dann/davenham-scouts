<?php get_header(); ?>

<?php while ( have_posts() ) : the_post();
    $hero_img      = get_the_post_thumbnail_url( get_the_ID(), 'full' );
    $news_page_id  = (int) get_option( 'page_for_posts' );
    $news_url      = $news_page_id ? get_permalink( $news_page_id ) : home_url( '/news/' );
    $recent_posts  = get_posts(
        array(
            'post_type'      => 'post',
            'posts_per_page' => 3,
            'post__not_in'   => array( get_the_ID() ),
            'orderby'        => 'date',
            'order'          => 'DESC',
        )
    );
    $categories = get_the_category();
?>
<main id="main-content" tabindex="-1">

<section class="post_hero">
    <?php if ( $hero_img ) : ?>
        <img src="<?php echo esc_url( $hero_img ); ?>" class="post_hero__bg" alt="" decoding="async" />
    <?php endif; ?>
    <div class="post_hero__overlay" aria-hidden="true"></div>
    <div class="wrapper">
        <div class="post_hero__inner">
            <nav class="post_hero__crumbs" aria-label="Breadcrumb">
                <a href="<?php echo esc_url( $news_url ); ?>"><?php esc_html_e( 'News', 'the-scouts-skills-for-life' ); ?></a>
                <span aria-hidden="true">/</span>
                <span><?php the_title(); ?></span>
            </nav>
            <?php if ( ! empty( $categories ) ) : ?>
                <span class="post_hero__eyebrow"><?php echo esc_html( $categories[0]->name ); ?></span>
            <?php else : ?>
                <span class="post_hero__eyebrow"><?php esc_html_e( 'Latest', 'the-scouts-skills-for-life' ); ?></span>
            <?php endif; ?>
            <h1 class="post_hero__title"><?php the_title(); ?></h1>
            <div class="post_hero__meta">
                <time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
                <?php $author = get_the_author(); if ( $author ) : ?>
                    <span class="post_hero__meta-sep" aria-hidden="true">·</span>
                    <span><?php
                        /* translators: %s: post author name */
                        printf( esc_html__( 'By %s', 'the-scouts-skills-for-life' ), esc_html( $author ) );
                    ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'post_article' ); ?>>
    <div class="wrapper">
        <div class="post_article__body">
            <?php the_content(); ?>

            <?php if ( has_tag() ) : ?>
            <div class="post_article__tags">
                <span class="post_article__tags-label"><?php esc_html_e( 'Tags', 'the-scouts-skills-for-life' ); ?></span>
                <?php the_tags( '<span class="post_article__tag-list">', '</span><span class="post_article__tag-list">', '</span>' ); ?>
            </div>
            <?php endif; ?>

            <div class="post_article__footer">
                <a href="<?php echo esc_url( $news_url ); ?>" class="post_article__back">← <?php esc_html_e( 'Back to all news', 'the-scouts-skills-for-life' ); ?></a>
            </div>
        </div>
    </div>
</article>

<?php if ( $recent_posts ) : ?>
<section class="post_related">
    <div class="wrapper">
        <div class="post_related__header">
            <h2 class="post_related__heading"><?php esc_html_e( 'More stories from the group', 'the-scouts-skills-for-life' ); ?></h2>
            <a class="post_related__view-all" href="<?php echo esc_url( $news_url ); ?>"><?php esc_html_e( 'View all news', 'the-scouts-skills-for-life' ); ?> →</a>
        </div>
        <div class="post_related__grid">
            <?php foreach ( $recent_posts as $recent_post ) :
                $rp_thumb = get_the_post_thumbnail_url( $recent_post, 'medium_large' );
                $rp_cats  = get_the_category( $recent_post->ID );
            ?>
            <a href="<?php echo esc_url( get_permalink( $recent_post ) ); ?>" class="post_related__card<?php echo $rp_thumb ? '' : ' post_related__card--no-image'; ?>">
                <?php if ( $rp_thumb ) : ?>
                <div class="post_related__card-image">
                    <img src="<?php echo esc_url( $rp_thumb ); ?>" alt="" loading="lazy" />
                </div>
                <?php endif; ?>
                <div class="post_related__card-body">
                    <?php if ( ! empty( $rp_cats ) ) : ?>
                        <span class="post_related__card-tag"><?php echo esc_html( $rp_cats[0]->name ); ?></span>
                    <?php endif; ?>
                    <h3 class="post_related__card-title"><?php echo esc_html( get_the_title( $recent_post ) ); ?></h3>
                    <time class="post_related__card-time" datetime="<?php echo esc_attr( get_the_date( 'c', $recent_post ) ); ?>"><?php echo esc_html( get_the_date( '', $recent_post ) ); ?></time>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php endwhile; ?>
</main>

<?php get_footer(); ?>
