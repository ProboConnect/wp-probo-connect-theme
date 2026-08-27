<?php
/**
 * Testimonials block.
 *
 * @package Probo_Connect
 *
 * @var array $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;

// Quote | naam | bedrijf | cijfer — the same line format the USP bar and the
// steps block use, so it is edited with the shared repeater control.
$items = probo_parse_lines( $attributes['items'], 4 );

if ( ! $items ) {
	return;
}

$dark = 'Donker' === $attributes['tone'];
?>
<section
	<?php
	echo probo_block_wrapper( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by get_block_wrapper_attributes().
		$attributes,
		$dark ? 'bg-secondary py-16 text-secondary-fg lg:py-18' : 'py-16 lg:py-18'
	);
	?>
>
	<div class="pp-container">
		<?php if ( $attributes['heading'] ) : ?>
			<h2 class="mb-9 text-3xl font-extrabold tracking-[-0.03em] lg:text-[38px]">
				<?php echo esc_html( $attributes['heading'] ); ?>
			</h2>
		<?php endif; ?>

		<div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
			<?php
			foreach ( $items as $item ) :
				list( $quote, $name, $company, $score ) = $item;
				$stars                                  = max( 0, min( 5, (int) $score ) );
				?>
				<figure class="<?php echo $dark ? 'rounded-pp flex flex-col justify-between p-6.5' : 'pp-card flex flex-col justify-between p-6.5'; ?>"
					<?php echo $dark ? 'style="background:color-mix(in srgb, var(--pp-secondary-fg) 8%, transparent)"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed string. ?>
				>
					<?php if ( $stars ) : ?>
						<div class="font-mono mb-4 text-[13px] font-medium <?php echo $dark ? 'text-accent' : 'text-accent-ink'; ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %d: score out of five. */ __( '%d out of 5 stars', 'probo-connect-theme' ), $stars ) ); ?>">
							<?php echo esc_html( str_repeat( '★', $stars ) . str_repeat( '☆', 5 - $stars ) ); ?>
						</div>
					<?php endif; ?>

					<blockquote class="text-[17px] leading-relaxed <?php echo $dark ? '' : 'text-ink'; ?>">
						<?php echo esc_html( $quote ); ?>
					</blockquote>

					<?php if ( $name || $company ) : ?>
						<figcaption class="mt-5.5 text-[13px]">
							<?php if ( $name ) : ?>
								<span class="font-bold"><?php echo esc_html( $name ); ?></span>
							<?php endif; ?>

							<?php if ( $company ) : ?>
								<span class="<?php echo $dark ? 'opacity-70' : 'text-ink-3'; ?>">
									<?php echo esc_html( ( $name ? '· ' : '' ) . $company ); ?>
								</span>
							<?php endif; ?>
						</figcaption>
					<?php endif; ?>
				</figure>
			<?php endforeach; ?>
		</div>
	</div>
</section>
