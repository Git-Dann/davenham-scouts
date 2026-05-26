<?php get_header(); ?>

<?php while ( have_posts() ) : the_post();
    $raw = get_the_content();

    // Extract first image src for hero background
    preg_match( '/<img[^>]+src=["\']([^"\']+)["\']/', $raw, $m );
    $hero_img = $m[1] ?? '';

    // Strip all <figure> blocks from content for the hero box text
    $content_no_figs = preg_replace( '/<figure[^>]*>.*?<\/figure>/s', '', apply_filters( 'the_content', $raw ) );

    // Split at the first <h3> — everything before goes in hero, rest below
    $split   = preg_split( '/(?=<h3)/', $content_no_figs, 2 );
    $hero_text  = trim( $split[0] ?? '' );
    $body_text  = trim( $split[1] ?? '' );
?>

<?php
$notice_text      = get_theme_mod( 'site_notice_text', '' );
$notice_link      = get_theme_mod( 'site_notice_link', '' );
$notice_link_text = get_theme_mod( 'site_notice_link_text', '' );
if ( $notice_text ) : ?>
<div class="site-notice white">
    <div class="wrapper">
        <span><?php echo esc_html( $notice_text ); ?></span>
        <?php if ( $notice_link ) : ?>
            <a class="notice-btn" href="<?php echo esc_url( $notice_link ); ?>"><?php echo esc_html( $notice_link_text ); ?></a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<section class="hero cf">
    <?php if ( $hero_img ) : ?>
        <img src="<?php echo esc_url( $hero_img ); ?>" class="bg" alt="" decoding="async" fetchpriority="high" />
    <?php endif; ?>
    <div class="wrapper">
        <div class="box cf">
            <div class="wrap">
                <?php echo $hero_text; ?>
                <div class="btn_row">
                    <?php
                    $about = get_page_by_path( 'about-us' );
                    $join  = get_page_by_path( 'join' );
                    if ( $about ) : ?>
                        <a href="<?php echo esc_url( get_permalink( $about ) ); ?>" class="btn outline">Find out more</a>
                    <?php endif;
                    if ( $join ) : ?>
                        <a href="<?php echo esc_url( get_permalink( $join ) ); ?>" class="btn white">Join Today</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="white_container cf">

    <?php if ( $body_text ) : ?>
    <section class="welcome_section">
        <div class="wrapper cf">
            <div class="text">
                <?php echo $body_text; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <section class="age_section">
        <div class="wrapper cf">
            <div class="title_bar">
                <h3>Aged 6 to 25?</h3>
            </div>
            <div class="age_blocks cf">
                <div class="age_blocks six">
                    <?php
                    $sections = [
                        [ 'age-icon-1.svg', 'Squirrels Logo', 'squirrels', '4-6 years' ],
                        [ 'age-icon-2.svg', 'Beavers Logo',   'beavers',   '6-8 years' ],
                        [ 'age-icon-3.svg', 'Cubs Logo',      'cubs',      '8-10½ years' ],
                        [ 'age-icon-4.svg', 'Scouts Logo',    'scouts',    '10½-14 years' ],
                        [ 'age-icon-5.svg', 'Explorers Logo', 'explorers', '14-18 years' ],
                        [ 'age-icon-6.svg', 'Network Logo',   'network',   '18-25 years' ],
                    ];
                    foreach ( $sections as $i => $s ) : $n = $i + 1; ?>
                    <div class="block block-<?php echo $n; ?>">
                        <div class="head <?php echo esc_attr( $s[2] ); ?>">
                            <div class="inner">
                                <img src="<?php echo esc_url( get_template_directory_uri() . '/includes/svg/' . $s[0] ); ?>" alt="<?php echo esc_attr( $s[1] ); ?>" />
                                <span><?php echo esc_html( $s[3] ); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <?php
    $news_query = new WP_Query( [ 'post_type' => 'post', 'posts_per_page' => 3, 'post_status' => 'publish' ] );
    if ( $news_query->have_posts() ) : ?>
    <section class="news_section">
        <div class="wrapper">
            <div class="title_bar">
                <h3>Latest News</h3>
                <?php $news_page = get_option( 'page_for_posts' );
                if ( $news_page ) : ?>
                    <a href="<?php echo esc_url( get_permalink( $news_page ) ); ?>" class="btn outline">All News</a>
                <?php endif; ?>
            </div>
            <div class="news_blocks cf">
                <?php while ( $news_query->have_posts() ) : $news_query->the_post(); ?>
                <div class="news_block">
                    <?php if ( has_post_thumbnail() ) : ?>
                        <a href="<?php the_permalink(); ?>" class="news_block__image" tabindex="-1" aria-hidden="true">
                            <?php the_post_thumbnail( 'medium_large' ); ?>
                        </a>
                    <?php endif; ?>
                    <div class="news_block__body">
                        <time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date( 'jS M Y' ) ); ?></time>
                        <h4><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>
                        <div class="news_block__excerpt"><?php the_excerpt(); ?></div>
                        <a href="<?php the_permalink(); ?>" class="btn outline">Read more</a>
                    </div>
                </div>
                <?php endwhile; wp_reset_postdata(); ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

</div>

<?php endwhile; ?>

<?php get_footer(); ?>
