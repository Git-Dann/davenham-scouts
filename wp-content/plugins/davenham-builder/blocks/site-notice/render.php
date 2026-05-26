<?php
/**
 * Render: davenham/site-notice
 *
 * @var array $attributes Block attributes.
 */
$text        = $attributes['text']            ?? '';
$button_text = $attributes['buttonText']      ?? '';
$button_url  = $attributes['buttonUrl']       ?? '#';
$style       = $attributes['style']           ?? 'white';
$bg_color    = $attributes['backgroundColor'] ?? '';

if ( ! $text ) {
	return;
}

// Unique ID for this notice so dismiss state is stored per notice.
$notice_id    = 'db-notice-' . substr( md5( $text ), 0, 8 );
$inline_style = $bg_color ? ' style="background:' . esc_attr( $bg_color ) . ';"' : '';
?>
<div class="site-notice <?php echo esc_attr( $style ); ?>" id="<?php echo esc_attr( $notice_id ); ?>"<?php echo $inline_style; ?>>
	<div class="wrapper">
		<span class="site-notice__text"><?php echo esc_html( $text ); ?></span>
		<?php if ( $button_text ) : ?>
			<a class="notice-btn" href="<?php echo esc_url( $button_url ); ?>">
				<?php echo esc_html( $button_text ); ?>
			</a>
		<?php endif; ?>
		<button class="site-notice__dismiss" aria-label="Dismiss notice" type="button">&#10005;</button>
	</div>
</div><!-- /.site-notice -->

<script>
(function(){
	var key = '<?php echo esc_js( $notice_id ); ?>';
	var el  = document.getElementById( key );
	if ( ! el ) return;
	try {
		if ( localStorage.getItem( key ) ) { el.style.display = 'none'; return; }
	} catch(e) {}
	el.querySelector( '.site-notice__dismiss' ).addEventListener( 'click', function() {
		el.style.display = 'none';
		try { localStorage.setItem( key, '1' ); } catch(e) {}
	} );
})();
</script>
