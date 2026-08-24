<?php
/**
 * Delivery — the "wanneer en met wie" choice in step 2 of the checkout.
 *
 * Overrides probo-connect/templates/checkout/sections/shipping-blocks-shipping-dates.php
 * through wc_get_template(), the same way the theme overrides WooCommerce's own
 * templates. Nothing about the plugin changes: the radio name, the values and
 * the AJAX that hangs off them are the plugin's, and every price on this page is
 * the plugin's own number. What the theme changes is what a customer is asked.
 *
 * The plugin's default asks two questions in a row — first a day, then a
 * carrier. Most people want neither question; they want the parcel fast, or
 * cheap. So this asks one:
 *
 *   1. Snelste       — the first day the API offers, with its cheapest carrier.
 *   2. Voordeligste  — the lowest day total, with the carrier that makes it so.
 *   3. Kies zelf     — reveals the full day list and the full carrier list.
 *
 * A preset answers both underlying questions at once, which is why it is not a
 * date radio: picking one hands the pair straight to the plugin's own state
 * (see assets/js/checkout-steps.js), the plugin stores it, and this block comes
 * back rendered from that stored state. So the preset is never a claim about
 * what is selected — it is only checked here when the plugin says that pair is
 * what is selected.
 *
 * "Cheapest" is the day with the lowest total of its cheapest delivery method
 * plus its rush surcharge — computed here from the data the plugin already
 * passes in ($date['shipping_methods'] carries a price per carrier), never
 * recalculated.
 *
 * @package Probo_Connect
 *
 * @var array $data Delivery data; see the plugin template for the full shape.
 */

defined( 'ABSPATH' ) || exit;

$probo_dates = array_values( (array) ( $data['delivery_dates'] ?? array() ) );

if ( ! $probo_dates ) {
	echo '<p class="text-[15px] text-ink-3">' . esc_html__( 'There are currently no delivery slots available.', 'probo-connect' ) . '</p>';
	return;
}

/**
 * The cheapest delivery (non-pickup) method of a day, and the day's total.
 *
 * @param array $date One delivery date.
 * @return array{method: array|null, total: float}
 */
$probo_best = static function ( $date ) {
	$best = null;

	foreach ( (array) ( $date['shipping_methods'] ?? array() ) as $method ) {
		if ( ! empty( $method['is_pickup'] ) ) {
			continue;
		}

		if ( null === $best || (float) $method['price'] < (float) $best['price'] ) {
			$best = $method;
		}
	}

	return array(
		'method' => $best,
		'total'  => ( $best ? (float) $best['price'] : 0.0 ) + (float) ( $date['rush_surcharge'] ?? 0 ),
	);
};

// Fastest is the first day that can be delivered at all; cheapest is the lowest
// total. Ties go to the earlier day, because it is the better of two equals.
$probo_fastest  = null;
$probo_cheapest = null;

foreach ( $probo_dates as $index => $date ) {
	if ( ! $probo_best( $date )['method'] ) {
		continue;
	}

	if ( null === $probo_fastest ) {
		$probo_fastest = $index;
	}

	if ( null === $probo_cheapest || $probo_best( $date )['total'] < $probo_best( $probo_dates[ $probo_cheapest ] )['total'] ) {
		$probo_cheapest = $index;
	}
}

/**
 * The pair the plugin currently has stored, as day index and method code.
 *
 * @return array{date: int|null, method: string}
 */
$probo_selected = array(
	'date'   => null,
	'method' => '',
);

foreach ( $probo_dates as $index => $date ) {
	if ( empty( $date['selected'] ) ) {
		continue;
	}

	$probo_selected['date'] = $index;

	foreach ( (array) ( $date['shipping_methods'] ?? array() ) as $method ) {
		if ( ! empty( $method['selected'] ) ) {
			$probo_selected['method'] = (string) $method['code'];
		}
	}
}

/**
 * Whether the stored pair is exactly the pair a preset stands for.
 *
 * @param int|null $index Day index the preset points at.
 * @return bool
 */
$probo_is_current = static function ( $index ) use ( $probo_dates, $probo_best, $probo_selected ) {
	if ( null === $index || $index !== $probo_selected['date'] || '' === $probo_selected['method'] ) {
		return false;
	}

	$method = $probo_best( $probo_dates[ $index ] )['method'];

	return $method && (string) $method['code'] === $probo_selected['method'];
};

