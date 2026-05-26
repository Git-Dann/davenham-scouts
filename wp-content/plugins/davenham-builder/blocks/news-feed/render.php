<?php
/**
 * Render: davenham/news-feed
 * Dynamic block — queries WP posts on every page load.
 *
 * @var array $attributes Block attributes.
 */
$heading          = $attributes['heading']       ?? 'Latest news';
$view_all_text    = $attributes['viewAllText']   ?? 'View all news';
$view_all_url     = $attributes['viewAllUrl']    ?? '/news';
$number_of_posts  = max( 1, (int) ( $attributes['numberOfPosts'] ?? 4 ) );

$query = new WP_Query( [
	'post_type'      => 'post',
	'posts_per_page' => $number_of_posts,
	'post_status'    => 'publish',
	'orderby'        => 'date',
	'order'          => 'DESC',
	'no_found_rows'  => true,
] );
?>
<section class="news_section cf">
	<div class="wrapper">
		<div class="title_bar">
			<h3><?php echo esc_html( $heading ); ?></h3>
			<?php if ( $view_all_url ) : ?>
				<a href="<?php echo esc_url( $view_all_url ); ?>" class="link white">
					<?php echo esc_html( $view_all_text ); ?>
				</a>
			<?php endif; ?>
		</div><!-- .title_bar -->
	</div><!-- .wrapper -->
	<div class="wrapper large cf">
		<div class="news_blocks">
			<?php if ( $query->have_posts() ) : ?>
				<?php while ( $query->have_posts() ) : $query->the_post(); ?>
					<a href="<?php the_permalink(); ?>" class="article block">
						<div class="article-content">
							<div class="wrap">
								<span class="date"><?php echo esc_html( get_the_date( 'jS M Y' ) ); ?></span>
								<h3><?php the_title(); ?></h3>
								<span class="link">Read more</span>
								<div class="tag_wrap"></div>
							</div><!-- .wrap -->
						</div>
					</a><!-- .article -->
				<?php endwhile; ?>
				<?php wp_reset_postdata(); ?>
			<?php else : ?>
				<p style="padding:20px 0;">No news posts found.</p>
			<?php endif; ?>
		</div><!-- .news_blocks -->
	</div>
</section><!-- .news_section -->
