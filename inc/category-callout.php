<?php
/**
 * Per-category callouts.
 *
 * A callout is a short pitch with a button, stored on the product category
 * itself rather than on a block. A category can carry several — each one
 * chooses its own template independently, so one can run as the wide band
 * while another sits as a tile between the products. They show up in two
 * places: on that category's archive page, and as tiles in the
 * Categorietegels block wherever that category is listed.
 *
 * Storing them on the term is what makes them manageable — the shop edits the
 * text once, next to the category it belongs to, instead of repeating it in
 * every block that happens to show that category.
 *
 * @package Probo_Connect
 */

defined( 'ABSPATH' ) || exit;

/**
 * How many callouts a category can hold.
 *
 * A fixed number of edit-screen slots rather than a JS add/remove repeater —
 * simpler to build, and plenty for what is meant to be a handful of pitches
 * per category, not a feed.
 *
 * @return int
 */
function probo_callout_max_slots() {
	/**
	 * Filters how many callout slots a category's edit screen offers.
	 *
	 * @param int $slots Number of slots.
	 */
	return max( 1, (int) apply_filters( 'probo_callout_max_slots', 6 ) );
}

/**
 * The templates a callout can render as.
 *
 * Two ship with the theme. A child theme or plugin adds its own by hooking
 * this filter — no other file needs to change, and the new template shows up
 * in the "Template" picker automatically.
 *
 * 'placement' decides where the callout lands on the category archive:
 * 'band' renders full-width above the products, 'tile' renders as a grid
 * item between them. In the Categorietegels block every callout is a grid
 * item, so a 'band' template there falls back to the built-in tile.
 *
 * @return array<string, array{label: string, placement: string, callback: callable}>
 */
function probo_callout_templates() {
	$templates = array(
		'tile' => array(
			'label'     => __( 'Tile (between the products)', 'probo-connect' ),
			'placement' => 'tile',
			'callback'  => 'probo_callout_tile',
		),
		'band' => array(
			'label'     => __( 'Banner (above the products)', 'probo-connect' ),
			'placement' => 'band',
			'callback'  => 'probo_callout_banner',
		),
	);

	/**
	 * Filters the callout templates a category can choose from.
	 *
	 * @param array $templates Templates, keyed by slug.
	 */
	return apply_filters( 'probo_callout_templates', $templates );
}

/**
 * Where a callout's chosen template puts it: 'band' or 'tile'.
 *
 * @param array $callout Normalised callout row.
 * @return string
 */
function probo_callout_placement( $callout ) {
	$templates = probo_callout_templates();
	$template  = isset( $templates[ $callout['template'] ] ) ? $templates[ $callout['template'] ] : reset( $templates );

	return isset( $template['placement'] ) && 'band' === $template['placement'] ? 'band' : 'tile';
}

/**
 * Render one callout through its chosen template.
 *
 * @param array $callout Normalised callout row.
 */
function probo_callout_render( $callout ) {
	$templates = probo_callout_templates();
	$template  = isset( $templates[ $callout['template'] ] ) ? $templates[ $callout['template'] ] : reset( $templates );

	if ( ! empty( $template['callback'] ) && is_callable( $template['callback'] ) ) {
		call_user_func( $template['callback'], $callout );
	}
}

/**
 * The callout fields, as key => label.
 *
 * One list drives the edit screen, the save handler and the getter, so a new
 * field is added in exactly one place.
 *
 * @return array<string, array{label: string, type: string, help?: string, options?: array<string, string>}>
 */
