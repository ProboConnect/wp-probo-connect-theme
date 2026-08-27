<?php
/**
 * The contact block's form handler.
 *
 * The block renders a real form, so something has to receive it. This is the
 * smallest honest handler: a nonce, a honeypot, server-side validation and one
 * wp_mail() to the shop. It exists so the block works on a site with no form
 * plugin at all — a shop that runs Contact Form 7 or WPForms puts that
 * shortcode in the block instead and never reaches this code.
 *
 * @package Probo_Connect
 */

defined( 'ABSPATH' ) || exit;

/**
 * The URL of the request being rendered.
 *
 * The form posts to admin-post.php and has to be sent back to the page it was
 * submitted from, which is not something admin-post.php can work out by itself.
 *
 * @return string
 */
function probo_current_url() {
	global $wp;

	return home_url( add_query_arg( array(), $wp->request ? $wp->request : '' ) );
}

/**
 * Where the contact form's messages are sent.
 *
 * The site's admin address, unless a shop points it somewhere else. It is not a
 * block attribute on purpose: a recipient that travels with the form is a
 * recipient an attacker can rewrite, and this handler would happily mail it.
 *
 * @return string
 */
function probo_contact_recipient() {
	$recipient = get_option( 'admin_email' );

	/**
	 * Filters the contact form's recipient.
	 *
	 * @param string $recipient Email address.
	 */
	return (string) apply_filters( 'probo_contact_recipient', $recipient );
}

/**
 * Receive the contact block's form.
 *
 * Always redirects back to the page the form was on, with the outcome in the
 * query string — the block reads it and draws either the confirmation or the
 * error. Nothing is echoed from here, so a failed send never strands someone
 * on a blank admin-post.php.
 */
function probo_handle_contact_form() {
	$return = isset( $_POST['probo_return'] ) ? esc_url_raw( wp_unslash( $_POST['probo_return'] ) ) : home_url( '/' );

	// wp_safe_redirect() would reject an off-site return URL anyway, but the
	// value is only ever this site's own page, so it is checked here too.
	if ( ! wp_validate_redirect( $return, '' ) ) {
		$return = home_url( '/' );
	}

	$fail = static function () use ( $return ) {
		wp_safe_redirect( add_query_arg( 'probo-contact', 'fout', $return ) . '#contact' );
		exit;
	};

	if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_wpnonce'] ) ), 'probo_contact' ) ) {
		$fail();
	}

	// A filled honeypot is a bot. It is answered with the confirmation rather
	// than an error, so the bot has nothing to learn from and tune against.
	if ( ! empty( $_POST['probo_website'] ) ) {
		wp_safe_redirect( add_query_arg( 'probo-contact', 'verzonden', $return ) . '#contact' );
		exit;
	}

	$name    = isset( $_POST['probo_name'] ) ? sanitize_text_field( wp_unslash( $_POST['probo_name'] ) ) : '';
	$email   = isset( $_POST['probo_email'] ) ? sanitize_email( wp_unslash( $_POST['probo_email'] ) ) : '';
	$order   = isset( $_POST['probo_order'] ) ? sanitize_text_field( wp_unslash( $_POST['probo_order'] ) ) : '';
	$message = isset( $_POST['probo_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['probo_message'] ) ) : '';

	if ( ! $name || ! is_email( $email ) || ! $message ) {
		$fail();
	}

	$body = array(
		sprintf( /* translators: %s: sender name. */ __( 'Name: %s', 'probo-connect-theme' ), $name ),
		sprintf( /* translators: %s: sender email. */ __( 'Email: %s', 'probo-connect-theme' ), $email ),
	);

	if ( $order ) {
		$body[] = sprintf( /* translators: %s: order number. */ __( 'Order number: %s', 'probo-connect-theme' ), $order );
	}

	$body[] = '';
	$body[] = $message;

	$sent = wp_mail(
		probo_contact_recipient(),
		sprintf(
			/* translators: 1: site name, 2: sender name. */
			__( '[%1$s] Contact form — %2$s', 'probo-connect-theme' ),
			wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
			$name
		),
		implode( "\n", $body ),
		array( 'Reply-To: ' . $name . ' <' . $email . '>' )
	);

	if ( ! $sent ) {
		$fail();
	}

	wp_safe_redirect( add_query_arg( 'probo-contact', 'verzonden', $return ) . '#contact' );
	exit;
}
add_action( 'admin_post_probo_contact', 'probo_handle_contact_form' );
add_action( 'admin_post_nopriv_probo_contact', 'probo_handle_contact_form' );
