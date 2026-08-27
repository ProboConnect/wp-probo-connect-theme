<?php
/**
 * Contact block.
 *
 * Contact details on the left, a form on the right. The form is the theme's
 * own — a real <form> with real inputs, not the design's placeholder divs — and
 * it posts to the handler in inc/contact.php.
 *
 * A shop that already runs a form plugin puts its shortcode in the block's
 * "Form shortcode" field instead; the plugin's form is then rendered in the
 * card and the theme's own handler stays out of the way. That is the escape
 * hatch the design handoff asks for (CF7 / WPForms / REST), without making a
 * plugin a requirement for the block to work at all.
 *
 * @package Probo_Connect
 *
 * @var array $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;

$rows      = probo_parse_lines( $attributes['rows'], 3 );
$shortcode = trim( (string) $attributes['shortcode'] );
$status    = isset( $_GET['probo-contact'] ) ? sanitize_key( wp_unslash( $_GET['probo-contact'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- a display-only flag on a redirect back to the page.
$field     = 'rounded-pp h-11.5 w-full border border-line bg-white px-3.5 text-sm text-ink';
$label     = 'mb-[7px] block text-[13px] font-bold text-ink';
?>
<section <?php echo probo_block_wrapper( $attributes, 'bg-white py-14' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by get_block_wrapper_attributes(). ?>>
	<div class="mx-auto grid max-w-[1120px] items-start gap-14 px-6 lg:grid-cols-[1.1fr_0.9fr] lg:px-10">
		<div>
			<?php if ( $attributes['heading'] ) : ?>
				<h2 class="mb-3 text-[28px] leading-[1.05] font-extrabold tracking-[-0.03em] text-balance lg:text-[36px]">
					<?php echo esc_html( $attributes['heading'] ); ?>
				</h2>
			<?php endif; ?>

			<?php if ( $attributes['intro'] ) : ?>
				<p class="mb-7 max-w-[420px] text-base leading-[1.6] text-pretty text-ink-3"><?php echo esc_html( $attributes['intro'] ); ?></p>
			<?php endif; ?>

			<?php if ( $rows ) : ?>
				<div class="flex flex-col gap-4.5">
					<?php foreach ( $rows as $row ) : ?>
						<div class="flex items-start gap-3.5">
							<span class="rounded-pp flex h-9.5 w-9.5 flex-none items-center justify-center bg-accent-soft text-base text-accent-ink" aria-hidden="true">
								<?php echo esc_html( $row[0] ? $row[0] : '·' ); ?>
							</span>
							<div>
								<div class="text-sm font-bold"><?php echo esc_html( $row[1] ); ?></div>
								<?php if ( $row[2] ) : ?>
									<div class="text-[13px] text-ink-3"><?php echo esc_html( $row[2] ); ?></div>
								<?php endif; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>

		<div class="rounded-pp bg-surface p-7">
			<?php if ( $shortcode ) : ?>
				<?php echo do_shortcode( $shortcode ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- shortcode output, escaped at its source. ?>
			<?php else : ?>
				<?php if ( 'verzonden' === $status ) : ?>
					<p class="rounded-pp m-0 border border-line bg-white p-4.5 text-sm text-ink-2">
						<?php esc_html_e( 'Thanks — your message has been sent. We will get back to you within one working day.', 'probo-connect' ); ?>
					</p>
				<?php else : ?>
					<?php if ( 'fout' === $status ) : ?>
						<p class="rounded-pp mt-0 mb-4.5 border border-l-4 border-line border-l-red-600 bg-white p-4.5 text-sm text-ink-2">
							<?php esc_html_e( 'Your message could not be sent. Please check your name, email address and message.', 'probo-connect' ); ?>
						</p>
					<?php endif; ?>

					<form class="m-0" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="probo_contact" />
						<input type="hidden" name="probo_return" value="<?php echo esc_url( probo_current_url() ); ?>" />
						<?php wp_nonce_field( 'probo_contact' ); ?>

						<div class="mb-3.5 grid gap-3.5 sm:grid-cols-2">
							<div>
								<label class="<?php echo esc_attr( $label ); ?>" for="probo-contact-name"><?php esc_html_e( 'Name', 'probo-connect' ); ?></label>
								<input class="<?php echo esc_attr( $field ); ?>" id="probo-contact-name" name="probo_name" type="text" required placeholder="<?php esc_attr_e( 'First and last name', 'probo-connect' ); ?>" />
							</div>
							<div>
								<label class="<?php echo esc_attr( $label ); ?>" for="probo-contact-email"><?php esc_html_e( 'Email', 'probo-connect' ); ?></label>
								<input class="<?php echo esc_attr( $field ); ?>" id="probo-contact-email" name="probo_email" type="email" required placeholder="name@company.com" />
							</div>
						</div>

						<div class="mb-3.5">
							<label class="<?php echo esc_attr( $label ); ?>" for="probo-contact-order">
								<?php esc_html_e( 'Order number', 'probo-connect' ); ?>
								<span class="font-medium text-ink-4"><?php esc_html_e( '(optional)', 'probo-connect' ); ?></span>
							</label>
							<input class="<?php echo esc_attr( $field ); ?>" id="probo-contact-order" name="probo_order" type="text" placeholder="<?php esc_attr_e( 'e.g. PB-102934', 'probo-connect' ); ?>" />
						</div>

						<div class="mb-4.5">
							<label class="<?php echo esc_attr( $label ); ?>" for="probo-contact-message"><?php esc_html_e( 'Message', 'probo-connect' ); ?></label>
							<textarea class="rounded-pp min-h-26 w-full border border-line bg-white p-3.5 text-sm text-ink" id="probo-contact-message" name="probo_message" required placeholder="<?php esc_attr_e( 'How can we help?', 'probo-connect' ); ?>"></textarea>
						</div>

						<?php
						// The honeypot: a field no one can see and every bot
						// fills in. Hidden with the theme's own screen-reader
						// class so it stays out of the tab order as well.
						?>
						<div class="sr-only" aria-hidden="true">
							<label for="probo-contact-website"><?php esc_html_e( 'Leave this field empty', 'probo-connect' ); ?></label>
							<input id="probo-contact-website" name="probo_website" type="text" tabindex="-1" autocomplete="off" />
						</div>

						<button class="pp-btn-secondary h-13 w-full text-[15px]" type="submit"><?php echo esc_html( $attributes['submitLabel'] ); ?></button>
					</form>
				<?php endif; ?>
			<?php endif; ?>
		</div>
	</div>
</section>
