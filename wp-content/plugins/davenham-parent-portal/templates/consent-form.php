<?php
/**
 * Event consent form ([davenham_event_consent] / injected on event pages).
 *
 * @var bool   $can          Current user is an approved parent.
 * @var string $login_url    Login URL returning here.
 * @var array  $children     Parent's children [name, dob, section].
 * @var int    $event_id     The event.
 * @var array  $errors       Validation errors.
 * @var string $status       'consent_saved' | 'error' | ''.
 * @var array  $existing_map index => [signed_at] for children already done.
 */

defined( 'ABSPATH' ) || exit;
?>
<section id="dpp-consent" class="dpp-consent">
	<h3 class="dpp-consent-title"><?php esc_html_e( 'Event consent', 'davenham-parent-portal' ); ?></h3>

	<?php if ( ! $can ) : ?>

		<p><?php esc_html_e( 'Registered parents can complete consent for this event once logged in.', 'davenham-parent-portal' ); ?></p>
		<p><a class="button" href="<?php echo esc_url( $login_url ); ?>"><?php esc_html_e( 'Log in', 'davenham-parent-portal' ); ?></a></p>

	<?php elseif ( empty( $children ) ) : ?>

		<p class="dpp-muted"><?php esc_html_e( 'No children are recorded on your account yet — please contact a leader.', 'davenham-parent-portal' ); ?></p>

	<?php else : ?>

		<?php if ( 'consent_saved' === $status ) : ?>
			<div class="dpp-message dpp-message--success">
				<p><strong><?php esc_html_e( 'Thank you — your consent has been recorded.', 'davenham-parent-portal' ); ?></strong></p>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $errors ) ) : ?>
			<div class="dpp-message dpp-message--error">
				<ul>
					<?php foreach ( $errors as $error ) : ?>
						<li><?php echo esc_html( $error ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $existing_map ) ) : ?>
			<p class="dpp-consent-done">
				<?php esc_html_e( 'Already submitted for:', 'davenham-parent-portal' ); ?>
				<?php
				$done = array();
				foreach ( $existing_map as $i => $info ) {
					if ( isset( $children[ $i ]['name'] ) ) {
						$done[] = $children[ $i ]['name'];
					}
				}
				echo esc_html( implode( ', ', $done ) );
				?>
				<?php esc_html_e( '(submitting again updates the details).', 'davenham-parent-portal' ); ?>
			</p>
		<?php endif; ?>

		<form class="dpp-form dpp-consent-form" method="post" action="#dpp-consent">
			<?php wp_nonce_field( 'dpp_consent', 'dpp_consent_nonce' ); ?>
			<input type="hidden" name="dpp_form" value="consent" />
			<input type="hidden" name="dpp_event_id" value="<?php echo esc_attr( (string) $event_id ); ?>" />

			<p class="dpp-field">
				<label for="dpp_child_index"><?php esc_html_e( 'Which child is this for?', 'davenham-parent-portal' ); ?> <span class="dpp-req">*</span></label>
				<select id="dpp_child_index" name="dpp_child_index" required>
					<option value=""><?php esc_html_e( 'Choose…', 'davenham-parent-portal' ); ?></option>
					<?php foreach ( $children as $i => $kid ) : ?>
						<option value="<?php echo esc_attr( (string) $i ); ?>"><?php echo esc_html( $kid['name'] ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>

			<p class="dpp-field">
				<span class="dpp-field-label"><?php esc_html_e( 'Is your child attending?', 'davenham-parent-portal' ); ?> <span class="dpp-req">*</span></span>
				<label class="dpp-inline"><input type="radio" name="dpp_attending" value="yes" checked /> <?php esc_html_e( 'Yes', 'davenham-parent-portal' ); ?></label>
				<label class="dpp-inline"><input type="radio" name="dpp_attending" value="no" /> <?php esc_html_e( 'No', 'davenham-parent-portal' ); ?></label>
			</p>

			<p class="dpp-field">
				<span class="dpp-field-label"><?php esc_html_e( 'Photo / media consent', 'davenham-parent-portal' ); ?></span>
				<label class="dpp-inline"><input type="radio" name="dpp_photo" value="yes" checked /> <?php esc_html_e( 'I consent to photos/video', 'davenham-parent-portal' ); ?></label>
				<label class="dpp-inline"><input type="radio" name="dpp_photo" value="no" /> <?php esc_html_e( 'I do not consent', 'davenham-parent-portal' ); ?></label>
			</p>

			<p class="dpp-field">
				<label for="dpp_medical"><?php esc_html_e( 'Medical conditions / allergies', 'davenham-parent-portal' ); ?></label>
				<textarea id="dpp_medical" name="dpp_medical" rows="2"></textarea>
			</p>

			<p class="dpp-field">
				<label for="dpp_medications"><?php esc_html_e( 'Medications carried/needed', 'davenham-parent-portal' ); ?></label>
				<textarea id="dpp_medications" name="dpp_medications" rows="2"></textarea>
			</p>

			<p class="dpp-field">
				<label for="dpp_dietary"><?php esc_html_e( 'Dietary requirements', 'davenham-parent-portal' ); ?></label>
				<textarea id="dpp_dietary" name="dpp_dietary" rows="2"></textarea>
			</p>

			<p class="dpp-field">
				<label for="dpp_additional"><?php esc_html_e( 'Anything else we should know', 'davenham-parent-portal' ); ?></label>
				<textarea id="dpp_additional" name="dpp_additional" rows="2"></textarea>
			</p>

			<div class="dpp-consent-emergency">
				<p class="dpp-field">
					<label for="dpp_emergency_name"><?php esc_html_e( 'Emergency contact name', 'davenham-parent-portal' ); ?></label>
					<input type="text" id="dpp_emergency_name" name="dpp_emergency_name" value="" />
				</p>
				<p class="dpp-field">
					<label for="dpp_emergency_phone"><?php esc_html_e( 'Emergency contact phone', 'davenham-parent-portal' ); ?></label>
					<input type="tel" id="dpp_emergency_phone" name="dpp_emergency_phone" value="" />
				</p>
			</div>

			<p class="dpp-field">
				<label for="dpp_signature"><?php esc_html_e( 'Sign by typing your full name', 'davenham-parent-portal' ); ?> <span class="dpp-req">*</span></label>
				<input type="text" id="dpp_signature" name="dpp_signature" value="" required />
			</p>

			<p class="dpp-submit">
				<button type="submit" class="button"><?php esc_html_e( 'Submit consent', 'davenham-parent-portal' ); ?></button>
			</p>
		</form>

	<?php endif; ?>
</section>
