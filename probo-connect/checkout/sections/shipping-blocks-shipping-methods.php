<?php
/**
 * Carriers and pickup points for the chosen delivery day.
 *
 * Overrides probo-connect/templates/checkout/sections/shipping-blocks-shipping-methods.php
 * through wc_get_template(). The radios, their names and their values are the
 * plugin's; only the arrangement is the theme's.
 *
 * Two changes against the plugin's default, both from the design:
 *
 *  - The carrier is not asked at all in the normal case: the presets in the
 *    dates block above answer it along with the day, and the stylesheet keeps
 *    this list out of the way until someone picks "Kies zelf". Which is why the
 *    carriers are a flat list here rather than one row and a fold — by the time
 *    it is on screen, choosing a carrier is exactly what was asked for.
 *  - Pickup is a tab, not six rows at the bottom of the carrier list. The tabs
 *    are printed by the step, above this block; the panes below are what they
 *    switch between, through a :has(:checked) rule in the stylesheet — no
 *    script, so it also works while the page is still loading. Pickup has no
 *    preset (there is nothing to be fast or cheap about), so its list keeps the
 *    fold: three points, then the rest.
 *
 * @package Probo_Connect
 *
 * @var array $data Shipping methods for the selected day; see the plugin template.
 */

defined( 'ABSPATH' ) || exit;

$probo_methods = array_values( (array) $data );

if ( ! $probo_methods ) {
	echo '<p class="text-[15px] text-ink-3">' . esc_html__( 'First choose a delivery day.', 'probo-connect' ) . '</p>';
	return;
}

$probo_delivery = array_values( array_filter( $probo_methods, static fn( $method ) => empty( $method['is_pickup'] ) ) );
$probo_pickup   = array_values( array_filter( $probo_methods, static fn( $method ) => ! empty( $method['is_pickup'] ) ) );

/**
 * Whether any method in a group is the current choice.
 *
 * @param array $methods Methods.
 * @return bool
 */
$probo_has_selected = static function ( $methods ) {
	foreach ( $methods as $method ) {
		if ( ! empty( $method['selected'] ) ) {
			return true;
		}
	}

	return false;
};

/**
 * One carrier or pickup point as an option row.
 *
 * @param array $method Method.
 */
$probo_method_row = static function ( $method ) {
	$id = 'method-' . $method['code'];
	?>
	<label class="pp-carrier connect-method-option" for="<?php echo esc_attr( $id ); ?>">
		<input
			id="<?php echo esc_attr( $id ); ?>"
			type="radio"
			name="connect_shipping_method"
			value="<?php echo esc_attr( $method['code'] ); ?>"
			<?php checked( ! empty( $method['selected'] ) ); ?>
		/>
		<?php if ( ! empty( $method['icon'] ) ) : ?>
			<img class="connect-method-icon" src="<?php echo esc_url( $method['icon'] ); ?>" alt="" />
		<?php endif; ?>
		<span class="connect-method-description">
			<span class="connect-method-name"><?php echo esc_html( $method['name'] ); ?></span>
			<?php if ( ! empty( $method['short_description'] ) ) : ?>
				<span class="connect-method-short"><?php echo esc_html( $method['short_description'] ); ?></span>
			<?php endif; ?>
		</span>
		<span class="connect-method-price"><?php echo wp_kses_post( $method['price_formatted'] ); ?></span>
	</label>
	<?php
};

/**
 * A group: the rows that stay visible, and the ones behind a fold.
 *
 * @param array  $methods Methods in the group.
 * @param int    $visible How many rows stay out of the fold.
 * @param string $label   Summary label for the fold.
 */
$probo_group = static function ( $methods, $visible, $label ) use ( $probo_method_row, $probo_has_selected ) {
	$shown  = array_slice( $methods, 0, $visible );
	$folded = array_slice( $methods, $visible );

	foreach ( $shown as $method ) {
		$probo_method_row( $method );
	}

	if ( ! $folded ) {
		return;
	}
	?>
	<details class="pp-carriers-more"<?php echo $probo_has_selected( $folded ) ? ' open' : ''; ?>>
		<summary><?php echo esc_html( $label ); ?></summary>
		<div class="pp-carrier-list">
			<?php
			foreach ( $folded as $method ) {
				$probo_method_row( $method );
			}
			?>
		</div>
	</details>
	<?php
};
?>

<div class="connect-shipping-methods pp-carriers" data-pp-pickup-count="<?php echo esc_attr( (string) count( $probo_pickup ) ); ?>">
	<?php if ( $probo_delivery ) : ?>
		<div class="connect-shipping-methods-group connect-shipping-methods-group--delivery pp-delivery-pane pp-delivery-pane--ship">
			<div class="pp-carrier-list">
				<span class="pp-picker-label"><?php esc_html_e( 'Carrier', 'probo-connect' ); ?></span>
				<?php
				foreach ( $probo_delivery as $probo_method ) {
					$probo_method_row( $probo_method );
				}
				?>
			</div>
		</div>
	<?php endif; ?>

	<?php if ( $probo_pickup ) : ?>
		<div class="connect-shipping-methods-group connect-shipping-methods-group--pickup pp-delivery-pane pp-delivery-pane--pickup">
			<div class="pp-carrier-list">
				<span class="pp-picker-label"><?php esc_html_e( 'Pickup point', 'probo-connect' ); ?></span>
				<?php
				$probo_group(
					$probo_pickup,
					3,
					sprintf(
						/* translators: %s: number of pickup points. */
						__( 'All %s pickup points', 'probo-connect' ),
						number_format_i18n( count( $probo_pickup ) )
					)
				);
				?>
			</div>
		</div>
	<?php endif; ?>
</div>
