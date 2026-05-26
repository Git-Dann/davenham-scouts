<?php get_header(); ?>

<?php while ( have_posts() ) : the_post();
    $raw_content       = get_the_content();
    $filtered_content  = apply_filters( 'the_content', $raw_content );
    $hero_img          = get_the_post_thumbnail_url( get_the_ID(), 'large' ) ?: '';
    $content_for_page  = $filtered_content;
    $hero_intro        = '';
    $current_id        = get_the_ID();
    $ancestors         = get_post_ancestors( $current_id );
    $section_root_id   = $ancestors ? end( $ancestors ) : $current_id;
    $section_root_page = get_post( $section_root_id );
    $section_children  = get_pages( [
        'parent'      => $section_root_id,
        'sort_column' => 'menu_order,post_title',
    ] );
    $show_section_nav  = ! empty( $section_children ) || ! empty( $ancestors );

    if ( ! $hero_img && preg_match( '/<img[^>]+src=["\']([^"\']+)["\']/', $filtered_content, $image_match ) ) {
        $hero_img = $image_match[1];
    }

    if ( ! has_post_thumbnail() ) {
        $content_for_page = preg_replace(
            '/<p>\s*(?:<a[^>]*>)?\s*<img[^>]+>\s*(?:<\/a>)?\s*<\/p>/i',
            '',
            $content_for_page,
            1
        );
    }

    if ( has_excerpt() ) {
        $hero_intro = get_the_excerpt();
    } elseif ( preg_match( '/<(h6|p)[^>]*>(.*?)<\/\1>/is', $content_for_page, $intro_match ) ) {
        $hero_intro = wp_trim_words( wp_strip_all_tags( html_entity_decode( $intro_match[2] ) ), 26 );
    }
?>

<section class="hero standard cf">
    <?php if ( $hero_img ) : ?>
        <img src="<?php echo esc_url( $hero_img ); ?>" class="bg" alt="" decoding="async" />
    <?php endif; ?>
    <div class="wrapper alt">
        <div class="inner">
            <h2><?php the_title(); ?></h2>
            <?php if ( $hero_intro ) : ?>
                <p><?php echo esc_html( $hero_intro ); ?></p>
            <?php endif; ?>
        </div>
    </div>
</section>

<div class="container cf">
    <div class="wrapper cf page_wrapper">
        <div class="main_content playground cf" id="scroll-access">
            <h1><?php the_title(); ?></h1>
            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <?php echo $content_for_page; ?>
            </article>
        </div>

        <aside class="sidebar cf">
            <div class="block join">
                <h3>Join today</h3>
                <p>Scouts are do-ers and give-it-a-go-ers, and it's for everyone. We go camping, hiking, swimming, abseiling, cycling and canoeing. We make friends, have fun, play games, and work in teams.</p>
                <a href="<?php echo esc_url( home_url( '/join/' ) ); ?>" class="btn white">Join now</a>
            </div>

            <?php if ( $show_section_nav && $section_root_page ) : ?>
            <div class="block green nav">
                <h4>More in this section</h4>
                <nav class="page-nav">
                    <ul class="menu side cf">
                        <li class="page_item <?php echo $section_root_id === $current_id ? 'current_page_item' : ''; ?>">
                            <a href="<?php echo esc_url( get_permalink( $section_root_id ) ); ?>" <?php echo $section_root_id === $current_id ? 'aria-current="page"' : ''; ?>>
                                <?php echo esc_html( get_the_title( $section_root_id ) ); ?>
                            </a>
                        </li>
                        <?php foreach ( $section_children as $child ) : ?>
                        <li class="page_item <?php echo (int) $child->ID === $current_id ? 'current_page_item' : ''; ?>">
                            <a href="<?php echo esc_url( get_permalink( $child->ID ) ); ?>" <?php echo (int) $child->ID === $current_id ? 'aria-current="page"' : ''; ?>>
                                <?php echo esc_html( $child->post_title ); ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </aside>
    </div>
</div>

<?php endwhile; ?>

<?php get_footer(); ?>
