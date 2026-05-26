<?php
/**
 * Render: davenham/session-times
 * Card showing day / time / age range / location for a section page.
 *
 * @var array $attributes Block attributes.
 */
$day       = trim( (string) ( $attributes['day']       ?? '' ) );
$time      = trim( (string) ( $attributes['time']      ?? '' ) );
$age_range = trim( (string) ( $attributes['ageRange']  ?? '' ) );
$location  = trim( (string) ( $attributes['location']  ?? '' ) );
$leaders   = trim( (string) ( $attributes['leaders']   ?? '' ) );

// Don't render an empty card.
if ( ! $day && ! $time && ! $age_range && ! $location && ! $leaders ) {
	return;
}

$rows = array();
if ( $day )       { $rows[] = array( 'label' => __( 'Day',      'davenham-builder' ), 'value' => $day,       'icon' => '📅' ); }
if ( $time )      { $rows[] = array( 'label' => __( 'Time',     'davenham-builder' ), 'value' => $time,      'icon' => '🕐' ); }
if ( $age_range ) { $rows[] = array( 'label' => __( 'Age',      'davenham-builder' ), 'value' => $age_range, 'icon' => '👥' ); }
if ( $location )  { $rows[] = array( 'label' => __( 'Where',    'davenham-builder' ), 'value' => $location,  'icon' => '📍' ); }
?>
<div class="session_times cf">
	<div class="wrapper">
		<div class="session_times__card">
			<ul class="session_times__list">
				<?php foreach ( $rows as $row ) : ?>
				<li class="session_times__row">
					<span class="session_times__icon" aria-hidden="true"><?php echo esc_html( $row['icon'] ); ?></span>
					<span class="session_times__label"><?php echo esc_html( $row['label'] ); ?></span>
					<span class="session_times__value"><?php echo esc_html( $row['value'] ); ?></span>
				</li>
				<?php endforeach; ?>
			</ul>
			<?php if ( $leaders ) : ?>
			<div class="session_times__leaders">
				<span class="session_times__leaders-label"><?php esc_html_e( 'Leadership team', 'davenham-builder' ); ?></span>
				<span class="session_times__leaders-value"><?php echo esc_html( $leaders ); ?></span>
			</div>
			<?php endif; ?>
		</div>
	</div>
</div>
