<?php
$heading = $attributes['heading'] ?? 'How it works';
$items   = is_array( $attributes['items'] ?? null ) ? $attributes['items'] : array();
?>
<section class="steps_section cf">
	<div class="wrapper">
		<?php if ( $heading ) : ?><h2 class="db-section-heading"><?php echo esc_html( $heading ); ?></h2><?php endif; ?>
		<div class="steps_grid">
			<?php foreach ( $items as $index => $item ) : ?>
				<div class="step_card">
					<div class="step_card__number"><?php echo esc_html( (string) ( $index + 1 ) ); ?></div>
					<?php if ( ! empty( $item['title'] ) ) : ?><h3><?php echo esc_html( $item['title'] ); ?></h3><?php endif; ?>
					<?php if ( ! empty( $item['text'] ) ) : ?><div><?php echo wp_kses_post( $item['text'] ); ?></div><?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
