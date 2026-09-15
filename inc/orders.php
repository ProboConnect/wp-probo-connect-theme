<?php
/**
 * Customer orders.
 *
 * The portal's order list: the last so many orders of whoever is logged in, as
 * the design's card — order number, date, total, a status pill and a track &
 * trace link. WooCommerce's own account page has an orders table of its own;
 * this is the one a portal puts on a dashboard beside the customer's products.
 *
 * Track & trace comes from the Probo Connect plugin, which hangs its status
 * data on the order as `_probo_status_data` — `status_page_url` among it. The
 * key is still read through a filter: the plugin owns that name, and a theme
 * that hardcodes another project's meta key has to be edited the day that
 * project renames it.
 *
 * @package Probo_Connect
 */

defined( 'ABSPATH' ) || exit;

/**
 * The order meta key Probo Connect writes its status data to.
 *
 * @return string[] One key, unless a filter says otherwise.
 */
function probo_order_meta_keys() {
	/**
	 * Filters the order meta keys read for Probo Connect's status data.
	 *
	 * Several may be listed; the first one holding data wins. That is what a
	 * shop mid-upgrade needs — the old key behind the new one — rather than a
	 * choice this theme makes on its own.
	 *
	 * @param string[] $keys Meta keys, most likely first.
	 */
	return (array) apply_filters( 'probo_order_meta_keys', array( '_probo_status_data' ) );
}

/**
 * Probo Connect's own data for one order.
 *
 * @param WC_Order $order Order.
 * @return array Empty when the plugin has written nothing to this order.
 */
function probo_order_meta( $order ) {
	$meta = array();

	if ( $order instanceof WC_Order ) {
		foreach ( probo_order_meta_keys() as $key ) {
			$value = $order->get_meta( $key );

			// A serialised array comes back as an array; a payload the plugin
			// stored as JSON comes back as the string it was written as, and
			// WordPress has no idea it is anything else.
			if ( is_string( $value ) && '' !== $value ) {
				$decoded = json_decode( $value, true );
				$value   = is_array( $decoded ) ? $decoded : $value;
			}

			if ( is_array( $value ) && $value ) {
				$meta = $value;
				break;
			}
		}
	}

	/**
	 * Filters Probo Connect's data for one order.
	 *
	 * The single point to override if the plugin exposes this some other way —
	 * a method, an API call, a differently shaped array.
	 *
	 * @param array         $meta  Order data, `status_page_url` among it.
	 * @param WC_Order|null $order Order.
	 */
	return (array) apply_filters( 'probo_order_meta', $meta, $order );
}

/**
 * The track & trace page for one order.
 *
 * @param WC_Order $order Order.
 * @return string URL, or '' when the order has none yet.
 */
function probo_order_status_page_url( $order ) {
	$probo_order_meta = probo_order_meta( $order );
	$url              = isset( $probo_order_meta['status_page_url'] ) ? (string) $probo_order_meta['status_page_url'] : '';

	// Only ever a real link: esc_url_raw() drops anything that is not a URL of
	// a scheme a browser will follow, so a half-written meta value renders as
	// the column's "no track yet" state instead of a broken anchor.
	$url = $url ? (string) esc_url_raw( $url, array( 'http', 'https' ) ) : '';

	/**
	 * Filters the track & trace URL for one order.
	 *
	 * @param string        $url   Status page URL, '' when there is none.
	 * @param WC_Order|null $order Order.
	 */
	return (string) apply_filters( 'probo_order_status_page_url', $url, $order );
}

/**
 * Status slug → the pill's tone.
 *
 * WooCommerce's seven are mapped here. A plugin that registers statuses of its
 * own — a print shop has "in productie" and "bestandscheck" long before
 * "voltooid" — adds them through the filter; anything unmapped draws neutral,
 * which is a colour and never a crash.
 *
 * @return array<string, string> Status slug (no wc- prefix) => tone.
 */
function probo_order_status_tones() {
	/**
	 * Filters the tone each order status is drawn in.
	 *
	 * Tones: 'ok', 'accent', 'warn', 'neutral', 'error'.
	 *
	 * @param array<string, string> $tones Status slug => tone.
	 */
	return (array) apply_filters(
		'probo_order_status_tones',
		array(
			'completed'  => 'ok',
			'processing' => 'accent',
			'on-hold'    => 'warn',
			'pending'    => 'neutral',
			'refunded'   => 'neutral',
			'cancelled'  => 'error',
			'failed'     => 'error',
		)
	);
}

/**
 * The tone one order is drawn in.
 *
 * @param WC_Order $order Order.
 * @return string One of 'ok', 'accent', 'warn', 'neutral', 'error'.
 */
