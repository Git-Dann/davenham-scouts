<?php
/**
 * Render: davenham/section-shop-grid
 *
 * Six tiles — one per Scout section — each in that section's national
 * brand colour with its logo. Clicking goes to a search-filtered shop
 * view tagged with the section keyword.
 *
 * Mirrors the "Shop by section" pattern from shop.scouts.org.uk.
 *
 * @var array $attributes
 */
$heading  = trim( (string) ( $attributes['heading']  ?? 'Shop by section' ) );
$subtitle = trim( (string) ( $attributes['subtitle'] ?? 'Pick a section to find kit, tickets and resources for that age group.' ) );

$theme_uri = get_template_directory_uri();

// Section data — colours from design.md §12 Age Section Colours.
$sections = array(
	array(
		'slug'    => 'squirrels',
		'name'    => 'Squirrels',
		'age'     => '4 – 6 years',
		'colour'  => '#ED3F23',
		'logo'    => $theme_uri . '/images/logo-squirrels.svg',
	),
	array(
		'slug'    => 'beavers',
		'name'    => 'Beavers',
		'age'     => '6 – 8 years',
		'colour'  => '#088486',
		'logo'    => $theme_uri . '/images/logo-beavers.svg',
	),
	array(
		'slug'    => 'cubs',
		'name'    => 'Cubs',
		'age'     => '8 – 10½ years',
		'colour'  => '#008A1C',
		'logo'    => $theme_uri . '/images/logo-cubs.svg',
	),
	array(
		'slug'    => 'scouts',
		'name'    => 'Scouts',
		'age'     => '10½ – 14 years',
		'colour'  => '#205B41',
		'logo'    => $theme_uri . '/images/logo-scouts.svg',
	),
	array(
		'slug'    => 'explorers',
		'name'    => 'Explorers',
		'age'     => '14 – 18 years',
		'colour'  => '#003982',
		'logo'    => $theme_uri . '/images/logo-explorers.svg',
	),
	array(
		'slug'    => 'network',
		'name'    => 'Network',
		'age'     => '18 – 25 years',
		'colour'  => '#590FA9',
		'logo'    => $theme_uri . '/images/logo-network.svg',
	),
);

$shop_url = class_exists( 'WooCommerce' ) && function_exists( 'wc_get_page_permalink' )
	? wc_get_page_permalink( 'shop' )
	: home_url( '/shop/' );
?>
<section class="section_shop_grid">
	<div class="wrapper">
		<?php if ( $heading || $subtitle ) : ?>
		<div class="section_shop_grid__header">
			<?php if ( $heading ) : ?>
				<h2 class="section_shop_grid__heading"><?php echo esc_html( $heading ); ?></h2>
			<?php endif; ?>
			<?php if ( $subtitle ) : ?>
				<p class="section_shop_grid__subtitle"><?php echo esc_html( $subtitle ); ?></p>
			<?php endif; ?>
		</div>
		<?php endif; ?>

		<div class="section_shop_grid__tiles">
			<?php foreach ( $sections as $sec ) :
				$url = add_query_arg( 's', $sec['slug'], $shop_url );
			?>
			<a class="section_shop_tile"
			   href="<?php echo esc_url( $url ); ?>"
			   style="--section-colour: <?php echo esc_attr( $sec['colour'] ); ?>;"
			   aria-label="Shop for <?php echo esc_attr( $sec['name'] ); ?> (<?php echo esc_attr( $sec['age'] ); ?>)">
				<div class="section_shop_tile__bg" aria-hidden="true"></div>
				<img class="section_shop_tile__logo" src="<?php echo esc_url( $sec['logo'] ); ?>" alt="" aria-hidden="true" />
				<div class="section_shop_tile__content">
					<span class="section_shop_tile__name"><?php echo esc_html( $sec['name'] ); ?></span>
					<span class="section_shop_tile__age"><?php echo esc_html( $sec['age'] ); ?></span>
				</div>
				<span class="section_shop_tile__arrow" aria-hidden="true">→</span>
			</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>