function probo_callout_fields() {
	return array(
		'enabled' => array(
			'label' => __( 'Show callout', 'probo-connect' ),
			'type'  => 'checkbox',
			'help'  => __( 'Turning it off keeps the text, but does not show this callout anywhere.', 'probo-connect' ),
		),
		'title' => array(
			'label' => __( 'Title', 'probo-connect' ),
			'type'  => 'text',
			'help'  => __( 'Leave empty = this block will not be used.', 'probo-connect' ),
		),
		'text'  => array(
			'label' => __( 'Text', 'probo-connect' ),
			'type'  => 'textarea',
		),
		'image' => array(
			'label' => __( 'Image', 'probo-connect' ),
			'type'  => 'image',
			'help'  => __( 'Optional.', 'probo-connect' ),
		),
		'cta'   => array(
			'label' => __( 'Button text', 'probo-connect' ),
			'type'  => 'text',
		),
		'url'   => array(
			'label' => __( 'Button URL', 'probo-connect' ),
			'type'  => 'text',
			'help'  => __( 'Leave empty = the category page itself.', 'probo-connect' ),
		),
		'tone'  => array(
			'label'   => __( 'Color', 'probo-connect' ),
			'type'    => 'select',
			'options' => array(
				'Accent'    => __( 'Accent', 'probo-connect' ),
				'Secondary' => __( 'Secondary', 'probo-connect' ),
			),
		),
		'template' => array(
			'label'   => __( 'Template', 'probo-connect' ),
			'type'    => 'select',
			'options' => wp_list_pluck( probo_callout_templates(), 'label' ),
			'help'    => __( 'Want both a banner and a tile with the same pitch? Create two callouts, one per template. In the Category Tiles block, a banner callout is always shown as a tile.', 'probo-connect' ),
		),
		'interval' => array(
			'label' => __( 'Tile after product number', 'probo-connect' ),
			'type'  => 'text',
			'help'  => __( 'Only for the tile view. Appears once, right after this product number — not repeated. Leave empty = after product 4.', 'probo-connect' ),
		),
	);
}

/**
 * The meta key the whole list of callouts is stored under.
 *
 * @return string
 */
function probo_callouts_meta_key() {
	return 'probo_callouts';
}

/**
 * The meta key a single legacy (pre-multiple-callouts) field was stored under.
 *
 * Only read as a fallback, for a category saved before this screen supported
 * more than one callout — so that data is not silently dropped.
 *
 * @param string $field Field key.
 * @return string
 */
function probo_callout_legacy_meta_key( $field ) {
	return 'probo_callout_' . $field;
}

/**
 * Fill in a row's derived values: link fallback, default tone, and so on.
 *
 * @param array   $row  Raw stored row.
 * @param WP_Term $term Product category the row belongs to.
 * @return array|null Normalised row, or null when it has no title or is off.
 */
function probo_callout_normalize_row( $row, $term ) {
	$row = wp_parse_args( $row, array_fill_keys( array_keys( probo_callout_fields() ), '' ) );

	if ( ! $row['title'] ) {
		return null;
	}

	// The toggle decides. A row saved before the toggle existed has no value
	// stored at all; that falls back to the old rule — a title meant it was
	// on — so nothing silently disappears from a live shop.
	if ( '' !== $row['enabled'] && ! $row['enabled'] ) {
		return null;
	}

	unset( $row['enabled'] );

	if ( ! $row['url'] ) {
		$link       = get_term_link( $term );
		$row['url'] = is_wp_error( $link ) ? '' : $link;
	}

	if ( ! $row['tone'] ) {
		$row['tone'] = 'Accent';
	}

	if ( ! isset( $row['template'] ) || ! $row['template'] ) {
		// A row saved before templates existed has a 'display' value instead
		// ('Band', 'Tegel' or 'Beide' — 'Beide' has no template equivalent, so
		// it becomes a band; add a second callout with the tile template for
		// the tile half of what it used to do).
		$legacy_display  = isset( $row['display'] ) ? $row['display'] : '';
		$row['template'] = 'Tegel' === $legacy_display ? 'tile' : 'band';
	}

	$templates = probo_callout_templates();

	if ( ! isset( $templates[ $row['template'] ] ) ) {
		$keys            = array_keys( $templates );
		$row['template'] = isset( $keys[0] ) ? $keys[0] : 'tile';
	}

	unset( $row['display'] );

	$row['interval'] = max( 1, (int) $row['interval'] ?: 4 );

	return $row;
}

