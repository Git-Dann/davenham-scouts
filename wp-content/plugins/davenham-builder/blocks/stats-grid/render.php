<?php
$heading = $attributes['heading'] ?? 'Impact in numbers';
$items   = is_array( $attributes['items'] ?? null ) ? $attributes['items'] : array();
?>
<section class="stats_section cf">
	<div class="wrapper">
		<?php if ( $heading ) : ?><h2 class="db-section-heading"><?php echo esc_html( $heading ); ?></h2><?php endif; ?>
		<div class="stats_grid">
			<?php foreach ( $items as $item ) : ?>
				<div class="stats_card">
					<div class="stats_card__value"><?php echo esc_html( $item['value'] ?? '' ); ?></div>
					<h3 class="stats_card__label"><?php echo esc_html( $item['label'] ?? '' ); ?></h3>
					<?php if ( ! empty( $item['detail'] ) ) : ?><div class="stats_card__detail"><?php echo wp_kses_post( $item['detail'] ); ?></div><?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