function probo_order_status_tone( $order ) {
	$status = $order instanceof WC_Order ? $order->get_status() : '';
	$tones  = probo_order_status_tones();
	$tone   = $tones[ $status ] ?? 'neutral';

	return in_array( $tone, array( 'ok', 'accent', 'warn', 'neutral', 'error' ), true ) ? $tone : 'neutral';
}

/**
 * A customer's most recent orders.
 *
 * @param int|null $user_id Customer, defaults to the current one.
 * @param int      $limit   How many.
 * @return WC_Order[]
 */
function probo_customer_orders( $user_id = null, $limit = 10 ) {
	$user_id = null === $user_id ? get_current_user_id() : absint( $user_id );

	if ( ! $user_id || ! function_exists( 'wc_get_orders' ) ) {
		return array();
	}

	/**
	 * Filters the query behind a customer's order list.
	 *
	 * @param array $args    wc_get_orders() arguments.
	 * @param int   $user_id Customer id.
	 */
	$args = (array) apply_filters(
		'probo_customer_orders_args',
		array(
			'customer_id' => $user_id,
			'limit'       => max( 1, (int) $limit ),
			'orderby'     => 'date',
			'order'       => 'DESC',
		),
		$user_id
	);

	$orders = wc_get_orders( $args );

	return is_array( $orders ) ? $orders : array();
}

/**
 * Draw the orders card.
 *
 * @param WC_Order[] $orders Orders to list.
 * @param array      $args   Optional: 'all_url' and 'all_text' for the header link.
 */
function probo_render_orders_table( $orders, $args = array() ) {
	if ( ! $orders ) {
		return;
	}

	$args = wp_parse_args(
		$args,
		array(
			'heading'  => '',
			'all_url'  => '',
			'all_text' => __( 'All orders', 'probo-connect-theme' ),
		)
	);
	?>
	<div class="pp-orders-card">
		<?php if ( $args['heading'] || $args['all_url'] ) : ?>
			<div class="pp-orders-head">
				<?php if ( $args['heading'] ) : ?>
					<h2 class="pp-orders-title"><?php echo esc_html( $args['heading'] ); ?></h2>
				<?php endif; ?>

				<?php if ( $args['all_url'] ) : ?>
					<a class="pp-orders-all" href="<?php echo esc_url( $args['all_url'] ); ?>">
						<?php echo esc_html( $args['all_text'] ); ?> <span aria-hidden="true">→</span>
					</a>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php // Five columns do not fit a phone; the table scrolls inside the card rather than pushing the page sideways. ?>
		<div class="pp-orders-scroll">
			<table class="pp-orders-table">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Order', 'probo-connect-theme' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Date', 'probo-connect-theme' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Amount', 'probo-connect-theme' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Status', 'probo-connect-theme' ); ?></th>
						<?php // Plain ampersand: esc_html_e() encodes it, and an entity here would print as "&amp;". ?>
						<th scope="col" class="pp-orders-right"><?php esc_html_e( 'Track & Trace', 'probo-connect-theme' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ( $orders as $probo_order ) :
						if ( ! $probo_order instanceof WC_Order ) {
							continue;
						}

						$probo_track = probo_order_status_page_url( $probo_order );
						$probo_date  = $probo_order->get_date_created();
						?>
						<tr>
							<td class="pp-orders-number">
								<?php // The design draws the number as plain bold text; it is a link to the order all the same — a number nobody can open is a dead end in a portal. ?>
								<a href="<?php echo esc_url( $probo_order->get_view_order_url() ); ?>">
									#<?php echo esc_html( $probo_order->get_order_number() ); ?>
								</a>
							</td>
							<td class="pp-orders-muted">
								<?php echo $probo_date ? esc_html( wc_format_datetime( $probo_date ) ) : '—'; ?>
							</td>
							<td>
								<?php echo wp_kses_post( $probo_order->get_formatted_order_total() ); ?>
							</td>
							<td>
								<span class="pp-order-status pp-order-status--<?php echo esc_attr( probo_order_status_tone( $probo_order ) ); ?>">
									<?php echo esc_html( wc_get_order_status_name( $probo_order->get_status() ) ); ?>
								</span>
							</td>
							<td class="pp-orders-right">
								<?php if ( $probo_track ) : ?>
									<a class="pp-orders-track" href="<?php echo esc_url( $probo_track ); ?>" target="_blank" rel="noopener noreferrer">
										<?php esc_html_e( 'Track', 'probo-connect-theme' ); ?>
										<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'probo-connect-theme' ); ?></span>
									</a>
								<?php else : ?>
									<span class="pp-orders-track is-off">
										<?php esc_html_e( 'Track', 'probo-connect-theme' ); ?>
										<span class="screen-reader-text"><?php esc_html_e( '(not available yet)', 'probo-connect-theme' ); ?></span>
									</span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
	<?php
}
