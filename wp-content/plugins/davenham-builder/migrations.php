<?php
/**
 * One-time page migration: convert classic-HTML pages into Davenham
 * Builder block markup so they're "ready to go" inside the page builder.
 *
 * Approach is non-destructive: the original post_content is backed up to
 * post_meta (_db_original_content) before any change. A "Restore original"
 * action puts it back at any time.
 *
 * Patterns:
 *   - simple  → page-hero + rich-text (entire content preserved)
 *   - section → page-hero + rich-text(intro) + session-times + rich-text(rest)
 *   - form    → page-hero + rich-text with inline form HTML replaced
 *               by the existing scouts_*_form shortcode
 *
 * Triggered from Builder → Page Migration (admin-only). Dry-run by default;
 * "Apply" only writes when the admin confirms.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Per-page migration plan. Page IDs are the live-site IDs as of writing.
 */
function db_migration_plans() {
	return array(
		12 => array( 'pattern' => 'simple',  'label' => 'About Us' ),
		14 => array( 'pattern' => 'simple',  'label' => 'Centenary Scout Hall' ),
		16 => array( 'pattern' => 'simple',  'label' => 'Volunteer' ),
		22 => array( 'pattern' => 'simple',  'label' => 'Gallery' ),
		24 => array( 'pattern' => 'form',    'label' => 'Contact', 'shortcode' => 'scouts_contact_form' ),
		26 => array( 'pattern' => 'form',    'label' => 'Join',    'shortcode' => 'scouts_join_form' ),
		28 => array( 'pattern' => 'simple',  'label' => 'Parents Area' ),
		32 => array(
			'pattern' => 'section', 'label' => 'Beavers',
			'session' => array(
				'day' => 'Monday', 'time' => '17:30 – 18:45', 'ageRange' => '5¾ – 8 years',
				'location' => '', 'leaders' => 'Gwenda, Dan, Thomas',
			),
		),
		34 => array(
			'pattern' => 'section', 'label' => 'Cubs (Friday)',
			'session' => array(
				'day' => 'Friday', 'time' => '17:30 – 19:00', 'ageRange' => '8 – 10½ years',
				'location' => '', 'leaders' => '',
			),
		),
		36 => array( 'pattern' => 'simple',  'label' => 'Cubs (Tuesday)' ),
		38 => array(
			'pattern' => 'section', 'label' => 'Scouts',
			'session' => array(
				'day' => 'Monday', 'time' => '19:30 – 21:00', 'ageRange' => '10½ – 14½ years',
				'location' => 'Peckmill Scout Wood / Bostock Farm', 'leaders' => '',
			),
		),
		40 => array( 'pattern' => 'simple',  'label' => 'Trustee Board' ),
		42 => array( 'pattern' => 'simple',  'label' => 'Our Supporters' ),
		44 => array( 'pattern' => 'simple',  'label' => 'General Information' ),
		46 => array( 'pattern' => 'simple',  'label' => 'Fundraising' ),
		48 => array( 'pattern' => 'simple',  'label' => 'Vacancies' ),
	);
}

/**
 * Detect whether a page already contains davenham/* block markup.
 */
function db_page_has_davenham_blocks( $content ) {
	return false !== strpos( (string) $content, '<!-- wp:davenham/' );
}

/**
 * Build a single block comment-marker line.
 */
