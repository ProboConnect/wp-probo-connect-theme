<?php
/**
 * Site header: USP top bar, logo + search + account row, primary navigation.
 *
 * @package Probo_Connect
 */

defined( 'ABSPATH' ) || exit;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<link rel="profile" href="https://gmpg.org/xfn/11" />
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-[100] focus:bg-accent focus:px-4 focus:py-2 focus:text-accent-fg" href="#pp-content">
	<?php esc_html_e( 'Skip to content', 'probo-connect-theme' ); ?>
</a>

<?php
// The checkout gets a header of its own: logo, progress, phone. A search bar
// and a full navigation on the page where someone is trying to finish are exits,
// not service, so nothing below this branch is rendered there.
if ( function_exists( 'probo_is_checkout_flow' ) && probo_is_checkout_flow() ) :
	probo_checkout_header();
	?>
	<div id="pp-content">
	<?php
	return;
endif;

// Variant B (the compact header) is opt-in through the Customizer and must never
// load without an explicit choice: any value other than 'compact' falls back to
// Variant A, the theme's default three-row header. See template-parts/header-*.php.
$probo_header_variant = probo_get( 'header_variant' );

get_template_part( 'template-parts/header', 'compact' === $probo_header_variant ? 'compact' : 'ruim' );
?>

<div id="pp-content">
