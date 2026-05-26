<?php
/**
 * Render: davenham/icon-feature-row
 *
 * @var array $attributes Block attributes.
 */
$columns = $attributes['columns'] ?? [];
if ( empty( $columns ) ) {
	return;
}
?>
<div class="block-icon-row cf">
	<?php foreach ( $columns as $col ) : ?>
		<div class="block-icon">
			<?php if ( ! empty( $col['iconUrl'] ) ) : ?>
				<img src="<?php echo esc_url( $col['iconUrl'] ); ?>" alt="" />
			<?php endif; ?>
			<?php if ( ! empty( $col['text'] ) ) : ?>
				<div class="icon-text"><?php echo wp_kses_post( $col['text'] ); ?></div>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>
</div>
