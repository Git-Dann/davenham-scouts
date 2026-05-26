<?php
$heading = $attributes['heading'] ?? 'Key facts';
$items   = is_array( $attributes['items'] ?? null ) ? $attributes['items'] : array();
?>
<section class="key_facts_section cf">
	<div class="wrapper">
		<?php if ( $heading ) : ?><h2 class="db-section-heading"><?php echo esc_html( $heading ); ?></h2><?php endif; ?>
		<div class="key_facts_grid">
			<?php foreach ( $items as $item ) : ?>
				<div class="key_fact">
					<?php if ( ! empty( $item['label'] ) ) : ?><div class="key_fact__label"><?php echo esc_html( $item['label'] ); ?></div><?php endif; ?>
					<?php if ( ! empty( $item['value'] ) ) : ?><div class="key_fact__value"><?php echo wp_kses_post( $item['value'] ); ?></div><?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
