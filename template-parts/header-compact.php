<?php
/**
 * Header — Variant B ("compact"): one dark bar (logo, products megamenu,
 * search, account and cart) over a thin light USP strip.
 *
 * The optional counterpart to template-parts/header-ruim.php. It does the same
 * work in ~114px instead of ~180px, so category and product pages keep their
 * hero and first row of products above the fold. It only loads when the
 * "Header style" Customizer setting is explicitly set to "compact"; header.php
 * falls back to Variant A for any other value.
 *
 * The main bar follows the "Top bar" Customizer setting (bar_style / bar_color)
 * through the same --pp-bar-* tokens the spacious header's top strip uses, so a
 * custom top-bar colour repaints this header too. Those tokens are contrast-
 * derived, so the logo, links and hairline stay legible on a dark, light or
 * accent bar alike. Everything else comes from the theme's runtime tokens
 * (--pp-accent, --pp-radius, …) via the same Tailwind utilities the rest of the
 * theme uses.
 *
 * @package Probo_Connect
 */

defined( 'ABSPATH' ) || exit;
?>
<header class="bg-white text-ink">
	<div class="bg-bar-bg text-bar-fg">
		<div class="pp-container flex h-[68px] items-center gap-8">
			<?php probo_logo( 'light', 'text-[22px]' ); ?>

			<button
				class="rounded-pp flex h-11 flex-none items-center gap-[11px] border px-4.5 text-sm font-bold"
				style="border-color:color-mix(in srgb, var(--pp-bar-fg) 24%, transparent)"
				type="button"
				data-probo-products-toggle
				aria-expanded="false"
				aria-controls="pp-products-menu"
			>
				<span class="flex w-3.5 flex-col gap-[3px]" aria-hidden="true">
					<span class="h-0.5 bg-current"></span>
					<span class="h-0.5 bg-current"></span>
					<span class="h-0.5 bg-current"></span>
				</span>
				<?php esc_html_e( 'Products', 'probo-connect' ); ?>
			</button>

			<div class="hidden max-w-[520px] flex-1 min-[720px]:block">
				<?php probo_search_form( 'compact' ); ?>
			</div>

			<div class="ml-auto flex flex-none items-center gap-6 text-sm font-semibold">
				<?php
				if ( has_nav_menu( 'topbar' ) ) {
					wp_nav_menu(
						array(
							'theme_location' => 'topbar',
							'container'      => false,
							'menu_class'     => 'pp-compact-service hidden lg:flex',
							'depth'          => 1,
							'fallback_cb'    => false,
						)
					);
				}
				?>

				<a class="hidden whitespace-nowrap text-bar-fg no-underline hover:opacity-80 lg:inline" href="<?php echo esc_url( probo_account_url() ); ?>">
					<?php echo esc_html( probo_account_link_text() ); ?>
				</a>

				<a class="flex items-center gap-2.5 font-bold whitespace-nowrap text-bar-fg no-underline hover:opacity-80" href="<?php echo esc_url( probo_cart_url() ); ?>">
					<span class="hidden sm:inline"><?php esc_html_e( 'Cart', 'probo-connect' ); ?></span>
					<span class="sm:hidden" aria-hidden="true">🛒</span>
					<span class="font-mono inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-accent px-1 text-[11px] leading-none font-medium text-accent-fg">
						<?php echo esc_html( (string) probo_cart_count() ); ?>
					</span>
				</a>
			</div>
		</div>

		<?php // Below 720px the search leaves the crowded bar for its own full-width row. ?>
		<div class="pp-container pb-3 min-[720px]:hidden">
			<?php probo_search_form( 'compact' ); ?>
		</div>
	</div>

	<?php // The products megamenu: hidden until the trigger opens it, at every width. ?>
	<nav class="relative border-b border-line bg-white" aria-label="<?php esc_attr_e( 'Products', 'probo-connect' ); ?>">
		<div class="pp-container">
			<div id="pp-products-menu" class="hidden py-5" data-probo-products>
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'container'      => false,
						'menu_class'     => 'pp-nav-menu',
						'depth'          => 2,
						'fallback_cb'    => 'probo_primary_menu_fallback',
					)
				);
				?>

				<?php // On small screens the service and account links live in here, not in the bar. ?>
				<div class="mt-4 flex flex-col gap-3 border-t border-line pt-4 text-sm font-semibold lg:hidden">
					<a class="text-ink no-underline hover:text-accent-ink" href="<?php echo esc_url( probo_account_url() ); ?>">
						<?php echo esc_html( probo_account_link_text() ); ?>
					</a>
					<?php
					if ( has_nav_menu( 'topbar' ) ) {
						wp_nav_menu(
							array(
								'theme_location' => 'topbar',
								'container'      => false,
								'menu_class'     => 'pp-compact-service pp-compact-service--stacked',
								'depth'          => 1,
								'fallback_cb'    => false,
							)
						);
					}
					?>
				</div>
			</div>
		</div>
	</nav>

	<div class="border-b border-line bg-white">
		<div class="pp-container flex h-[46px] items-center gap-7 text-xs font-medium leading-none tracking-[0.04em] text-ink-2">
			<?php
			$compact_usps = array_filter( array_map( 'probo_get', array( 'topbar_usp_1', 'topbar_usp_2', 'topbar_usp_3' ) ) );

			foreach ( $compact_usps as $index => $usp ) :
				?>
				<span class="items-center gap-2 whitespace-nowrap <?php echo 0 === $index ? 'flex' : 'hidden min-[1080px]:flex'; ?>">
					<span class="text-accent-ink" aria-hidden="true">✓</span><?php echo esc_html( $usp ); ?>
				</span>
			<?php endforeach; ?>

			<a class="ml-auto font-semibold whitespace-nowrap text-accent-ink no-underline" href="<?php echo esc_url( probo_account_url() ); ?>">
				<?php esc_html_e( 'Business account →', 'probo-connect' ); ?>
			</a>
		</div>
	</div>
</header>
