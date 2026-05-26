<?php
/**
 * Render: davenham/news-feed
 * Dynamic block — queries WP posts on every page load.
 *
 * @var array $attributes Block attributes.
 */
$heading         = $attributes['heading']       ?? 'Latest news';
$view_all_text   = $attributes['viewAllText']   ?? 'View all news';
$view_all_url    = $attributes['viewAllUrl']    ?? '/news';
$number_of_posts = max( 1, (int) ( $attributes['numberOfPosts'] ?? 4 ) );

$query = new WP_Query( array(
	'post_type'      => 'post',
	'posts_per_page' => $number_of_posts,
	'post_status'    => 'publish',
	'orderby'        => 'date',
	'order'          => 'DESC',
	'no_found_rows'  => true,
) );
?>
<section class="news_section cf">
	<div class="wrapper">
		<div class="title_bar">
			<h3><?php echo esc_html( $heading ); ?></h3>
			<?php if ( $view_all_url ) : ?>
				<a href="<?php echo esc_url( $view_all_url ); ?>" class="btn outline">
					<?php echo esc_html( $view_all_text ); ?>
				</a>
			<?php endif; ?>
		</div><!-- .title_bar -->
		<div class="news_blocks cf">
			<?php if ( $query->have_posts() ) : ?>
				<?php while ( $query->have_posts() ) : $query->the_post(); ?>
					<div class="news_block">
						<?php if ( has_post_thumbnail() ) : ?>
							<a href="<?php the_permalink(); ?>" class="news_block__image" tabindex="-1" aria-hidden="true">
								<?php the_post_thumbnail( 'medium_large' ); ?>
							</a>
						<?php endif; ?>
						<div class="news_block__body">
							<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
								<?php echo esc_html( get_the_date( 'jS M Y' ) ); ?>
							</time>
							<h4><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>
							<div class="news_block__excerpt"><?php the_excerpt(); ?></div>
							<a href="<?php the_permalink(); ?>" class="btn outline">Read more</a>
						</div><!-- .news_block__body -->
					</div><!-- .news_block -->
				<?php endwhile; ?>
				<?php wp_reset_postdata(); ?>
			<?php else : ?>
				<p class="news_block__empty">No news posts found.</p>
			<?php endif; ?>
		</div><!-- .news_blocks -->
	</div><!-- .wrapper -->
</section><!-- .news_section -->
