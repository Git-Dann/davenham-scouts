<?php
/**
 * Render: davenham/age-section
 * SVG icons are served from the active theme's includes/svg directory.
 *
 * @var array $attributes Block attributes.
 */
$heading = $attributes['heading'] ?? 'Aged 6 to 25?';

$all_sections = [
	[ 'show' => 'showSquirrels', 'class' => 'squirrels', 'icon' => 'age-icon-1.svg', 'alt' => 'Squirrels Logo', 'age' => '4-6 years'    ],
	[ 'show' => 'showBeavers',   'class' => 'beavers',   'icon' => 'age-icon-2.svg', 'alt' => 'Beavers Logo',   'age' => '6-8 years'    ],
	[ 'show' => 'showCubs',      'class' => 'cubs',      'icon' => 'age-icon-3.svg', 'alt' => 'Cubs Logo',      'age' => '8-10½ years'  ],
	[ 'show' => 'showScouts',    'class' => 'scouts',    'icon' => 'age-icon-4.svg', 'alt' => 'Scouts Logo',    'age' => '10½-14 years' ],
	[ 'show' => 'showExplorers', 'class' => 'explorers', 'icon' => 'age-icon-5.svg', 'alt' => 'Explorers Logo', 'age' => '14-18 years'  ],
	[ 'show' => 'showNetwork',   'class' => 'network',   'icon' => 'age-icon-6.svg', 'alt' => 'Network Logo',   'age' => '18-25 years'  ],
];

$sections = array_filter( $all_sections, function( $s ) use ( $attributes ) {
	return (bool) ( $attributes[ $s['show'] ] ?? true );
} );
$sections   = array_values( $sections );
$count      = count( $sections );
$theme_uri  = get_template_directory_uri();
$width_class = $count === 6 ? 'six' : '';
?>
<section class="age_section">
	<div class="wrapper cf">
		<div class="title_bar">
			<h3><?php echo esc_html( $heading ); ?></h3>
		</div><!-- .title_bar -->
		<div class="age_blocks cf">
			<div class="age_blocks <?php echo esc_attr( $width_class ); ?> cf">
				<?php foreach ( $sections as $i => $s ) : ?>
				<div class="block block-<?php echo $i + 1; ?>">
					<div class="head <?php echo esc_attr( $s['class'] ); ?>">
						<div class="inner">
							<img src="<?php echo esc_url( $theme_uri . '/includes/svg/' . $s['icon'] ); ?>"
							     alt="<?php echo esc_attr( $s['alt'] ); ?>" />
							<span><?php echo esc_html( $s['age'] ); ?></span>
						</div>
					</div>
				</div>
				<?php endforeach; ?>
			</div>
		</div>
	</div><!-- .wrapper -->
</section><!-- .age_section -->
