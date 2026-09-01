<?php
/**
 * Header — Variant C ("portal"): one light bar with the account navigation,
 * the signed-in company and a log-out link.
 *
 * The third option beside template-parts/header-ruim.php (Variant A, the
 * default) and template-parts/header-compact.php (Variant B). It only loads
 * when the "Header style" Customizer setting is explicitly set to "portal";
 * header.php falls back to Variant A for any other value.
 *
 * This is the chrome for a B2B portal rather than a shop front: no search, no
 * USP strip and no cart button, because the questions this header answers are
 * "where am I in my account" and "who am I signed in as". A shop that still
 * needs the cart in the header wants Variant A or B — see the README.
 *
 * The design fixes this band as light: white, an ink wordmark, a hairline
 * underneath. It therefore takes none of the --pp-bar-* tokens Variant B
 * follows, and reads the theme's ordinary surface tokens instead (--pp-line,
 * --pp-ink*, --pp-accent-soft, --pp-accent-ink, --pp-radius) so the accent and
 * corner radius still follow the Customizer.
 *
 * The measure is 1120px, not the 1280px .pp-container gives the rest of the
 * theme. That narrower column is the design's own, and deliberate: a portal
 * carries four nav items and an account chip, not a full shop navigation.
 *
 * @package Probo_Connect
 */

defined( 'ABSPATH' ) || exit;

$probo_portal_company = probo_portal_account_name();
?>
<?php // relative: below lg the nav opens as a panel positioned against this bar. ?>
<header class="relative border-b border-line bg-white text-ink">
	<div class="mx-auto flex h-[68px] w-full max-w-[1120px] items-center gap-6 px-6 lg:gap-12 lg:px-10">
		<?php probo_logo( 'dark', 'text-[22px]' ); ?>

		<?php
		/*
		 * One nav element for both sizes rather than a desktop copy and a
		 * burger copy: from lg it is the row of links the design draws, below
		 * lg the same element drops out of the bar as a panel under it, which
		 * is why it is positioned rather than merely hidden. data-pp-nav is the
		 * hook initNav() in assets/js/theme.js already toggles for Variant A,
		 * including the reset when the viewport crosses 1024px.
		 */
		?>
		<nav
			id="pp-portal-nav"
			class="rounded-pp absolute top-full right-0 left-0 z-40 hidden border-b border-line bg-white px-6 py-4 shadow-lg lg:static lg:z-auto lg:flex lg:rounded-none lg:border-0 lg:px-0 lg:py-0 lg:shadow-none"
			data-pp-nav
			aria-label="<?php esc_attr_e( 'Account navigation', 'probo-connect-theme' ); ?>"
		>
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'container'      => false,
					'menu_class'     => 'pp-portal-menu',
					'depth'          => 1,
					'fallback_cb'    => 'probo_primary_menu_fallback',
				)
			);
			?>
		</nav>

		<div class="ml-auto flex flex-none items-center gap-5 text-sm font-semibold">
			<?php if ( is_user_logged_in() ) : ?>
				<span class="flex items-center gap-[9px] text-ink-3">
					<?php // The initials are a decorative stand-in for the name spelled out beside them. ?>
					<span class="flex h-7 w-7 flex-none items-center justify-center rounded-full bg-accent-soft text-[12px] font-extrabold text-accent-ink" aria-hidden="true">
						<?php echo esc_html( probo_portal_initials( $probo_portal_company ) ); ?>
					</span>
					<span class="hidden whitespace-nowrap sm:inline"><?php echo esc_html( $probo_portal_company ); ?></span>
				</span>

				<a class="whitespace-nowrap text-ink-3 no-underline hover:text-ink" href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>">
					<?php esc_html_e( 'Log out', 'probo-connect-theme' ); ?>
				</a>
			<?php else : ?>
				<a class="whitespace-nowrap text-ink-3 no-underline hover:text-ink" href="<?php echo esc_url( probo_account_url() ); ?>">
					<?php echo esc_html( probo_account_link_text() ); ?>
				</a>
			<?php endif; ?>

			<button
				class="rounded-pp border border-line-strong px-3 py-2.5 text-ink lg:hidden"
				type="button"
				data-pp-nav-toggle
				aria-expanded="false"
				aria-controls="pp-portal-nav"
			>
				<span class="sr-only"><?php esc_html_e( 'Menu', 'probo-connect-theme' ); ?></span>
				<span aria-hidden="true">☰</span>
			</button>
		</div>
	</div>
</header>
