<?php
defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$event_id    = get_the_ID();
	$meta        = Davenham_Events_Fundraising::event_meta( $event_id );
	$date_label  = Davenham_Events_Fundraising::event_date_label( $event_id );
	$time_label  = Davenham_Events_Fundraising::event_time_label( $event_id );
	$hero_image  = get_the_post_thumbnail_url( $event_id, 'full' );

	// Pick three sibling events for the "More events" related strip at the foot.
	$related_events = get_posts(
		array(
			'post_type'      => 'event',
			'posts_per_page' => 3,
			'post__not_in'   => array( $event_id ),
			'orderby'        => 'meta_value',
			'meta_key'       => '_def_event_date',
			'order'          => 'ASC',
		)
	);

	$events_archive_url = get_post_type_archive_link( 'event' );
?>
<main id="main-content" tabindex="-1">

<section class="event_hero<?php echo $hero_image ? ' event_hero--with-image' : ''; ?>">
	<?php if ( $hero_image ) : ?>
		<img class="event_hero__bg" src="<?php echo esc_url( $hero_image ); ?>" alt="" decoding="async" loading="eager" />
	<?php endif; ?>
	<div class="event_hero__overlay" aria-hidden="true"></div>
	<div class="wrapper">
		<div class="event_hero__inner">
			<nav class="event_hero__crumbs" aria-label="Breadcrumb">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'davenham-events-fundraising' ); ?></a>
				<span aria-hidden="true">/</span>
				<?php if ( $events_archive_url ) : ?>
					<a href="<?php echo esc_url( $events_archive_url ); ?>"><?php esc_html_e( 'Events', 'davenham-events-fundraising' ); ?></a>
					<span aria-hidden="true">/</span>
				<?php endif; ?>
				<span><?php the_title(); ?></span>
			</nav>
			<span class="event_hero__eyebrow"><?php esc_html_e( 'Event', 'davenham-events-fundraising' ); ?></span>
			<h1 class="event_hero__title"><?php the_title(); ?></h1>
			<ul class="event_hero__meta" aria-label="<?php esc_attr_e( 'Event details', 'davenham-events-fundraising' ); ?>">
				<li><span aria-hidden="true">📅</span> <?php echo esc_html( $date_label ); ?></li>
				<?php if ( $time_label ) : ?>
					<li><span aria-hidden="true">🕐</span> <?php echo esc_html( $time_label ); ?></li>
				<?php endif; ?>
				<?php if ( ! empty( $meta['location'] ) ) : ?>
					<li><span aria-hidden="true">📍</span> <?php echo esc_html( $meta['location'] ); ?></li>
				<?php endif; ?>
			</ul>
		</div>
	</div>
</section>

<div class="event_layout">
	<div class="wrapper">
		<div class="event_layout__grid">
			<article id="post-<?php the_ID(); ?>" <?php post_class( 'event_article' ); ?>>
				<div class="event_article__body">
					<?php the_content(); ?>
				</div>

				<?php
				Davenham_Events_Fundraising::render_public_product_grid(
					__( 'Book tickets', 'davenham-events-fundraising' ),
					$meta['ticket_product_ids'],
					__( 'Online tickets are not available for this event yet.', 'davenham-events-fundraising' )
				);

				Davenham_Events_Fundraising::render_public_product_grid(
					__( 'Make a donation', 'davenham-events-fundraising' ),
					$meta['donation_product_ids'],
					__( 'Online donations are not available for this event yet.', 'davenham-events-fundraising' )
				);

				Davenham_Events_Fundraising::render_public_product_grid(
					__( 'Additional items', 'davenham-events-fundraising' ),
					$meta['addon_product_ids'],
					__( 'There are no additional items for this event yet.', 'davenham-events-fundraising' )
				);
				?>
			</article>

			<aside class="event_sidebar">
				<section class="event_sidebar__panel">
					<h2 class="event_sidebar__heading"><?php esc_html_e( 'Event details', 'davenham-events-fundraising' ); ?></h2>
					<dl class="event_sidebar__list">
						<div>
							<dt><?php esc_html_e( 'Date', 'davenham-events-fundraising' ); ?></dt>
							<dd><?php echo esc_html( $date_label ); ?></dd>
						</div>
						<?php if ( $time_label ) : ?>
							<div>
								<dt><?php esc_html_e( 'Time', 'davenham-events-fundraising' ); ?></dt>
								<dd><?php echo esc_html( $time_label ); ?></dd>
							</div>
						<?php endif; ?>
						<?php if ( ! empty( $meta['location'] ) ) : ?>
							<div>
								<dt><?php esc_html_e( 'Location', 'davenham-events-fundraising' ); ?></dt>
								<dd><?php echo esc_html( $meta['location'] ); ?></dd>
							</div>
						<?php endif; ?>
					</dl>
				</section>

				<section class="event_sidebar__panel event_sidebar__panel--cta">
					<h2 class="event_sidebar__heading"><?php esc_html_e( 'Need help?', 'davenham-events-fundraising' ); ?></h2>
					<p><?php esc_html_e( 'If you have questions about tickets, payments, accessibility, or what to bring, contact the group before booking.', 'davenham-events-fundraising' ); ?></p>
					<a class="event_sidebar__cta" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><?php esc_html_e( 'Contact us', 'davenham-events-fundraising' ); ?> →</a>
				</section>
			</aside>
		</div>
	</div>
</div>

<?php if ( $related_events ) : ?>
<section class="event_related">
	<div class="wrapper">
		<div class="event_related__header">
			<h2 class="event_related__heading"><?php esc_html_e( 'More events from the group', 'davenham-events-fundraising' ); ?></h2>
			<?php if ( $events_archive_url ) : ?>
				<a class="event_related__view-all" href="<?php echo esc_url( $events_archive_url ); ?>"><?php esc_html_e( 'View all events', 'davenham-events-fundraising' ); ?> →</a>
			<?php endif; ?>
		</div>
		<div class="event_related__grid">
			<?php foreach ( $related_events as $rel ) :
				$rel_id    = $rel->ID;
				$rel_thumb = get_the_post_thumbnail_url( $rel_id, 'medium_large' );
				$rel_date  = Davenham_Events_Fundraising::event_date_label( $rel_id );
				$rel_meta  = Davenham_Events_Fundraising::event_meta( $rel_id );
			?>
			<a class="event_related__card<?php echo $rel_thumb ? '' : ' event_related__card--no-image'; ?>" href="<?php echo esc_url( get_permalink( $rel_id ) ); ?>">
				<?php if ( $rel_thumb ) : ?>
				<div class="event_related__card-image">
					<img src="<?php echo esc_url( $rel_thumb ); ?>" alt="" loading="lazy" />
				</div>
				<?php endif; ?>
				<div class="event_related__card-body">
					<span class="event_related__card-tag"><?php echo esc_html( $rel_date ); ?></span>
					<h3 class="event_related__card-title"><?php echo esc_html( get_the_title( $rel_id ) ); ?></h3>
					<?php if ( ! empty( $rel_meta['location'] ) ) : ?>
						<span class="event_related__card-location">📍 <?php echo esc_html( $rel_meta['location'] ); ?></span>
					<?php endif; ?>
				</div>
			</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>

</main>
<?php
endwhile;

get_footer();
