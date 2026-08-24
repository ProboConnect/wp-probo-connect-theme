<?php
/**
 * Header — Variant A ("ruim"): USP top bar, logo + search + account row,
 * primary navigation. This is the theme's default header.
 *
 * Loaded by header.php via get_template_part() when the "Header style"
 * Customizer setting is "ruim" (or unset). See template-parts/header-compact.php
 * for Variant B.
 *
 * @package Probo_Connect
 */

defined( 'ABSPATH' ) || exit;
?>
<header class="bg-white text-ink">
	<div class="border-b bg-bar-bg text-bar-fg" style="border-color:var(--pp-bar-line)">
		<div class="pp-container flex h-10 items-center justify-between gap-6 text-xs leading-none font-medium tracking-[0.04em]">
			<div class="flex items-center gap-7 overflow-hidden">
				<?php
				foreach ( array( 'topbar_usp_1', 'topbar_usp_2', 'topbar_usp_3' ) as $index => $key ) :
					$usp = probo_get( $key );

					if ( ! $usp ) {
						continue;
					}
					?>
					<span class="flex shrink-0 items-center gap-2 <?php echo 0 === $index ? '' : 'hidden text-bar-muted md:flex'; ?>">
						<span class="text-bar-accent" aria-hidden="true">✓</span>
						<span data-probo-partial="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $usp ); ?></span>
					</span>
				<?php endforeach; ?>
			</div>

			<?php
			if ( has_nav_menu( 'topbar' ) ) {
				wp_nav_menu(
					array(
						'theme_location' => 'topbar',
						'container'      => false,
						'menu_class'     => 'pp-topbar-menu hidden md:flex',
						'depth'          => 1,
						'fallback_cb'    => false,
					)
				);
			}
			?>
		</div>
	</div>

	<div class="pp-container flex items-center gap-6 py-5.5 lg:gap-9">
		<?php probo_logo(); ?>

		<div class="hidden max-w-[640px] flex-1 lg:block">
			<?php probo_search_form( 'header' ); ?>
		</div>

		<div class="ml-auto flex items-center gap-4 text-sm font-semibold lg:gap-6.5">
			<a class="hidden text-ink no-underline hover:text-accent-ink sm:inline" href="<?php echo esc_url( probo_account_url() ); ?>">
				<?php echo esc_html( probo_account_link_text() ); ?>
			</a>

			<a class="rounded-pp flex items-center gap-2.5 bg-secondary px-5 py-3.5 font-bold text-secondary-fg no-underline hover:opacity-90" href="<?php echo esc_url( probo_cart_url() ); ?>">
				<span class="hidden sm:inline"><?php esc_html_e( 'Cart', 'probo-connect' ); ?></span>
				<span class="sm:hidden" aria-hidden="true">🛒</span>
				<span class="font-mono inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-accent px-1 text-[11px] leading-none font-medium text-accent-fg">
					<?php echo esc_html( (string) probo_cart_count() ); ?>
				</span>
			</a>

			<button
				class="rounded-pp border border-line-strong px-3 py-2.5 text-ink lg:hidden"
				type="button"
				data-probo-nav-toggle
				aria-expanded="false"
				aria-controls="pp-primary-nav"
			>
				<span class="sr-only"><?php esc_html_e( 'Menu', 'probo-connect' ); ?></span>
				<span aria-hidden="true">☰</span>
			</button>
		</div>
	</div>

	<div class="pp-container pb-4 lg:hidden">
		<?php probo_search_form( 'header' ); ?>
	</div>

	<?php // relative: the flyout panels are positioned against this bar, not against their own menu item. ?>
<nav class="relative border-y border-line" aria-label="<?php esc_attr_e( 'Primary navigation', 'probo-connect' ); ?>">
		<div class="pp-container">
			<div id="pp-primary-nav" class="hidden py-4 lg:flex lg:h-[50px] lg:items-center lg:py-0" data-probo-nav>
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

				<?php if ( is_active_sidebar( 'nav-account' ) ) : ?>
					<?php dynamic_sidebar( 'nav-account' ); ?>
				<?php else : ?>
					<a class="text-[13px] font-semibold text-ink-3 no-underline lg:ml-auto" href="<?php echo esc_url( probo_account_url() ); ?>">
						<?php esc_html_e( 'Business account →', 'probo-connect' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>
	</nav>
</header>