/**
 * A category's stored callout rows, exactly as saved — no defaults filled in.
 *
 * Shared by the getter below and the edit screen, so both agree on what a
 * category actually has stored. Without that, the edit screen could show
 * empty slots for a category whose callout still lives only under the old,
 * pre-multiple-callouts meta keys, and saving from there would wipe that data
 * instead of carrying it forward.
 *
 * @param WP_Term $term Product category.
 * @return array<int, array<string, string>>
 */
function probo_callout_raw_rows( $term ) {
	$stored = get_term_meta( $term->term_id, probo_callouts_meta_key(), true );
	$stored = is_array( $stored ) ? array_values( $stored ) : array();

	// A category saved under the old one-callout-per-term storage has no
	// 'probo_callouts' meta at all yet; its legacy fields become row 0 rather
	// than vanishing the first time this runs on it.
	if ( ! $stored ) {
		$legacy = array();

		foreach ( array_keys( probo_callout_fields() ) as $field ) {
			$legacy[ $field ] = (string) get_term_meta( $term->term_id, probo_callout_legacy_meta_key( $field ), true );
		}

		if ( $legacy['title'] ) {
			$stored = array( $legacy );
		}
	}

	return $stored;
}

/**
 * A category's callouts, in the order they were saved.
 *
 * @param WP_Term|int|null $term Term or term id.
 * @return array<int, array{title: string, text: string, image: string, cta: string, url: string, tone: string, template: string, interval: int}>
 */
function probo_category_callouts( $term = null ) {
	$term = $term instanceof WP_Term ? $term : get_term( (int) $term, 'product_cat' );

	if ( ! $term instanceof WP_Term ) {
		return array();
	}

	$callouts = array();

	foreach ( probo_callout_raw_rows( $term ) as $row ) {
		$row = is_array( $row ) ? probo_callout_normalize_row( $row, $term ) : null;

		if ( $row ) {
			$callouts[] = $row;
		}
	}

	/**
	 * Filters a category's callouts.
	 *
	 * @param array   $callouts Normalised callout rows.
	 * @param WP_Term $term     Product category.
	 */
	return apply_filters( 'probo_category_callouts', $callouts, $term );
}

/**
 * One input, in whichever shape the field asks for.
 *
 * @param string $name   Form field name, e.g. 'probo_callout[0][title]'.
 * @param string $id     Form field id.
 * @param array  $config Field configuration.
 * @param string $value  Current value.
 */
