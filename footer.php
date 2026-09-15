<?php
/**
 * Site footer.
 *
 * @package Probo_Connect
 */

defined( 'ABSPATH' ) || exit;

// Sidebar id => the Customizer key holding that column's heading.
$probo_columns = array(
	'footer-1' => 'footer_col_1_title',
	'footer-2' => 'footer_col_2_title',
	'footer-3' => 'footer_col_3_title',
);
?>
</div><!-- #pp-content -->

<?php
// On the checkout the link columns go with the navigation: the legal bar is the
// only part of the footer that still has a job there.
$probo_bare = function_exists( 'probo_is_checkout_flow' ) && probo_is_checkout_flow();
?>

<footer class="bg-footer-bg text-footer-fg">
	<?php if ( ! $probo_bare ) : ?>
	<div class="pp-container grid gap-12 py-14 pb-10 md:grid-cols-2 lg:grid-cols-[1.4fr_1fr_1fr_1fr]">
		<div>
			<div class="mb-4"><?php probo_logo( 'light', 'text-[21px]' ); ?></div>

			<?php if ( is_active_sidebar( 'footer-intro' ) ) : ?>
				<div class="pp-footer-widgets max-w-[280px] text-sm leading-relaxed text-footer-muted">
					<?php dynamic_sidebar( 'footer-intro' ); ?>
				</div>
			<?php else : ?>
				<p class="max-w-[280px] text-sm leading-relaxed text-footer-muted" data-pp-partial="footer_description">
					<?php echo esc_html( probo_get( 'footer_description' ) ); ?>
				</p>
			<?php endif; ?>
		</div>

		<?php
		foreach ( $probo_columns as $probo_id => $probo_key ) :
			$probo_title = probo_get( $probo_key );
			?>
			<div class="pp-footer-widgets">
				<?php // The heading is printed here, not by the widget, so it stays put
				// regardless of what gets dropped into the sidebar below it. An empty
				// heading keeps its element for selective refresh, minus the spacing. ?>
				<div class="pp-eyebrow <?php echo '' === $probo_title ? '' : 'mb-4.5 '; ?>text-footer-fg" data-pp-partial="<?php echo esc_attr( $probo_key ); ?>"><?php echo esc_html( $probo_title ); ?></div>

				<?php if ( is_active_sidebar( $probo_id ) ) : ?>
					<?php dynamic_sidebar( $probo_id ); ?>
				<?php else : ?>
					<p class="text-sm text-footer-muted">
						<?php esc_html_e( 'Add a menu or text widget here.', 'probo-connect-theme' ); ?>
					</p>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>

	<div class="<?php echo $probo_bare ? '' : 'border-t'; ?>" style="border-color:var(--pp-footer-line)">
		<div class="pp-container font-mono flex flex-col gap-3 py-5 text-[11px] leading-none font-medium tracking-[0.04em] text-footer-muted sm:flex-row sm:items-center sm:justify-between">
			<span data-pp-partial="footer_legal"><?php echo esc_html( probo_get( 'footer_legal' ) ); ?></span>

			<?php
			if ( has_nav_menu( 'legal' ) ) {
				wp_nav_menu(
					array(
						'theme_location' => 'legal',
						'container'      => false,
						'menu_class'     => 'pp-legal-menu',
						'depth'          => 1,
						'fallback_cb'    => false,
					)
				);
			}
			?>
		</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
