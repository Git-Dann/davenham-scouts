<?php
$heading = $attributes['heading'] ?? 'Downloads';
$items   = is_array( $attributes['items'] ?? null ) ? $attributes['items'] : array();
?>
<section class="downloads_section cf">
	<div class="wrapper">
		<?php if ( $heading ) : ?><h2 class="db-section-heading"><?php echo esc_html( $heading ); ?></h2><?php endif; ?>
		<div class="downloads_list">
			<?php foreach ( $items as $item ) : ?>
				<div class="downloads_item">
					<div class="downloads_item__copy">
						<strong><?php echo esc_html( $item['title'] ?? '' ); ?></strong>
						<?php if ( ! empty( $item['meta'] ) ) : ?><div class="downloads_item__meta"><?php echo esc_html( $item['meta'] ); ?></div><?php endif; ?>
					</div>
					<?php if ( ! empty( $item['url'] ) ) : ?><a class="btn outline" href="<?php echo esc_url( $item['url'] ); ?>">Download</a><?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
