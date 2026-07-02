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
		<div class="news_blocks">
			<?php if ( $query->have_posts() ) : ?>
				<?php $card_i = 0; ?>
				<?php while ( $query->have_posts() ) : $query->the_post(); ?>
					<?php
					// Clean excerpt: insert a space before each tag so block boundaries
					// don't run words together (e.g. "2026From 1pm"), then trim to a
					// consistent length across all cards.
					$raw_excerpt = has_excerpt() ? get_the_excerpt() : get_the_content();
					$clean       = wp_strip_all_tags( str_replace( '<', ' <', $raw_excerpt ) );
					$excerpt     = wp_trim_words( $clean, 24, '…' );

					// First real category → placeholder label (skip the default
					// "Uncategorized" bucket, which reads badly on a card).
					$cats  = get_the_category();
					$label = 'Latest news';
					if ( ! empty( $cats ) && ! is_wp_error( $cats ) && 'uncategorized' !== strtolower( $cats[0]->slug ) ) {
						$label = $cats[0]->name;
					}
					$has_img = has_post_thumbnail();
					?>
					<a href="<?php the_permalink(); ?>" class="news_block<?php echo $has_img ? '' : ' news_block--no-image'; ?> news_block--i<?php echo (int) ( $card_i % 3 ); ?>">
						<div class="news_block__media">
							<?php if ( $has_img ) : ?>
								<?php the_post_thumbnail( 'large' ); ?>
							<?php else : ?>
								<span class="news_block__placeholder-label"><?php echo esc_html( $label ); ?></span>
							<?php endif; ?>
						</div>
						<div class="news_block__body">
							<span class="news_block__eyebrow"><?php echo esc_html( get_the_date() ); ?></span>
							<h4><?php the_title(); ?></h4>
							<div class="news_block__excerpt"><?php echo esc_html( $excerpt ); ?></div>
							<span class="news_block__more">Read more <span aria-hidden="true">&rarr;</span></span>
						</div><!-- .news_block__body -->
					</a><!-- .news_block -->
					<?php $card_i++; ?>
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
