<?php
/**
 * Render: davenham/rich-text
 * Full-width HTML content area.
 *
 * @var array $attributes Block attributes.
 */
$content    = $attributes['content']    ?? '';
$background = $attributes['background'] ?? 'white';

$bg_styles = [
	'white'  => 'background:#fff;',
	'grey'   => 'background:#F1F1F1;',
	'navy'   => 'background:#003982;color:#fff;',
	'purple' => 'background:#590FA9;color:#fff;',
];
$inline_style = $bg_styles[ $background ] ?? $bg_styles['white'];
?>
<section class="richtext_section cf" style="<?php echo esc_attr( $inline_style ); ?>">
	<div class="wrapper">
		<div class="richtext_content">
			<?php echo wp_kses_post( $content ); ?>
		</div>
	</div><!-- .wrapper -->
</section><!-- .richtext_section -->