function probo_callout_field_input( $name, $id, $config, $value ) {
	if ( 'checkbox' === $config['type'] ) {
		// The hidden field is what makes unchecking stick: an unchecked box sends
		// nothing at all, which would otherwise be indistinguishable from "field
		// not on this screen" in the save handler.
		printf( '<input type="hidden" name="%s" value="0" />', esc_attr( $name ) );
		printf(
			'<label><input type="checkbox" name="%1$s" id="%2$s" value="1"%3$s /> %4$s</label>',
			esc_attr( $name ),
			esc_attr( $id ),
			checked( $value, '1', false ),
			esc_html__( 'On', 'probo-connect' )
		);

		return;
	}

	if ( 'textarea' === $config['type'] ) {
		printf(
			'<textarea name="%1$s" id="%2$s" rows="4" cols="40">%3$s</textarea>',
			esc_attr( $name ),
			esc_attr( $id ),
			esc_textarea( $value )
		);

		return;
	}

	if ( 'image' === $config['type'] ) {
		$attachment_id = absint( $value );
		$image_url     = $attachment_id ? wp_get_attachment_image_url( $attachment_id, 'thumbnail' ) : '';
		?>
		<div class="probo-callout-image-field">
			<input type="hidden" name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $id ); ?>" value="<?php echo esc_attr( $attachment_id ); ?>" />
			<div class="probo-callout-image-preview" style="<?php echo $image_url ? '' : 'display:none;'; ?>">
				<img src="<?php echo esc_url( $image_url ); ?>" alt="" />
			</div>
			<p class="probo-callout-image-buttons">
				<button type="button" class="button probo-callout-image-select"><?php esc_html_e( 'Choose image', 'probo-connect' ); ?></button>
				<button type="button" class="button probo-callout-image-remove" style="<?php echo $image_url ? '' : 'display:none;'; ?>"><?php esc_html_e( 'Remove', 'probo-connect' ); ?></button>
			</p>
		</div>
		<?php
		return;
	}

	if ( 'select' === $config['type'] ) {
		printf( '<select name="%1$s" id="%2$s">', esc_attr( $name ), esc_attr( $id ) );

		foreach ( $config['options'] as $option => $label ) {
			printf(
				'<option value="%s"%s>%s</option>',
				esc_attr( $option ),
				selected( $value, $option, false ),
				esc_html( $label )
			);
		}

		echo '</select>';

		return;
	}

	printf(
		'<input type="text" name="%1$s" id="%2$s" value="%3$s" size="40" />',
		esc_attr( $name ),
		esc_attr( $id ),
		esc_attr( $value )
	);
}

/**
 * The capability that guards callout editing.
 *
 * @return string
 */
function probo_callout_capability() {
	return current_user_can( 'manage_product_terms' ) ? 'manage_product_terms' : 'manage_categories';
}

/**
 * The media library, for the callouts' image fields.
 *
 * @param string $hook Current admin page hook.
 */
function probo_callout_enqueue_media( $hook ) {
	if ( 'product_page_probo-callouts' !== $hook ) {
		return;
	}

	wp_enqueue_media();
}
add_action( 'admin_enqueue_scripts', 'probo_callout_enqueue_media' );

/**
 * Add the screen under Producten.
 *
 * Callouts get their own page rather than a section on the category form: they
 * are theme content, and wedged between WooCommerce's own fields they read as
 * if the plugin owned them. A single screen also means the shop can see every
 * category's callouts at once instead of clicking through them one by one.
 */
function probo_callout_menu() {
	add_submenu_page(
		'edit.php?post_type=product',
		__( 'Callouts', 'probo-connect' ),
		__( 'Callouts', 'probo-connect' ),
		probo_callout_capability(),
		'probo-callouts',
		'probo_callout_screen'
	);
}
add_action( 'admin_menu', 'probo_callout_menu' );

/**
 * The screen: a list of categories, or the form for one of them.
 */