// One row when the first day is also the cheapest one, which is the common case:
// two rows saying the same thing is not a choice.
$probo_both     = null !== $probo_fastest && $probo_fastest === $probo_cheapest;
$probo_presets  = array();
$probo_saving   = 0.0;
$probo_has_auto = false;

if ( null !== $probo_fastest ) {
	$probo_presets[] = array(
		'key'   => 'fastest',
		'index' => $probo_fastest,
		'title' => $probo_both
			? __( 'Fastest and cheapest', 'probo-connect' )
			: __( 'Fastest', 'probo-connect' ),
		'chip'  => '',
	);
}

if ( ! $probo_both && null !== $probo_cheapest ) {
	$probo_saving = $probo_best( $probo_dates[ $probo_fastest ] )['total'] - $probo_best( $probo_dates[ $probo_cheapest ] )['total'];

	$probo_presets[] = array(
		'key'   => 'cheapest',
		'index' => $probo_cheapest,
		'title' => __( 'Cheapest', 'probo-connect' ),
		// The chip is escaped as text further down, so the amount is decoded here
		// — wc_price() hands back &euro;&nbsp;, which would otherwise be read out
		// as those characters.
		'chip'  => $probo_saving > 0
			? sprintf(
				/* translators: %s: amount saved against the fastest option. */
				__( '%s cheaper', 'probo-connect' ),
				html_entity_decode( wp_strip_all_tags( wc_price( $probo_saving ) ), ENT_QUOTES, get_bloginfo( 'charset' ) )
			)
			: '',
	);
}

foreach ( $probo_presets as $preset ) {
	if ( $probo_is_current( $preset['index'] ) ) {
		$probo_has_auto = true;
	}
}

// Anything the presets do not cover — a later day, another carrier, a pickup
// point — means the customer is steering themselves, so the pickers stay open.
$probo_custom = $probo_presets && ! $probo_has_auto;

/**
 * One day as a full-width option row in the "kies zelf" list.
 *
 * @param array  $date   Delivery date.
 * @param string $kicker Small line under the day.
 * @param string $chip   Optional chip beside the day.
 * @param array  $best   Result of $probo_best().
 */
$probo_row = static function ( $date, $kicker, $chip, $best ) {
	$id = 'date-' . $date['date'];
	?>
	<label class="pp-when-option connect-date-option" for="<?php echo esc_attr( $id ); ?>">
		<input
			id="<?php echo esc_attr( $id ); ?>"
			type="radio"
			name="connect_shipping_date"
			value="<?php echo esc_attr( $date['date'] ); ?>"
			<?php checked( ! empty( $date['selected'] ) ); ?>
		/>
		<span class="pp-when-body">
			<span class="pp-when-day">
				<span class="connect-date-text"><?php echo esc_html( $date['date_formatted'] ); ?></span>
				<?php if ( $chip ) : ?>
					<span class="pp-chip"><?php echo esc_html( $chip ); ?></span>
				<?php endif; ?>
			</span>
			<?php if ( $kicker ) : ?>
				<span class="pp-when-meta"><?php echo esc_html( $kicker ); ?></span>
			<?php endif; ?>
		</span>
		<span class="pp-when-price">
			<?php if ( $best['method'] ) : ?>
				<span class="pp-when-amount"><?php echo wp_kses_post( wc_price( $best['total'] ) ); ?></span>
				<span class="pp-when-price-meta">
					<?php
					if ( (float) ( $date['rush_surcharge'] ?? 0 ) > 0 ) {
						printf(
							/* translators: %s: rush surcharge amount. */
							esc_html__( 'incl. %s rush fee', 'probo-connect' ),
							wp_kses_post( wc_price( (float) $date['rush_surcharge'] ) )
						);
					} else {
						esc_html_e( 'from', 'probo-connect' );
					}
					?>
				</span>
			<?php endif; ?>
		</span>
	</label>
	<?php
};
?>

