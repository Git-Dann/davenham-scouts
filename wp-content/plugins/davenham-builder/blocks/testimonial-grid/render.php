<?php
$heading = $attributes['heading'] ?? 'What people say';
$items   = is_array( $attributes['items'] ?? null ) ? $attributes['items'] : array();
?>
<section class="testimonial_section cf">
	<div class="wrapper">
		<?php if ( $heading ) : ?><h2 class="db-section-heading"><?php echo esc_html( $heading ); ?></h2><?php endif; ?>
		<div class="testimonial_grid">
			<?php foreach ( $items as $item ) : ?>
				<article class="testimonial_card">
					<?php if ( ! empty( $item['quote'] ) ) : ?><div class="testimonial_card__quote"><?php echo wp_kses_post( $item['quote'] ); ?></div><?php endif; ?>
					<?php if ( ! empty( $item['name'] ) ) : ?><div class="testimonial_card__name"><?php echo esc_html( $item['name'] ); ?></div><?php endif; ?>
					<?php if ( ! empty( $item['meta'] ) ) : ?><div class="testimonial_card__meta"><?php echo esc_html( $item['meta'] ); ?></div><?php endif; ?>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
