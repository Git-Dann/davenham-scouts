<?php
/**
 * Parent self-registration form ([davenham_parent_register]).
 *
 * @var array  $errors    Validation errors to show.
 * @var array  $values    Previously-submitted values (on error).
 * @var string $status    'success' | 'error' | ''.
 * @var bool   $logged_in Whether the visitor is already logged in.
 * @var array  $sections  slug => label.
 */

defined( 'ABSPATH' ) || exit;

$v_name  = isset( $values['parent_name'] ) ? $values['parent_name'] : '';
$v_email = isset( $values['email'] ) ? $values['email'] : '';
$v_phone = isset( $values['phone'] ) ? $values['phone'] : '';
$v_kids  = ( isset( $values['children'] ) && is_array( $values['children'] ) && ! empty( $values['children'] ) ) ? $values['children'] : array( array( 'name' => '', 'dob' => '', 'section' => '' ) );
?>
<div class="dpp-register">

	<?php if ( $logged_in ) : ?>
		<p class="dpp-note"><?php esc_html_e( 'You are already logged in.', 'davenham-parent-portal' ); ?></p>
	<?php endif; ?>

	<?php if ( 'success' === $status ) : ?>
		<div class="dpp-message dpp-message--success">
			<p><strong><?php esc_html_e( 'Thank you — your registration has been received.', 'davenham-parent-portal' ); ?></strong></p>
			<p><?php esc_html_e( 'A leader will review it shortly. Once approved, you’ll get an email with a link to set your password and log in.', 'davenham-parent-portal' ); ?></p>
		</div>
	<?php else : ?>

		<?php if ( ! empty( $errors ) ) : ?>
			<div class="dpp-message dpp-message--error">
				<p><strong><?php esc_html_e( 'Please check the following:', 'davenham-parent-portal' ); ?></strong></p>
				<ul>
					<?php foreach ( $errors as $error ) : ?>
						<li><?php echo esc_html( $error ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<form class="dpp-form" method="post" action="">
			<?php wp_nonce_field( 'dpp_register', 'dpp_register_nonce' ); ?>
			<input type="hidden" name="dpp_form" value="register" />

			<p class="dpp-field">
				<label for="dpp_parent_name"><?php esc_html_e( 'Your name', 'davenham-parent-portal' ); ?> <span class="dpp-req">*</span></label>
				<input type="text" id="dpp_parent_name" name="dpp_parent_name" value="<?php echo esc_attr( $v_name ); ?>" required />
			</p>

			<p class="dpp-field">
				<label for="dpp_email"><?php esc_html_e( 'Email address', 'davenham-parent-portal' ); ?> <span class="dpp-req">*</span></label>
				<input type="email" id="dpp_email" name="dpp_email" value="<?php echo esc_attr( $v_email ); ?>" required />
			</p>

			<p class="dpp-field">
				<label for="dpp_phone"><?php esc_html_e( 'Phone (optional)', 'davenham-parent-portal' ); ?></label>
				<input type="tel" id="dpp_phone" name="dpp_phone" value="<?php echo esc_attr( $v_phone ); ?>" />
			</p>

			<fieldset class="dpp-children" data-dpp-children>
				<legend><?php esc_html_e( 'Your child(ren)', 'davenham-parent-portal' ); ?> <span class="dpp-req">*</span></legend>

				<?php foreach ( $v_kids as $kid ) : ?>
					<div class="dpp-child-row" data-dpp-child-row>
						<span class="dpp-child-field">
							<label class="screen-reader-text"><?php esc_html_e( 'Child name', 'davenham-parent-portal' ); ?></label>
							<input type="text" name="dpp_child_name[]" value="<?php echo esc_attr( $kid['name'] ); ?>" placeholder="<?php esc_attr_e( 'Child’s name', 'davenham-parent-portal' ); ?>" />
						</span>
						<span class="dpp-child-field">
							<label class="screen-reader-text"><?php esc_html_e( 'Date of birth', 'davenham-parent-portal' ); ?></label>
							<input type="date" name="dpp_child_dob[]" value="<?php echo esc_attr( $kid['dob'] ); ?>" />
						</span>
						<span class="dpp-child-field">
							<label class="screen-reader-text"><?php esc_html_e( 'Section', 'davenham-parent-portal' ); ?></label>
							<select name="dpp_child_section[]">
								<option value=""><?php esc_html_e( 'Choose section…', 'davenham-parent-portal' ); ?></option>
								<?php foreach ( $sections as $slug => $label ) : ?>
									<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $kid['section'], $slug ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</span>
						<button type="button" class="dpp-child-remove" data-dpp-remove aria-label="<?php esc_attr_e( 'Remove child', 'davenham-parent-portal' ); ?>">&times;</button>
					</div>
				<?php endforeach; ?>

				<button type="button" class="dpp-add-child" data-dpp-add-child>+ <?php esc_html_e( 'Add another child', 'davenham-parent-portal' ); ?></button>
			</fieldset>

			<?php // Honeypot — hidden from people, tempting to bots. ?>
			<div class="dpp-hp" aria-hidden="true">
				<label><?php esc_html_e( 'Leave this field empty', 'davenham-parent-portal' ); ?>
					<input type="text" name="dpp_website" value="" tabindex="-1" autocomplete="off" />
				</label>
			</div>

			<p class="dpp-submit">
				<button type="submit" class="button"><?php esc_html_e( 'Register', 'davenham-parent-portal' ); ?></button>
			</p>
			<p class="dpp-privacy"><?php esc_html_e( 'We only use these details to manage your child’s place and to contact you about Scouting. Your registration is reviewed before any login is created.', 'davenham-parent-portal' ); ?></p>
		</form>

	<?php endif; ?>

</div>

<template data-dpp-child-template>
	<div class="dpp-child-row" data-dpp-child-row>
		<span class="dpp-child-field">
			<input type="text" name="dpp_child_name[]" value="" placeholder="<?php esc_attr_e( 'Child’s name', 'davenham-parent-portal' ); ?>" />
		</span>
		<span class="dpp-child-field">
			<input type="date" name="dpp_child_dob[]" value="" />
		</span>
		<span class="dpp-child-field">
			<select name="dpp_child_section[]">
				<option value=""><?php esc_html_e( 'Choose section…', 'davenham-parent-portal' ); ?></option>
				<?php foreach ( $sections as $slug => $label ) : ?>
					<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</span>
		<button type="button" class="dpp-child-remove" data-dpp-remove aria-label="<?php esc_attr_e( 'Remove child', 'davenham-parent-portal' ); ?>">&times;</button>
	</div>
</template>
