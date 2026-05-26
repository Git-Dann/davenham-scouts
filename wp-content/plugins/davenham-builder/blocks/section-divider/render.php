<?php
/**
 * Render: davenham/section-divider
 * Blank space or decorative horizontal rule.
 *
 * @var array $attributes Block attributes.
 */
$height = max( 8, (int) ( $attributes['height'] ?? 48 ) );
$style  = $attributes['style'] ?? 'blank';
?>
<?php if ( $style === 'blank' ) : ?>
<div class="section_divider section_divider--blank" style="height:<?php echo $height; ?>px;" aria-hidden="true"></div>

<?php elseif ( $style === 'line' ) : ?>
<div class="section_divider section_divider--line" style="padding:<?php echo round( $height / 2 ); ?>px 0;" aria-hidden="true">
	<div class="wrapper">
		<hr style="border:none;border-top:1px solid #ddd;margin:0;" />
	</div>
</div>

<?php elseif ( $style === 'scouts' ) : ?>
<div class="section_divider section_divider--scouts" style="padding:<?php echo round( $height / 2 ); ?>px 0;text-align:center;" aria-hidden="true">
	<span style="font-size:24px;color:#590FA9;opacity:.35;letter-spacing:12px;">⚜ ⚜ ⚜</span>
</div>
<?php endif; ?>
