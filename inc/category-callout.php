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
	return max( 1, (int) apply_filters( 'probo_callout_max_slots', 3 ) );
}

/* ---------------------------------------------------------------------------
   Templates.

   A callout is always drawn by a template file, never by a PHP callback, and
   the set of templates is whatever is on disk under

       templates/callouts/{placement}/{name}.php

   The directory a template sits in is its placement — where the callout lands
   on the page. Nothing has to be registered: dropping a file in makes it appear
   in the "Template" picker, and a child theme's copy of a path shadows the
   parent's, exactly as template overrides work everywhere else in WordPress.

   A template names itself with a `Callout Template:` file header; without one
   the filename is humanised.
--------------------------------------------------------------------------- */

/**
 * The directories scanned for callout templates, most specific first.
 *
 * The child theme comes first so its copy of a path wins over the parent's.
 *
 * @return string[] Absolute directory paths, without a trailing slash.
 */
function probo_callout_template_roots() {
	$roots = array( get_stylesheet_directory() );

	if ( get_template_directory() !== get_stylesheet_directory() ) {
		$roots[] = get_template_directory();
	}

	/**
	 * Filters the roots searched for callout templates.
	 *
	 * A plugin that ships callout templates adds its own directory here; earlier
	 * entries shadow later ones, so anything added after the theme's roots is a
	 * fallback and anything added before is an override.
	 *
	 * @param string[] $roots Absolute directory paths.
	 */
	$roots = (array) apply_filters( 'probo_callout_template_roots', $roots );

	return array_values( array_unique( array_filter( array_map( 'untrailingslashit', $roots ) ) ) );
}

/**
 * The path, below a root, that holds the callout templates.
 *
 * @return string
 */
function probo_callout_template_dir() {
	/**
	 * Filters the directory callout templates are discovered in.
	 *
	 * @param string $dir Path relative to each root, no slashes at either end.
	 */
	return trim( (string) apply_filters( 'probo_callout_template_dir', 'templates/callouts' ), '/' );
}

/**
 * Human labels for the placements the theme ships.
 *
 * A placement discovered on disk that is not listed here still works — its
 * directory name is humanised instead — so a custom placement needs no
 * registration either.
 *
 * @return array<string, string>
 */
function probo_callout_placements() {
	/**
	 * Filters the placement labels.
	 *
	 * @param array<string, string> $placements Label per placement slug.
	 */
	return (array) apply_filters(
		'probo_callout_placements',
		array(
			'category_top'    => __( 'Category page — above the products', 'probo-connect' ),
			'grid'            => __( 'Between the products / in a tile grid', 'probo-connect' ),
			'category_bottom' => __( 'Category page — below the products', 'probo-connect' ),
		)
	);
}

/**
 * The label for one placement.
 *
 * @param string $placement Placement slug (the directory name).
 * @return string
 */
function probo_callout_placement_label( $placement ) {
	$placements = probo_callout_placements();

	if ( isset( $placements[ $placement ] ) ) {
		return $placements[ $placement ];
	}

	return ucfirst( str_replace( array( '-', '_' ), ' ', $placement ) );
}

/**
 * Turn one template file into its label.
 *
 * @param string $file Absolute path to the template.
 * @param string $name Filename without the extension.
 * @return string
 */
function probo_callout_template_label( $file, $name ) {
	$headers = get_file_data( $file, array( 'label' => 'Callout Template' ) );

	if ( ! empty( $headers['label'] ) ) {
		return $headers['label'];
	}

	return ucfirst( str_replace( array( '-', '_' ), ' ', $name ) );
}

/**
 * Every callout template on disk, keyed by "{placement}/{name}".
 *
 * @return array<string, array{label: string, placement: string, file: string}>
 */
function probo_callout_discover_templates() {
	$dir       = probo_callout_template_dir();
	$templates = array();

	foreach ( probo_callout_template_roots() as $root ) {
		$base = $root . '/' . $dir;

		if ( ! is_dir( $base ) ) {
			continue;
		}

		foreach ( (array) glob( $base . '/*', GLOB_ONLYDIR ) as $placement_dir ) {
			$placement = basename( $placement_dir );

			foreach ( (array) glob( $placement_dir . '/*.php' ) as $file ) {
				$name = basename( $file, '.php' );
				$key  = $placement . '/' . $name;

				// First root to offer a path owns it — that is what makes a child
				// theme's copy shadow the parent's.
				if ( isset( $templates[ $key ] ) ) {
					continue;
				}

				$templates[ $key ] = array(
					'label'     => probo_callout_template_label( $file, $name ),
					'placement' => $placement,
					'file'      => $file,
				);
			}
		}
	}

	uasort(
		$templates,
		static function ( $a, $b ) {
			return array( $a['placement'], $a['label'] ) <=> array( $b['placement'], $b['label'] );
		}
	);

	return $templates;
}

/**
 * The templates a callout can render as.
 *
 * Discovery is done once per request — a handful of globs, but they run on
 * every category archive and every tile grid, so the result is held.
 *
 * @return array<string, array{label: string, placement: string, file: string}>
 */
