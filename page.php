<?php
/**
 * Single page.
 *
 * @package Probo_Connect
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<?php
	// Cart and checkout are pages too, but their content is a shortcode that
	// brings its own full-width layout. Running that through the 760px prose
	// column squeezed the cart's [1fr 380px] grid into 680px, which is why the
	// totals column collapsed. Those two get the bare content instead.
	$probo_is_shop_page = function_exists( 'is_cart' ) && ( is_cart() || is_checkout() );

	// My account brings markup but no layout of its own: WooCommerce lays its
	// navigation and content out in woocommerce-layout.css, which this theme
	// drops wholesale (see probo_dropped_style_handles()). So it gets the
	// container and the heading here, and the two columns from theme.css.
	$probo_is_account = function_exists( 'is_account_page' ) && is_account_page();

	// Logged out, that same page is only the login form (or the password reset):
	// a 460px card that would otherwise sit against the left edge of a 1120px
	// container, with the heading stranded above it. The column is given exactly
	// the card's own width, so the two line up instead of the card floating
	// inside a wider box, and the heading rides along.
	$probo_is_login = $probo_is_account && ! is_user_logged_in();

	if ( $probo_is_shop_page ) :
		the_content();
	elseif ( $probo_is_login ) :
		?>
		<main class="pp-container py-16">
			<div class="mx-auto max-w-[460px]">
				<h1 class="mb-8 text-3xl font-extrabold tracking-[-0.035em] lg:text-4xl"><?php the_title(); ?></h1>
				<?php the_content(); ?>
			</div>
		</main>
		<?php
	elseif ( $probo_is_account ) :
		?>
		<main class="pp-container py-12">
			<h1 class="mb-8 text-3xl font-extrabold tracking-[-0.035em] lg:text-4xl"><?php the_title(); ?></h1>
			<?php the_content(); ?>
		</main>
		<?php
	else :
		?>
		<main class="pp-container py-14">
			<h1 class="mb-6 text-4xl font-extrabold tracking-[-0.035em] lg:text-5xl"><?php the_title(); ?></h1>
			<div class="prose-pp max-w-[760px] text-[17px] leading-relaxed text-ink-2"><?php the_content(); ?></div>
		</main>
		<?php
	endif;
	?>
	<?php
endwhile;

get_footer();
