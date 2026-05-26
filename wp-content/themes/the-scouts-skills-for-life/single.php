<?php get_header(); ?>

<?php while ( have_posts() ) : the_post();
    $hero_img      = get_the_post_thumbnail_url( get_the_ID(), 'large' );
    $news_page_id  = (int) get_option( 'page_for_posts' );
    $recent_posts  = get_posts(
        [
            'post_type'      => 'post',
            'posts_per_page' => 4,
            'post__not_in'   => [ get_the_ID() ],
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]
    );
?>
<main id="main-content" tabindex="-1">
<section class="hero standard cf">
    <?php if ( $hero_img ) : ?>
        <img src="<?php echo esc_url( $hero_img ); ?>" class="bg" alt="" decoding="async" />
    <?php endif; ?>
    <div class="wrapper alt">
        <div class="inner">
            <span class="section-eyebrow"><?php esc_html_e( 'News article', 'the-scouts-skills-for-life' ); ?></span>
            <h2><?php the_title(); ?></h2>
            <p><?php echo esc_html( get_the_date() ); ?></p>
        </div>
    </div>
</section>

<div class="container cf news-single">
    <div class="wrapper cf page_wrapper">
        <div class="main_content playground cf" id="scroll-access">
            <?php if ( $news_page_id ) : ?>
                <a href="<?php echo esc_url( get_permalink( $news_page_id ) ); ?>" class="news-single__back-link">&larr; <?php esc_html_e( 'Back to news', 'the-scouts-skills-for-life' ); ?></a>
            <?php endif; ?>
            <header class="post__header">
                <h1 class="post__title"><?php the_title(); ?></h1>
                <div class="post__details">
                    <span>Date: <?php echo esc_html( get_the_date() ); ?></span>
                    <span>Author: <?php the_author(); ?></span>
                </div>
            </header>
            <article id="post-<?php the_ID(); ?>" <?php post_class( 'playground' ); ?>>
                <?php the_content(); ?>
            </article>
        </div>

        <aside class="sidebar cf">
            <div class="block join">
                <h3>Join today</h3>
                <p>Scouts are do-ers and give-it-a-go-ers, and it's for everyone. We go camping, hiking, swimming, abseiling, cycling and canoeing. We make friends, have fun, play games, and work in teams.</p>
                <a href="<?php echo esc_url( home_url( '/join/' ) ); ?>" class="btn white">Join now</a>
            </div>
            <?php if ( $recent_posts ) : ?>
            <div class="block news-sidebar-list">
                <h4><?php esc_html_e( 'Recent stories', 'the-scouts-skills-for-life' ); ?></h4>
                <ul>
                    <?php foreach ( $recent_posts as $recent_post ) : ?>
                        <li>
                            <a href="<?php echo esc_url( get_permalink( $recent_post ) ); ?>"><?php echo esc_html( get_the_title( $recent_post ) ); ?></a>
                            <span><?php echo esc_html( get_the_date( '', $recent_post ) ); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            <div class="block green nav">
                <h4>More in this section</h4>
                <nav class="page-nav">
                    <ul class="menu side cf">
                        <?php if ( $news_page_id ) : ?>
                            <li class="page_item"><a href="<?php echo esc_url( get_permalink( $news_page_id ) ); ?>">News</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </aside>
    </div>
</div>
<?php endwhile; ?>
</main>

<?php get_footer(); ?>
