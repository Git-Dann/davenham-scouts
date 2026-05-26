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

    <section class="age_groups_section">
        <div class="wrapper">
            <h2 class="age_groups_section__heading">Explore our age groups</h2>
            <div class="age_groups_grid">
                <?php
                // Each entry: [ logo, label, age range, slug ]. We try the slug as both
                // a top-level page and a child of /about-us/ so the link works no matter
                // how the site is structured.
                $age_groups = array(
                    array( 'logo-squirrels.svg', 'Squirrels',  '4–6 years',     'squirrels' ),
                    array( 'logo-beavers.svg',   'Beavers',    '6–8 years',     'beavers' ),
                    array( 'logo-cubs.svg',      'Cubs',       '8–10½ years',   'cubs' ),
                    array( 'logo-scouts.svg',    'Scouts',     '10½–14 years',  'scouts' ),
                    array( 'logo-explorers.svg', 'Explorers',  '14–18 years',   'explorers' ),
                    array( 'logo-network.svg',   'Network',    '18–25 years',   'network' ),
                );
                $img_dir = get_template_directory_uri() . '/images/';
                foreach ( $age_groups as $group ) :
                    $slug = $group[3];
                    $page = get_page_by_path( $slug );
                    if ( ! $page ) {
                        $page = get_page_by_path( 'about-us/' . $slug );
                    }
                    $url = $page ? get_permalink( $page ) : false;
                ?>
                <<?php echo $url ? 'a href="' . esc_url( $url ) . '"' : 'div'; ?> class="age_group_card<?php echo $url ? ' age_group_card--linked' : ''; ?>" aria-label="<?php echo esc_attr( $group[1] . ', ' . $group[2] ); ?>">
                    <img src="<?php echo esc_url( $img_dir . $group[0] ); ?>" alt="<?php echo esc_attr( $group[1] ); ?>" />
                    <span class="age_group_card__range"><?php echo esc_html( $group[2] ); ?></span>
                </<?php echo $url ? 'a' : 'div'; ?>>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <?php
    $news_query = new WP_Query( [ 'post_type' => 'post', 'posts_per_page' => 3, 'post_status' => 'publish' ] );
    if ( $news_query->have_posts() ) : ?>
    <section class="news_section">
        <div class="wrapper">
            <div class="news_section__header">
                <h3>What's happening</h3>
                <p class="news_section__subtitle">All the latest news for you and your Scouts</p>
            </div>
            <div class="news_blocks">
                <?php while ( $news_query->have_posts() ) : $news_query->the_post(); ?>
                <a href="<?php the_permalink(); ?>" class="news_block<?php echo has_post_thumbnail() ? '' : ' news_block--no-image'; ?>">
                    <?php if ( has_post_thumbnail() ) : ?>
                        <div class="news_block__image">
                            <?php the_post_thumbnail( 'large' ); ?>
                        </div>
                    <?php endif; ?>
                    <div class="news_block__body">
                        <h4><?php the_title(); ?></h4>
                        <div class="news_block__excerpt"><?php the_excerpt(); ?></div>
                    </div>
                </a>
                <?php endwhile; wp_reset_postdata(); ?>
            </div>
            <?php $news_page = get_option( 'page_for_posts' );
            if ( $news_page ) : ?>
            <div class="news_section__footer">
                <a href="<?php echo esc_url( get_permalink( $news_page ) ); ?>" class="btn news_section__btn">View all</a>
            </div>
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>

</div>

<?php endwhile; ?>

<?php get_footer(); ?>
