<?php
$heading = $attributes['heading'] ?? 'Explore by topic';
$items   = array_values( is_array( $attributes['items'] ?? null ) ? $attributes['items'] : array() );
$uid     = 'db-tabs-' . substr( md5( wp_json_encode( $items ) . $heading ), 0, 8 );
?>
<section class="tabs_section cf">
	<div class="wrapper">
		<?php if ( $heading ) : ?><h2 class="db-section-heading"><?php echo esc_html( $heading ); ?></h2><?php endif; ?>
		<div class="db-tabs" data-db-tabs="<?php echo esc_attr( $uid ); ?>">
			<div class="db-tabs__list" role="tablist">
				<?php foreach ( $items as $index => $item ) : $tab_id = $uid . '-tab-' . $index; ?>
					<button class="db-tabs__tab" type="button" role="tab" aria-selected="false" data-db-tab-target="<?php echo esc_attr( $tab_id ); ?>"><?php echo esc_html( $item['title'] ?? 'Tab' ); ?></button>
				<?php endforeach; ?>
			</div>
			<?php foreach ( $items as $index => $item ) : $tab_id = $uid . '-tab-' . $index; ?>
				<div class="db-tabs__panel" role="tabpanel" data-db-tab-panel="<?php echo esc_attr( $tab_id ); ?>" hidden>
					<?php echo wp_kses_post( $item['content'] ?? '' ); ?>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