<div id="connect-shipping-dates" class="connect-shipping-dates pp-when">
	<?php if ( $probo_presets ) : ?>
		<div class="pp-presets">
			<?php
			foreach ( $probo_presets as $preset ) :
				$date   = $probo_dates[ $preset['index'] ];
				$best   = $probo_best( $date );
				$method = $best['method'];
				$id     = 'preset-' . $preset['key'];
				?>
				<label class="pp-when-option pp-preset" for="<?php echo esc_attr( $id ); ?>">
					<input
						id="<?php echo esc_attr( $id ); ?>"
						class="pp-preset-input pp-preset-input--auto"
						type="radio"
						name="probo_delivery_preset"
						value="<?php echo esc_attr( $preset['key'] ); ?>"
						data-probo-date="<?php echo esc_attr( $date['date'] ); ?>"
						data-probo-method="<?php echo esc_attr( $method['code'] ); ?>"
						<?php checked( $probo_is_current( $preset['index'] ) ); ?>
					/>
					<span class="pp-when-body">
						<span class="pp-when-day">
							<?php echo esc_html( $preset['title'] ); ?>
							<?php if ( $preset['chip'] ) : ?>
								<span class="pp-chip"><?php echo esc_html( $preset['chip'] ); ?></span>
							<?php endif; ?>
						</span>
						<span class="pp-when-meta">
							<?php
							echo esc_html(
								implode(
									' · ',
									array_filter(
										array(
											$date['date_formatted'],
											$method['name'],
											trim( (string) ( $method['short_description'] ?? '' ) ),
										)
									)
								)
							);
							?>
						</span>
					</span>
					<span class="pp-when-price">
						<span class="pp-when-amount"><?php echo wp_kses_post( wc_price( $best['total'] ) ); ?></span>
						<?php if ( (float) ( $date['rush_surcharge'] ?? 0 ) > 0 ) : ?>
							<span class="pp-when-price-meta">
								<?php
								printf(
									/* translators: %s: rush surcharge amount. */
									esc_html__( 'incl. %s rush fee', 'probo-connect' ),
									wp_kses_post( wc_price( (float) $date['rush_surcharge'] ) )
								);
								?>
							</span>
						<?php endif; ?>
					</span>
				</label>
			<?php endforeach; ?>

			<label class="pp-when-option pp-preset pp-preset--custom" for="preset-custom">
				<input
					id="preset-custom"
					class="pp-preset-input pp-preset-input--custom"
					type="radio"
					name="probo_delivery_preset"
					value="custom"
					<?php checked( $probo_custom ); ?>
				/>
				<span class="pp-when-body">
					<span class="pp-when-day"><?php esc_html_e( 'Choose yourself', 'probo-connect' ); ?></span>
					<span class="pp-when-meta">
						<?php
						// Naming both counts is the point of this row: a preset shows
						// one carrier, and this says how many are behind it.
						$probo_day = $probo_dates[ null !== $probo_selected['date'] ? $probo_selected['date'] : $probo_fastest ];

						$probo_carriers = count(
							array_filter(
								(array) ( $probo_day['shipping_methods'] ?? array() ),
								static function ( $method ) {
									return empty( $method['is_pickup'] );
								}
							)
						);

						printf(
							/* translators: 1: number of delivery days, 2: number of carriers. */
							esc_html__( 'Choose yourself from %1$s days and %2$s carriers', 'probo-connect' ),
							esc_html( number_format_i18n( count( $probo_dates ) ) ),
							esc_html( number_format_i18n( $probo_carriers ) )
						);
						?>
					</span>
				</span>
			</label>
		</div>
	<?php endif; ?>

	<div class="connect-shipping-dates-list pp-when-list">
		<?php if ( $probo_presets ) : ?>
			<span class="pp-picker-label"><?php esc_html_e( 'Delivery day', 'probo-connect' ); ?></span>
		<?php endif; ?>

		<?php
		foreach ( $probo_dates as $index => $date ) {
			$best = $probo_best( $date );

			if ( $index === $probo_fastest ) {
				$chip = $probo_both ? __( 'Fastest and cheapest', 'probo-connect' ) : __( 'Fastest', 'probo-connect' );
			} elseif ( $index === $probo_cheapest ) {
				$chip = __( 'Cheapest', 'probo-connect' );
			} else {
				$chip = '';
			}

			$probo_row(
				$date,
				$best['method'] ? (string) $best['method']['name'] : '',
				$chip,
				$best
			);
		}
		?>
	</div>
</div>
