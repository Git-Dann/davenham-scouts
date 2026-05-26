<?php
/**
 * Template Name: Builder Canvas
 * Template Post Type: page
 */
get_header();
while ( have_posts() ) : the_post();
?>
<div class="builder-canvas-page">
    <article id="post-<?php the_ID(); ?>" <?php post_class( 'builder-canvas-entry' ); ?>>
        <?php the_content(); ?>
    </article>
</div>
<?php
endwhile;
get_footer();
