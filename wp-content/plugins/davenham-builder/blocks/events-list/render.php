<?php
/**
 * Render: davenham/events-list
 * Dynamic block. Auto-detects the event CPT in this priority:
 *   1. The Events Calendar  (tribe_events)
 *   2. Custom 'event' CPT
 *   3. Fallback: regular posts tagged 'event'
 *
 * @var array $attributes Block attributes.
 */
$heading         = $attributes['heading']        ?? 'Upcoming events';
$view_all_text   = $attributes['viewAllText']    ?? 'View all events';
$view_all_url    = $attributes['viewAllUrl']     ?? '/events';
$number_of_events = max( 1, (int) ( $attributes['numberOfEvents'] ?? 5 ) );
$today           = current_time( 'Y-m-d' );

// ── Detect which CPT to query ──────────────────────────────────────────────
if ( post_type_exists( 'tribe_events' ) ) {
	// The Events Calendar
	$query = new WP_Query( [
		'post_type'      => 'tribe_events',
		'posts_per_page' => $number_of_events,
		'post_status'    => 'publish',
		'orderby'        => 'meta_value',
		'meta_key'       => '_EventStartDate',
		'order'          => 'ASC',
		'no_found_rows'  => true,
		'meta_query'     => [ [
			'key'     => '_EventStartDate',
			'value'   => $today,
			'compare' => '>=',
			'type'    => 'DATE',
		] ],
	] );
	$cpt = 'tribe_events';

} elseif ( post_type_exists( 'event' ) ) {
	// Generic 'event' custom post type with event_date meta
	$query = new WP_Query( [
		'post_type'      => 'event',
		'posts_per_page' => $number_of_events,
		'post_status'    => 'publish',
		'orderby'        => 'meta_value',
		'meta_key'       => 'event_date',
		'order'          => 'ASC',
		'no_found_rows'  => true,
		'meta_query'     => [ [
			'key'     => 'event_date',
			'value'   => $today,
			'compare' => '>=',
			'type'    => 'DATE',
		] ],
	] );
	$cpt = 'event';

} else {
	// Fallback: regular posts in 'events' category or tagged 'event'
	$query = new WP_Query( [
		'post_type'      => 'post',
		'posts_per_page' => $number_of_events,
		'post_status'    => 'publish',
		'orderby'        => 'date',
		'order'          => 'ASC',
		'no_found_rows'  => true,
		'tag'            => 'event',
	] );
	$cpt = 'post';
}
?>
<section class="events_section cf">
	<div class="wrapper">
		<div class="title_bar blue">
			<h3><?php echo esc_html( $heading ); ?></h3>
			<?php if ( $view_all_url ) : ?>
				<a href="<?php echo esc_url( $view_all_url ); ?>" class="link blue">
					<?php echo esc_html( $view_all_text ); ?>
				</a>
			<?php endif; ?>
		</div><!-- .title_bar -->

		<div class="events_blocks">
			<div class="events_list">
				<?php if ( $query->have_posts() ) : ?>
					<?php while ( $query->have_posts() ) : $query->the_post(); ?>
						<?php
						$event_id  = get_the_ID();
						$event_url = get_permalink();

						// ── Extract event meta by CPT ────────────────────────
						if ( $cpt === 'tribe_events' ) {
							$start_date  = get_post_meta( $event_id, '_EventStartDate', true );
							$end_date    = get_post_meta( $event_id, '_EventEndDate',   true );
							$venue_id    = get_post_meta( $event_id, '_EventVenueID',   true );
							$location    = $venue_id ? get_the_title( $venue_id ) : '';
							$start_ts    = $start_date ? strtotime( $start_date ) : false;
							$end_ts      = $end_date   ? strtotime( $end_date )   : false;

							if ( $start_ts ) {
								$date_display = date_i18n( 'jS', $start_ts ) . ' <span>' . date_i18n( 'M', $start_ts ) . '</span>';
								if ( $end_ts && date( 'Ymd', $start_ts ) !== date( 'Ymd', $end_ts ) ) {
									$date_display .= ' - ' . date_i18n( 'jS', $end_ts ) . ' <span>' . date_i18n( 'M ', $end_ts ) . '</span>';
								}
								$time_display = date_i18n( 'g:i a', $start_ts );
								if ( $end_ts ) {
									$time_display .= ' - ' . date_i18n( 'g:i a', $end_ts );
								}
							} else {
								$date_display = '';
								$time_display = '';
							}

						} elseif ( $cpt === 'event' ) {
							$event_date   = get_post_meta( $event_id, 'event_date',     true );
							$event_time   = get_post_meta( $event_id, 'event_time',     true );
							$event_end    = get_post_meta( $event_id, 'event_end_time', true );
							$location     = get_post_meta( $event_id, 'event_location', true );
							$start_ts     = $event_date ? strtotime( $event_date ) : false;
							$date_display = $start_ts ? date_i18n( 'jS', $start_ts ) . ' <span>' . date_i18n( 'M', $start_ts ) . '</span>' : '';
							$time_display = $event_time ? esc_html( $event_time . ( $event_end ? ' - ' . $event_end : '' ) ) : '';
							$location     = esc_html( $location );

						} else {
							$date_display = get_the_date( 'jS <\s\p\a\n>M</\s\p\a\n>' );
							$time_display = '';
							$location     = '';
						}
						?>
						<div class="event">
							<a href="<?php echo esc_url( $event_url ); ?>" class="full-block"></a>
							<div class="col one">
								<span class="title">Event</span>
								<h5><?php the_title(); ?></h5>
							</div><!-- .col -->
							<div class="col two">
								<span class="title">When</span>
								<p><?php echo $date_display; ?></p>
							</div><!-- .col -->
							<div class="col three">
								<span class="title">Time</span>
								<p><?php echo esc_html( $time_display ); ?></p>
							</div><!-- .col -->
							<div class="col four">
								<span class="title">Location</span>
								<p><?php echo esc_html( $location ); ?></p>
							</div><!-- .col -->
							<div class="col five">
								<a class="link blue" href="<?php echo esc_url( $event_url ); ?>">More Info</a>
							</div><!-- .col -->
						</div><!-- .event -->
					<?php endwhile; ?>
					<?php wp_reset_postdata(); ?>
				<?php else : ?>
					<p style="padding:20px 0;">No upcoming events found.</p>
				<?php endif; ?>
			</div><!-- .events_list -->
		</div><!-- .events_blocks -->
	</div><!-- .wrapper -->
</section><!-- .events_section -->
