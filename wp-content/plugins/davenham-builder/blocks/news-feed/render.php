<?php
/**
 * Render: davenham/news-feed
 * Dynamic block — queries WP posts on every page load.
 *
 * @var array $attributes Block attributes.
 */
$heading         = $attributes['heading']       ?? 'What\'s happening';
$subtitle        = $attributes['subtitle']      ?? 'All the latest news for you and your Scouts';
$view_all_text   = $attributes['viewAllText']   ?? 'View all';
$view_all_url    = $attributes['viewAllUrl']    ?? '/news';
$number_of_posts = max( 1, (int) ( $attributes['numberOfPosts'] ?? 3 ) );

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
		<div class="news_section__header">
			<h3><?php echo esc_html( $heading ); ?></h3>
			<?php if ( $subtitle ) : ?>
				<p class="news_section__subtitle"><?php echo esc_html( $subtitle ); ?></p>
			<?php endif; ?>
		</div><!-- .news_section__header -->
		<div class="news_blocks cf">
			<?php if ( $query->have_posts() ) : ?>
				<?php while ( $query->have_posts() ) : $query->the_post(); ?>
					<a href="<?php the_permalink(); ?>" class="news_block<?php echo has_post_thumbnail() ? '' : ' news_block--no-image'; ?>">
						<?php if ( has_post_thumbnail() ) : ?>
							<div class="news_block__image">
								<?php the_post_thumbnail( 'large' ); ?>
							</div>
						<?php endif; ?>
						<div class="news_block__body">
							<h4><?php the_title(); ?></h4>
							<div class="news_block__excerpt"><?php the_excerpt(); ?></div>
						</div><!-- .news_block__body -->
					</a><!-- .news_block -->
				<?php endwhile; ?>
				<?php wp_reset_postdata(); ?>
			<?php else : ?>
				<p class="news_block__empty">No news posts found.</p>
			<?php endif; ?>
		</div><!-- .news_blocks -->
		<?php if ( $view_all_url ) : ?>
		<div class="news_section__footer">
			<a href="<?php echo esc_url( $view_all_url ); ?>" class="btn news_section__btn">
				<?php echo esc_html( $view_all_text ); ?>
			</a>
		</div>
		<?php endif; ?>
	</div><!-- .wrapper -->
</section><!-- .news_section -->