function db_block_marker( $name, $attrs ) {
	// Self-closing block (no inner HTML between markers — render.php handles it).
	$json = empty( $attrs ) ? '' : ' ' . wp_json_encode( $attrs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	return '<!-- wp:' . $name . $json . ' /-->';
}

/**
 * Strip the "Day/Time/Age" paragraph from raw page HTML.
 * Returns [ $stripped_content, $detected_day, $detected_time, $detected_age ].
 */
function db_extract_session_times( $content ) {
	$day = $time = $age = '';

	// Match a paragraph that contains "Day", "Time" and "Age" markers.
	$pattern = '/<p>(?:\s|&nbsp;)*<strong>\s*Day\s*<\/strong>.*?<\/p>/is';
	if ( preg_match( $pattern, $content, $m ) ) {
		$para = $m[0];
		// Pull each value
		if ( preg_match( '/<strong>\s*Day\s*<\/strong>\s*:\s*([^<]+)/i', $para, $d ) )  { $day  = trim( $d[1] ); }
		if ( preg_match( '/<strong>\s*Time\s*<\/strong>\s*:\s*([^<]+)/i', $para, $t ) ) { $time = trim( $t[1] ); }
		if ( preg_match( '/<strong>\s*Age\s*<\/strong>\s*:\s*([^<]+)/i', $para, $a ) )  { $age  = trim( $a[1] ); }
		// Strip the paragraph from content
		$content = preg_replace( $pattern, '', $content, 1 );
	}

	return array( trim( $content ), $day, $time, $age );
}

/**
 * Strip an inline scouts-form HTML block (the editor used to paste it).
 * Replaced by a shortcode that re-renders fresh nonce/fields each load.
 */
function db_strip_inline_form( $content ) {
	// Greedy match the full form_wrap div
	$pattern = '/<div class="form_wrap[^"]*scouts-form[^"]*">.*?<\/form>\s*<\/div>/is';
	return preg_replace( $pattern, '', $content, 1 );
}

/**
 * Build the block markup for "simple" pattern.
 */
function db_build_simple( $original ) {
	$hero = db_block_marker( 'davenham/page-hero', array( 'heading' => '' ) );
	$rt   = db_block_marker( 'davenham/rich-text', array( 'content' => trim( $original ), 'background' => 'white' ) );
	return $hero . "\n\n" . $rt;
}

/**
 * Build the block markup for "section" pattern.
 */
function db_build_section( $original, $plan ) {
	$stripped = db_strip_inline_form( $original );
	list( $body, $det_day, $det_time, $det_age ) = db_extract_session_times( $stripped );

	$session = isset( $plan['session'] ) ? $plan['session'] : array();
	// Plan values win, but fall back to detected from page content
	$attrs = array(
		'day'      => $session['day']      ?? $det_day,
		'time'     => $session['time']     ?? $det_time,
		'ageRange' => $session['ageRange'] ?? $det_age,
		'location' => $session['location'] ?? '',
		'leaders'  => $session['leaders']  ?? '',
	);

	$hero    = db_block_marker( 'davenham/page-hero',      array( 'heading' => '' ) );
	$session = db_block_marker( 'davenham/session-times',  $attrs );
	$rt      = db_block_marker( 'davenham/rich-text',      array( 'content' => trim( $body ), 'background' => 'white' ) );

	return $hero . "\n\n" . $session . "\n\n" . $rt;
}

/**
 * Build the block markup for "form" pattern (Contact / Join).
 */
function db_build_form( $original, $plan ) {
	$stripped = db_strip_inline_form( $original );
	// Append the canonical shortcode so the form re-renders fresh nonces.
	$shortcode = isset( $plan['shortcode'] ) ? '[' . $plan['shortcode'] . ']' : '';
	$body = trim( $stripped ) . ( $shortcode ? "\n\n" . $shortcode : '' );

	$hero = db_block_marker( 'davenham/page-hero', array( 'heading' => '' ) );
	$rt   = db_block_marker( 'davenham/rich-text', array( 'content' => $body, 'background' => 'white' ) );
	return $hero . "\n\n" . $rt;
}

/**
 * Run the migration plan for a single page. Returns either:
 *   [ 'preview' => <new content> ]  (when $apply is false)
 *   [ 'ok' => true, 'before' => …, 'after' => … ] (when $apply is true)
 *   [ 'error' => '…' ] on failure.
 */
function db_migrate_page( $page_id, $apply = false ) {
	$plans = db_migration_plans();
	if ( ! isset( $plans[ $page_id ] ) ) {
		return array( 'error' => 'No migration plan for page ' . (int) $page_id );
	}
	$post = get_post( $page_id );
	if ( ! $post || 'page' !== $post->post_type ) {
		return array( 'error' => 'Page not found (id ' . (int) $page_id . ')' );
	}
	$plan = $plans[ $page_id ];

	$original = (string) $post->post_content;

	if ( db_page_has_davenham_blocks( $original ) ) {
		return array( 'error' => 'Page already contains builder blocks — skipping to avoid overwrite. Use Restore Original first if you want to re-run.' );
	}

	switch ( $plan['pattern'] ) {
		case 'section': $new_content = db_build_section( $original, $plan ); break;
		case 'form':    $new_content = db_build_form(    $original, $plan ); break;
		default:        $new_content = db_build_simple(  $original );        break;
	}

	if ( ! $apply ) {
		return array( 'preview' => $new_content, 'original' => $original );
	}

	// Persist the backup before overwriting
	update_post_meta( $page_id, '_db_original_content', $original );
	update_post_meta( $page_id, '_db_migrated_at', current_time( 'mysql' ) );

	$result = wp_update_post( array(
		'ID'           => $page_id,
		'post_content' => $new_content,
	), true );

	if ( is_wp_error( $result ) ) {
		return array( 'error' => $result->get_error_message() );
	}
	return array( 'ok' => true, 'before_bytes' => strlen( $original ), 'after_bytes' => strlen( $new_content ) );
}

/**
 * Undo a migration. Restores the original content from post_meta backup.
 */
function db_restore_page( $page_id ) {
	$backup = get_post_meta( $page_id, '_db_original_content', true );
	if ( '' === $backup || null === $backup || false === $backup ) {
		return array( 'error' => 'No backup found for page ' . (int) $page_id );
	}
	$result = wp_update_post( array(
		'ID'           => $page_id,
		'post_content' => $backup,
	), true );
	if ( is_wp_error( $result ) ) {
		return array( 'error' => $result->get_error_message() );
	}
	delete_post_meta( $page_id, '_db_original_content' );
	delete_post_meta( $page_id, '_db_migrated_at' );
	return array( 'ok' => true );
}

/**
 * Render the migration admin page.
 */
function db_render_migration_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'davenham-builder' ) );
	}

	$action_msg = '';
	$action_kind = 'success';

	// Handle POSTs (apply / restore) — single page at a time for safety.
	if ( 'POST' === $_SERVER['REQUEST_METHOD'] && check_admin_referer( 'db_migration_action' ) ) {
		$action  = sanitize_text_field( wp_unslash( $_POST['db_action']  ?? '' ) );
		$page_id = (int) ( $_POST['db_page_id'] ?? 0 );

		if ( 'apply' === $action && $page_id ) {
			$r = db_migrate_page( $page_id, true );
			if ( isset( $r['ok'] ) ) {
				$action_msg = sprintf( 'Migrated page %d (%d → %d bytes).', $page_id, $r['before_bytes'], $r['after_bytes'] );
			} else {
				$action_msg = 'Failed: ' . ( $r['error'] ?? 'unknown' );
				$action_kind = 'error';
			}
		} elseif ( 'apply_all' === $action ) {
			$ok = 0; $fail = 0; $skipped = 0; $errors = array();
			foreach ( array_keys( db_migration_plans() ) as $pid ) {
				$r = db_migrate_page( $pid, true );
				if ( isset( $r['ok'] ) )      { $ok++; }
				elseif ( isset( $r['error'] ) && 0 === strpos( $r['error'], 'Page already contains' ) ) { $skipped++; }
				else                            { $fail++; $errors[] = $pid . ': ' . $r['error']; }
			}
			$action_msg = sprintf( 'Migrated %d page(s). Skipped %d already-converted. Failed %d.', $ok, $skipped, $fail );
			if ( $errors ) { $action_msg .= ' Errors: ' . implode( ' / ', $errors ); $action_kind = 'warning'; }
		} elseif ( 'restore' === $action && $page_id ) {
			$r = db_restore_page( $page_id );
			$action_msg = isset( $r['ok'] ) ? sprintf( 'Restored original content for page %d.', $page_id ) : ( 'Failed: ' . $r['error'] );
			if ( ! isset( $r['ok'] ) ) { $action_kind = 'error'; }
		}
	}

	$plans = db_migration_plans();
	?>
	<div class="wrap db-settings-wrap">
		<h1><?php esc_html_e( 'Page Migration', 'davenham-builder' ); ?></h1>

		<p class="db-settings-lede">
			<?php esc_html_e( 'One-time conversion: turn the live classic-HTML pages into ready-to-edit Davenham Builder blocks. Original content is backed up to each page\'s meta and can be restored at any time. The frontend will look identical — only the editor experience changes.', 'davenham-builder' ); ?>
		</p>

		<?php if ( $action_msg ) : ?>
			<div class="notice notice-<?php echo esc_attr( $action_kind ); ?> is-dismissible">
				<p><?php echo esc_html( $action_msg ); ?></p>
			</div>
		<?php endif; ?>

		<section class="db-settings-card">
			<h2><?php esc_html_e( 'Bulk action', 'davenham-builder' ); ?></h2>
			<p class="db-settings-card__desc">
				<?php esc_html_e( 'Run the migration on every eligible page in one go. Pages that have already been converted are skipped, so this is safe to re-run.', 'davenham-builder' ); ?>
			</p>
			<form method="post" onsubmit="return confirm('Migrate every eligible page now? Original content is backed up per page; you can restore individually.');">
				<?php wp_nonce_field( 'db_migration_action' ); ?>
				<input type="hidden" name="db_action" value="apply_all" />
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Migrate all pages', 'davenham-builder' ); ?></button>
			</form>
		</section>

		<section class="db-settings-card">
			<h2><?php esc_html_e( 'Per-page status', 'davenham-builder' ); ?></h2>
			<table class="wp-list-table widefat striped" style="margin-top:8px;">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Page', 'davenham-builder' ); ?></th>
						<th><?php esc_html_e( 'Pattern', 'davenham-builder' ); ?></th>
						<th><?php esc_html_e( 'Status', 'davenham-builder' ); ?></th>
						<th><?php esc_html_e( 'Migrated at', 'davenham-builder' ); ?></th>
						<th style="width:280px;"><?php esc_html_e( 'Actions', 'davenham-builder' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $plans as $page_id => $plan ) :
					$post = get_post( $page_id );
					if ( ! $post ) {
						continue;
					}
					$has_blocks = db_page_has_davenham_blocks( (string) $post->post_content );
					$has_backup = (bool) get_post_meta( $page_id, '_db_original_content', true );
					$migrated_at = get_post_meta( $page_id, '_db_migrated_at', true );
					$edit_url = admin_url( 'admin.php?page=davenham-builder&post_id=' . $page_id );
				?>
					<tr>
						<td>
							<strong><?php echo esc_html( $plan['label'] ); ?></strong>
							<br />
							<small>ID <?php echo (int) $page_id; ?> · <?php echo esc_html( $post->post_name ); ?></small>
						</td>
						<td><?php echo esc_html( ucfirst( $plan['pattern'] ) ); ?></td>
						<td>
							<?php if ( $has_blocks ) : ?>
								<span style="color:#008A1C;font-weight:700;">✓ Migrated</span>
							<?php else : ?>
								<span style="color:#6E6E6E;">Not yet</span>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( $migrated_at ?: '—' ); ?></td>
						<td>
							<?php if ( ! $has_blocks ) : ?>
								<form method="post" style="display:inline-block;margin-right:6px;">
									<?php wp_nonce_field( 'db_migration_action' ); ?>
									<input type="hidden" name="db_action" value="apply" />
									<input type="hidden" name="db_page_id" value="<?php echo (int) $page_id; ?>" />
									<button type="submit" class="button button-secondary">Migrate</button>
								</form>
							<?php else : ?>
								<a href="<?php echo esc_url( $edit_url ); ?>" class="button">Open in Builder</a>
								<?php if ( $has_backup ) : ?>
									<form method="post" style="display:inline-block;margin-left:6px;" onsubmit="return confirm('Restore the original classic content for this page? Builder edits will be lost.');">
										<?php wp_nonce_field( 'db_migration_action' ); ?>
										<input type="hidden" name="db_action" value="restore" />
										<input type="hidden" name="db_page_id" value="<?php echo (int) $page_id; ?>" />
										<button type="submit" class="button button-link-delete">Restore original</button>
									</form>
								<?php endif; ?>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</section>

		<section class="db-settings-card">
			<h2><?php esc_html_e( 'Notes', 'davenham-builder' ); ?></h2>
			<ul style="margin:0;padding-left:20px;color:#404040;line-height:1.7;">
				<li><strong><?php esc_html_e( 'Frontend is unchanged.', 'davenham-builder' ); ?></strong> <?php esc_html_e( 'The Rich Text block outputs the same HTML that was on the live page, so visitors see exactly what they saw before.', 'davenham-builder' ); ?></li>
				<li><?php esc_html_e( 'Section pages (Beavers, Cubs Friday, Scouts) get an additional Session Times block above the body so the meeting day/time/age is editable as a single field.', 'davenham-builder' ); ?></li>
				<li><?php esc_html_e( 'Contact & Join pages get the inline form HTML replaced by the matching shortcode so the nonces are always fresh.', 'davenham-builder' ); ?></li>
				<li><?php esc_html_e( 'Pages we don\'t touch: Home, Shop, Cart, Checkout, My Account, Terms, Cookies, News, Events, and the existing builder example. They render via their own templates.', 'davenham-builder' ); ?></li>
				<li><?php esc_html_e( 'Every migrated page keeps its original content in post meta — use "Restore original" any time to roll back.', 'davenham-builder' ); ?></li>
			</ul>
		</section>
	</div>
	<?php
}