function probo_callout_templates() {
	static $discovered = null;

	if ( null === $discovered ) {
		$discovered = probo_callout_discover_templates();
	}

	/**
	 * Filters the callout templates a category can choose from.
	 *
	 * Discovery already covers a theme, a child theme and — through
	 * probo_callout_template_roots — a plugin's own directory, so this is the
	 * seam for removing or relabelling one rather than for adding files.
	 *
	 * @param array $templates Templates, keyed by "{placement}/{name}".
	 */
	return (array) apply_filters( 'probo_callout_templates', $discovered );
}

/**
 * The template picker's options, grouped by placement.
 *
 * @return array<string, array<string, string>> Labels keyed by template, per placement label.
 */
function probo_callout_template_options() {
	$options = array();

	foreach ( probo_callout_templates() as $key => $template ) {
		$options[ probo_callout_placement_label( $template['placement'] ) ][ $key ] = $template['label'];
	}

	return $options;
}

/**
 * Old template values, and what they are called now.
 *
 * Categories saved before templates were files carry 'band' or 'tile'. They are
 * translated on read rather than migrated, so downgrading the theme does not
 * strip a shop of its callouts.
 *
 * @return array<string, string>
 */
function probo_callout_legacy_template_map() {
	/**
	 * Filters the mapping from pre-discovery template slugs to template paths.
	 *
	 * @param array<string, string> $map Old slug => "{placement}/{name}".
	 */
	return (array) apply_filters(
		'probo_callout_legacy_template_map',
		array(
			'band' => 'category_top/top',
			'tile' => 'grid/callout',
		)
	);
}

/**
 * Translate a row's stored template value to a template that exists today.
 *
 * Used on the way in on both paths — the front end's normalisation and the edit
 * screen — because the picker only offers real template paths. A legacy 'tile'
 * left untranslated matches no option there, so the browser would quietly select
 * the first entry in the list and the next save would write that instead.
 *
 * @param string $template Stored template value.
 * @return string Template key, or '' when nothing matches.
 */
function probo_callout_resolve_template_key( $template ) {
	$template = (string) $template;
	$legacy   = probo_callout_legacy_template_map();

	if ( isset( $legacy[ $template ] ) ) {
		$template = $legacy[ $template ];
	}

	$templates = probo_callout_templates();

	if ( isset( $templates[ $template ] ) ) {
		return $template;
	}

	$keys = array_keys( $templates );

	return isset( $keys[0] ) ? $keys[0] : '';
}

/**
 * The template a callout should be drawn with.
 *
 * @param array  $callout   Normalised callout row.
 * @param string $placement Force a placement — the caller has only one kind of
 *                          slot to give, whatever the callout would prefer.
 * @return array{label: string, placement: string, file: string}|null
 */
function probo_callout_locate_template( $callout, $placement = '' ) {
	$templates = probo_callout_templates();

	if ( ! $templates ) {
		return null;
	}

	$key      = isset( $callout['template'] ) ? (string) $callout['template'] : '';
	$template = isset( $templates[ $key ] ) ? $templates[ $key ] : null;

	if ( ! $placement ) {
		return $template ? $template : reset( $templates );
	}

	// A forced placement wins: a grid has nowhere to put a full-width band, so
	// the callout is drawn with that placement's own template instead.
	if ( $template && $template['placement'] === $placement ) {
		return $template;
	}

	foreach ( $templates as $candidate ) {
		if ( $candidate['placement'] === $placement ) {
			return $candidate;
		}
	}

	return null;
}

/**
 * Where a callout's chosen template puts it.
 *
 * @param array $callout Normalised callout row.
 * @return string Placement slug, or '' when there is no template to draw it.
 */
function probo_callout_placement( $callout ) {
	$template = probo_callout_locate_template( $callout );

	return $template ? $template['placement'] : '';
}

/**
 * Include a template with only $callout in scope.
 *
 * A plain include would hand the template every local of the function that
 * called it, which is how a template quietly comes to depend on a caller's
 * variable and breaks the moment it is used somewhere else.
 *
 * @param string $file    Absolute path to the template.
 * @param array  $callout Normalised callout row.
 */
function probo_callout_include_template( $file, $callout ) {
	$include = static function ( $probo_callout_template, $callout ) {
		include $probo_callout_template;
	};

	$include( $file, $callout );
}

/**
 * Render one callout through its template.
 *
 * @param array  $callout   Normalised callout row.
 * @param string $placement Force a placement; see probo_callout_locate_template().
 */