function probo_callout_screen() {
	if ( ! current_user_can( probo_callout_capability() ) ) {
		wp_die( esc_html__( 'You do not have access to this screen.', 'probo-connect' ) );
	}

	$term_id = isset( $_GET['term_id'] ) ? absint( $_GET['term_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only navigation.

	if ( $term_id ) {
		probo_callout_edit_screen( $term_id );
		return;
	}

	probo_callout_list_screen();
}

/**
 * Every product category with the state of its callouts.
 */
function probo_callout_list_screen() {
	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
		)
	);

	$terms = is_wp_error( $terms ) ? array() : $terms;
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Callouts', 'probo-connect' ); ?></h1>

		<p>
			<?php esc_html_e( 'A callout is a small block with a button next to a product category. A category can have several. They appear on the category page and as a tile in the Category Tiles block.', 'probo-connect' ); ?>
		</p>

		<?php if ( isset( $_GET['updated'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only. ?>
			<div class="notice notice-success is-dismissible">
				<p><?php esc_html_e( 'Callouts saved.', 'probo-connect' ); ?></p>
			</div>
		<?php endif; ?>

		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Category', 'probo-connect' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Callouts', 'probo-connect' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Templates', 'probo-connect' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( ! $terms ) : ?>
					<tr>
						<td colspan="3"><?php esc_html_e( 'No product categories yet.', 'probo-connect' ); ?></td>
					</tr>
				<?php endif; ?>

				<?php
				$probo_template_labels = wp_list_pluck( probo_callout_templates(), 'label' );

				foreach ( $terms as $term ) :
					$callouts  = probo_category_callouts( $term );
					$templates = array_unique(
						array_map(
							static function ( $slug ) use ( $probo_template_labels ) {
								return isset( $probo_template_labels[ $slug ] ) ? $probo_template_labels[ $slug ] : $slug;
							},
							wp_list_pluck( $callouts, 'template' )
						)
					);
					$edit      = add_query_arg(
						array(
							'post_type' => 'product',
							'page'      => 'probo-callouts',
							'term_id'   => $term->term_id,
						),
						admin_url( 'edit.php' )
					);
					?>
					<tr>
						<td>
							<strong><a href="<?php echo esc_url( $edit ); ?>"><?php echo esc_html( $term->name ); ?></a></strong>
							<?php if ( $term->parent ) : ?>
								<span class="description"> — <?php esc_html_e( 'subcategory', 'probo-connect' ); ?></span>
							<?php endif; ?>
						</td>
						<td>
							<?php
							if ( $callouts ) {
								printf(
									/* translators: %d: number of callouts. */
									esc_html__( '%d active', 'probo-connect' ),
									count( $callouts )
								);
							} else {
								esc_html_e( 'None', 'probo-connect' );
							}
							?>
						</td>
						<td><?php echo $templates ? esc_html( implode( ', ', $templates ) ) : '<span class="description">—</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- both branches escaped. ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php
}

/**
 * The form for one category: a fixed set of slots, one per possible callout.
 *
 * @param int $term_id Term id.
 */
function probo_callout_edit_screen( $term_id ) {
	$term = get_term( $term_id, 'product_cat' );

	if ( ! $term instanceof WP_Term ) {
		wp_die( esc_html__( 'Category not found.', 'probo-connect' ) );
	}

	$back = add_query_arg(
		array( 'post_type' => 'product', 'page' => 'probo-callouts' ),
		admin_url( 'edit.php' )
	);

	$rows  = probo_callout_raw_rows( $term );
	$slots = probo_callout_max_slots();

	// A row whose 'enabled' was never explicitly set is live on the front end
	// under the old "a title means it's on" rule — probo_callout_normalize_row()
	// applies that same rule. The checkbox has to agree, or opening an existing
	// category's callouts and saving would read as "switch them all off" the
	// first time, since an unchecked box is stored as an explicit '0'.
	foreach ( $rows as $probo_row_index => $probo_row ) {
		$probo_row_enabled = isset( $probo_row['enabled'] ) ? $probo_row['enabled'] : '';

		if ( ! empty( $probo_row['title'] ) && '' === $probo_row_enabled ) {
			$rows[ $probo_row_index ]['enabled'] = '1';
		}
	}
	?>
	<div class="wrap">
		<h1>
			<?php
			printf(
				/* translators: %s: category name. */
				esc_html__( 'Callouts: %s', 'probo-connect' ),
				esc_html( $term->name )
			);
			?>
		</h1>

		<p><a href="<?php echo esc_url( $back ); ?>">&larr; <?php esc_html_e( 'Back to all callouts', 'probo-connect' ); ?></a></p>

		<p class="description">
			<?php
			printf(
				/* translators: %d: number of slots. */
				esc_html__( 'Up to %d callouts per category. Leave the title empty to skip a block.', 'probo-connect' ),
				(int) $slots
			);
			?>
		</p>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="probo_save_callout" />
			<input type="hidden" name="term_id" value="<?php echo esc_attr( $term->term_id ); ?>" />
			<?php wp_nonce_field( 'probo_callout_save_' . $term->term_id, 'probo_callout_nonce' ); ?>

			<?php for ( $i = 0; $i < $slots; $i++ ) : ?>
				<?php $probo_slot_title = isset( $rows[ $i ]['title'] ) ? (string) $rows[ $i ]['title'] : ''; ?>
				<details class="probo-callout-card"<?php echo $probo_slot_title ? ' open' : ''; ?>>
					<summary class="probo-callout-card-header">
						<span class="probo-callout-card-name">
							<?php
							printf(
								/* translators: %d: callout number. */
								esc_html__( 'Callout %d', 'probo-connect' ),
								$i + 1
							);
							?>
						</span>
						<span class="probo-callout-card-status <?php echo $probo_slot_title ? 'is-filled' : 'is-empty'; ?>">
							<?php echo $probo_slot_title ? esc_html( $probo_slot_title ) : esc_html__( 'empty', 'probo-connect' ); ?>
						</span>
					</summary>

					<table class="form-table" role="presentation">
						<?php foreach ( probo_callout_fields() as $field => $config ) : ?>
							<?php
							$name  = sprintf( 'probo_callout[%d][%s]', $i, $field );
							$id    = sprintf( 'probo_callout_%d_%s', $i, $field );
							$value = isset( $rows[ $i ][ $field ] ) ? (string) $rows[ $i ][ $field ] : '';
							?>
							<tr>
								<th scope="row">
									<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $config['label'] ); ?></label>
								</th>
								<td>
									<?php probo_callout_field_input( $name, $id, $config, $value ); ?>
									<?php if ( ! empty( $config['help'] ) ) : ?>
										<p class="description"><?php echo esc_html( $config['help'] ); ?></p>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</table>
				</details>
			<?php endfor; ?>

			<?php submit_button( __( 'Save callouts', 'probo-connect' ) ); ?>
		</form>
	</div>
	<style>
	.probo-callout-card {
		background: #fff;
		border: 1px solid #dcdcde;
		border-radius: 4px;
		margin-bottom: 12px;
		box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
	}

	.probo-callout-card-header {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 12px;
		padding: 12px 16px;
		cursor: pointer;
		font-weight: 600;
		list-style: none;
	}

	.probo-callout-card-header::-webkit-details-marker {
		display: none;
	}

	.probo-callout-card-header::before {
		content: '\25B8';
		display: inline-block;
		margin-right: 8px;
		color: #646970;
		transition: transform .1s ease-in-out;
	}

	.probo-callout-card[open] > .probo-callout-card-header::before {
		transform: rotate( 90deg );
	}

	.probo-callout-card-name {
		flex: 0 0 auto;
	}

	.probo-callout-card-status {
		flex: 1 1 auto;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
		text-align: right;
		font-weight: 400;
		font-size: 13px;
	}

	.probo-callout-card-status.is-filled {
		color: #1d2327;
	}

	.probo-callout-card-status.is-empty {
		color: #949494;
		font-style: italic;
	}

	.probo-callout-card > .form-table {
		margin: 0;
		border-top: 1px solid #dcdcde;
		padding: 0 16px;
	}

	.probo-callout-image-preview {
		display: inline-block;
		margin-bottom: 8px;
		padding: 4px;
		border: 1px solid #dcdcde;
		border-radius: 4px;
		background: #f6f7f7;
	}

	.probo-callout-image-preview img {
		display: block;
		max-width: 150px;
		height: auto;
		border-radius: 2px;
	}

	.probo-callout-image-buttons {
		margin: 0;
	}
	</style>
	<script>
	( function () {
		document.querySelectorAll( '.probo-callout-image-field' ).forEach( function ( field ) {
			var input   = field.querySelector( 'input[type="hidden"]' );
			var preview = field.querySelector( '.probo-callout-image-preview' );
			var img     = preview.querySelector( 'img' );
			var select  = field.querySelector( '.probo-callout-image-select' );
			var remove  = field.querySelector( '.probo-callout-image-remove' );
			var frame;

			select.addEventListener( 'click', function ( event ) {
				event.preventDefault();

				if ( frame ) {
					frame.open();
					return;
				}

				frame = wp.media( {
					title:    <?php echo wp_json_encode( __( 'Choose an image', 'probo-connect' ) ); ?>,
					multiple: false,
				} );

				frame.on( 'select', function () {
					var attachment = frame.state().get( 'selection' ).first().toJSON();

					input.value            = attachment.id;
					img.src                = ( attachment.sizes && attachment.sizes.thumbnail ) ? attachment.sizes.thumbnail.url : attachment.url;
					preview.style.display  = '';
					remove.style.display   = '';
				} );

				frame.open();
			} );

			remove.addEventListener( 'click', function ( event ) {
				event.preventDefault();
				input.value            = '';
				preview.style.display  = 'none';
				remove.style.display   = 'none';
			} );
		} );
	} )();
	</script>
	<?php
}

/**
 * Save every slot posted from the screen above.
 *
 * A slot with no title is dropped rather than stored empty, so the list a
 * category carries is always exactly the callouts it is actually using.
 */
function probo_callout_handle_save() {
	$term_id = isset( $_POST['term_id'] ) ? absint( $_POST['term_id'] ) : 0;

	if ( ! $term_id || ! current_user_can( probo_callout_capability() ) ) {
		wp_die( esc_html__( 'You do not have access to this action.', 'probo-connect' ) );
	}

	check_admin_referer( 'probo_callout_save_' . $term_id, 'probo_callout_nonce' );

	$posted = isset( $_POST['probo_callout'] ) && is_array( $_POST['probo_callout'] )
		? wp_unslash( $_POST['probo_callout'] ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized per field below.
		: array();

	$rows = array();

	foreach ( $posted as $posted_row ) {
		if ( ! is_array( $posted_row ) ) {
			continue;
		}

		$row = array();

		foreach ( probo_callout_fields() as $field => $config ) {
			$raw = isset( $posted_row[ $field ] ) ? $posted_row[ $field ] : '';

			if ( 'checkbox' === $config['type'] ) {
				// Stored either way: '0' is meaningful here, it is what tells the
				// getter this callout was deliberately switched off.
				$row[ $field ] = $raw ? '1' : '0';
				continue;
			}

			if ( 'textarea' === $config['type'] ) {
				$row[ $field ] = sanitize_textarea_field( $raw );
			} elseif ( 'image' === $config['type'] ) {
				$row[ $field ] = $raw ? (string) absint( $raw ) : '';
			} elseif ( 'select' === $config['type'] ) {
				$row[ $field ] = isset( $config['options'][ $raw ] ) ? $raw : '';
			} elseif ( 'url' === $field ) {
				$row[ $field ] = esc_url_raw( $raw );
			} elseif ( 'interval' === $field ) {
				$row[ $field ] = $raw ? (string) max( 1, (int) $raw ) : '';
			} else {
				$row[ $field ] = sanitize_text_field( $raw );
			}
		}

		if ( '' !== $row['title'] ) {
			$rows[] = $row;
		}
	}

	if ( $rows ) {
		update_term_meta( $term_id, probo_callouts_meta_key(), $rows );
	} else {
		delete_term_meta( $term_id, probo_callouts_meta_key() );
	}

	// The legacy single-callout fields are folded into row 0 above by
	// probo_category_callouts() only while 'probo_callouts' has never been
	// saved; now that it has, they would otherwise linger unused.
	foreach ( array_keys( probo_callout_fields() ) as $field ) {
		delete_term_meta( $term_id, probo_callout_legacy_meta_key( $field ) );
	}

	wp_safe_redirect(
		add_query_arg(
			array( 'post_type' => 'product', 'page' => 'probo-callouts', 'updated' => 1 ),
			admin_url( 'edit.php' )
		)
	);
	exit;
}
add_action( 'admin_post_probo_save_callout', 'probo_callout_handle_save' );
