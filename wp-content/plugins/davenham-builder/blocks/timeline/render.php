<?php
$heading = $attributes['heading'] ?? 'Our journey';
$items   = is_array( $attributes['items'] ?? null ) ? $attributes['items'] : array();
?>
<section class="timeline_section cf">
	<div class="wrapper">
		<?php if ( $heading ) : ?><h2 class="db-section-heading"><?php echo esc_html( $heading ); ?></h2><?php endif; ?>
		<div class="timeline">
			<?php foreach ( $items as $item ) : ?>
				<div class="timeline_item">
					<div class="timeline_item__year"><?php echo esc_html( $item['year'] ?? '' ); ?></div>
					<div class="timeline_item__content">
						<?php if ( ! empty( $item['title'] ) ) : ?><h3><?php echo esc_html( $item['title'] ); ?></h3><?php endif; ?>
						<?php if ( ! empty( $item['text'] ) ) : ?><div><?php echo wp_kses_post( $item['text'] ); ?></div><?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