function probo_callout_render( $callout, $placement = '' ) {
	if ( empty( $callout['title'] ) ) {
		return;
	}

	$template = probo_callout_locate_template( $callout, $placement );

	if ( ! $template || ! is_readable( $template['file'] ) ) {
		return;
	}

	probo_callout_include_template( $template['file'], $callout );
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
			'options' => probo_callout_template_options(),
			'help'    => __( 'The template decides where the callout lands. Want the same pitch in two places? Create two callouts, one per template. In the Category Tiles block every callout is drawn with a tile template, whatever it uses on the category page.', 'probo-connect' ),
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

	// Rows saved while templates were PHP callbacks carry 'band' or 'tile'; a
	// template that has since been renamed or removed falls back to the first
	// one discovered, because the callout still has to draw as something.
	$row['template'] = probo_callout_resolve_template_key( $row['template'] );

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
			// An array value is a group of options rather than one option, which
			// is how the template picker sorts its entries by placement.
			if ( is_array( $label ) ) {
				printf( '<optgroup label="%s">', esc_attr( $option ) );

				foreach ( $label as $grouped => $grouped_label ) {
					printf(
						'<option value="%s"%s>%s</option>',
						esc_attr( $grouped ),
						selected( $value, $grouped, false ),
						esc_html( $grouped_label )
					);
				}

				echo '</optgroup>';
				continue;
			}

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
 * The values a select field will accept, groups flattened out.
 *
 * @param array $config Field configuration.
 * @return string[]
 */
function probo_callout_field_option_values( $config ) {
	$values = array();

	foreach ( (array) ( $config['options'] ?? array() ) as $option => $label ) {
		if ( is_array( $label ) ) {
			$values = array_merge( $values, array_keys( $label ) );
			continue;
		}

		$values[] = $option;
	}

	return array_map( 'strval', $values );
}

/**
 * The capability that guards callout editing.
 *
 * @return string
 */
function probo_callout_capability() {
	$tax = get_taxonomy( 'product_cat' );

	return $tax ? $tax->cap->edit_terms : 'manage_categories';
}

/**
 * The media library, for the callouts' image fields.
 *
 * Callouts now live on the product category's own edit screen (term.php), so the
 * picker is loaded there rather than on a page of their own.
 *
 * @param string $hook Current admin page hook.
 */
function probo_callout_enqueue_media( $hook ) {
	if ( 'term.php' !== $hook ) {
		return;
	}

	$taxonomy = isset( $_GET['taxonomy'] ) ? sanitize_key( wp_unslash( $_GET['taxonomy'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only screen check.

	if ( 'product_cat' !== $taxonomy ) {
		return;
	}

	wp_enqueue_media();
}
add_action( 'admin_enqueue_scripts', 'probo_callout_enqueue_media' );

/**
 * Render the callout slots on the product category's own edit screen.
 *
 * Callouts are theme content stored on the term, so they belong next to the
 * category they describe rather than on a page of their own. The whole set of
 * slots sits in a single form-table row on WooCommerce's edit-category form;
 * because the inputs are inside that form, they post and save with the category.
 *
 * @param WP_Term $term     Product category being edited.
 * @param string  $taxonomy Taxonomy name (always 'product_cat' here).
 */
function probo_callout_edit_fields( $term, $taxonomy = 'product_cat' ) {
	if ( ! $term instanceof WP_Term || ! current_user_can( probo_callout_capability() ) ) {
		return;
	}

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

		// The picker only offers template paths that exist now, so a stored
		// legacy value has to be translated before it is matched against them.
		if ( ! empty( $probo_row['template'] ) ) {
			$rows[ $probo_row_index ]['template'] = probo_callout_resolve_template_key( $probo_row['template'] );
		}
	}
	?>
	<tr class="form-field probo-callouts-field">
		<th scope="row" valign="top">
			<label><?php esc_html_e( 'Callouts', 'probo-connect' ); ?></label>
		</th>
		<td>
			<p class="description" style="margin-top:0;">
				<?php
				printf(
					/* translators: %d: number of slots. */
					esc_html__( 'A callout is a small block with a button next to this category — on the category page and as a tile in the Category Tiles block. Up to %d per category; leave the title empty to skip a block.', 'probo-connect' ),
					(int) $slots
				);
				?>
			</p>

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
		</td>
	</tr>
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
add_action( 'product_cat_edit_form_fields', 'probo_callout_edit_fields', 20, 2 );

/**
 * Save every slot posted with the category, when its edit form is submitted.
 *
 * Runs on the term-save hook rather than on a page of its own, so the callouts
 * are written as part of the ordinary "Update" on the category. A slot with no
 * title is dropped rather than stored empty, so the list a category carries is
 * always exactly the callouts it is actually using.
 *
 * The 'probo_callout' key is only present when this theme's fields were on the
 * submitted form, so a Quick Edit or a programmatic term update leaves the
 * stored callouts untouched instead of wiping them.
 *
 * @param int $term_id Product category id being saved.
 */
function probo_callout_save_term( $term_id ) {
	if ( ! isset( $_POST['probo_callout'] ) || ! current_user_can( probo_callout_capability() ) || ! current_user_can( 'edit_term', $term_id ) ) {
		return;
	}

	check_admin_referer( 'probo_callout_save_' . $term_id, 'probo_callout_nonce' );

	$posted = is_array( $_POST['probo_callout'] )
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
				$row[ $field ] = in_array( (string) $raw, probo_callout_field_option_values( $config ), true ) ? (string) $raw : '';
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
}
add_action( 'edited_product_cat', 'probo_callout_save_term' );
