<?php
/**
 * Render: davenham/cta-button-row
 *
 * @var array $attributes Block attributes.
 */
$heading = $attributes['heading'] ?? '';
$buttons = $attributes['buttons'] ?? [];
if ( empty( $buttons ) ) {
	return;
}
?>
<?php if ( $heading ) : ?>
<div class="davenham-cta-heading">
	<h3><?php echo wp_kses_post( $heading ); ?></h3>
</div>
<?php endif; ?>
<div class="btn_row">
	<?php foreach ( $buttons as $btn ) : ?>
		<a href="<?php echo esc_url( $btn['url'] ?? '#' ); ?>"
		   class="btn <?php echo esc_attr( $btn['style'] ?? 'outline' ); ?>">
			<?php echo esc_html( $btn['text'] ?? '' ); ?>
		</a>
	<?php endforeach; ?>
</div>
