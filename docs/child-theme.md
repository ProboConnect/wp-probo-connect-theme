# Extending Probo Connect — child themes and templates

How to change this theme without editing it. The theme updates through the Probo
Connect plugin (it pulls GitHub releases and overwrites `wp-content/themes/probo-connect`),
so **every direct edit to the parent theme is lost on the next update**. Everything
below is written so your work survives that.

Written in English to match `README.md` and the code comments; the Customizer
labels and the shipped copy are Dutch.

## Pick the smallest tool that does the job

| You want to… | Use |
| --- | --- |
| Change colours, radius, fonts, header/footer/hero style, checkout style, USP lines, footer texts | The Customizer — see [README → Customizing without code](../README.md#customizing-without-code). No code at all. |
| Change footer columns, shop filters, the nav account link | Widgets. See `probo_widgets_init()` in `functions.php` for the registered areas. |
| Add or restyle a category pitch | A **callout template** — see [Callout templates](#callout-templates). Designed for this; no child theme needed to add one, but a child theme is where yours should live. |
| Change markup of a page, a WooCommerce template or a header variant | A **child theme** that copies the file — see [Overriding templates](#overriding-templates). |
| Change behaviour, wiring or derived values | A child theme's `functions.php` or a small plugin, through the [filters and actions](#filters-and-actions). |
| Change a block's markup | Hardest case — blocks are registered from the parent. See [Blocks](#blocks). |

Behaviour changes that are not tied to this theme (a shipping tweak, a REST
endpoint) belong in a **plugin**, not in a child theme — they should survive a
theme switch.

## Creating the child theme

```
wp-content/themes/probo-connect-child/
├── style.css
└── functions.php   (only if you need PHP)
```

`style.css`:

```css
/*
Theme Name: Probo Connect — Child
Template: probo-connect
Version: 1.0.0
Text Domain: probo-connect-child
*/
```

`Template:` must be exactly `probo-connect` — the parent's directory name, which
is also what the release zip installs as.

### You do not need to enqueue the parent's CSS

`probo_enqueue_assets()` in the parent already does the right thing for a child:

```php
wp_enqueue_style( 'pp-theme', get_template_directory_uri() . '/assets/css/theme.css', … );
wp_enqueue_style( 'pp-style', get_stylesheet_uri(), array( 'pp-theme' ), PROBO_VERSION );
```

`get_stylesheet_uri()` resolves to **your child's `style.css`**, and it is loaded
after the parent's compiled stylesheet, with a hard dependency on it. So:

* Do **not** add the usual `wp_enqueue_style( 'parent-style', … )` boilerplate —
  it would load `theme.css` twice, and the second copy's static `:root` defaults
  would override the Customizer tokens (the same bug the comment in `probo_setup()`
  describes for the editor canvas).
* CSS written straight into your child's `style.css` already wins on order.
* The `--pp-*` tokens are printed as inline CSS on the `pp-theme` handle at
  priority 20, so they are in place before your stylesheet — you can read them.

Version-busting your own file is up to you; bump `Version:` in the header, since
`pp-style` is versioned with `PROBO_VERSION` from the parent.

## Overriding templates

Standard WordPress rules apply: a file at the same relative path in the child
theme replaces the parent's.

### Root and part templates

| File | Renders |
| --- | --- |
| `header.php` | doctype, skip link, checkout-header branch, then delegates to a header variant |
| `template-parts/header-ruim.php` | Variant A, the default three-row header |
| `template-parts/header-compact.php` | Variant B, opt-in via **Customizer → Header & footer** |
| `footer.php` | footer columns, widget areas, legal row |
| `front-page.php` | homepage; prints the page's blocks, falls back to `probo_homepage_blocks()` |
| `index.php` | blog index, archives, search |
| `page.php`, `single.php`, `404.php`, `comments.php`, `searchform.php` | as their names say |

`header.php` picks the variant with `get_template_part( 'template-parts/header', … )`,
which is stylesheet-aware — so copying only `template-parts/header-ruim.php` into
your child is enough to redraw the header. You rarely need to copy `header.php`
itself.

Copy the whole file, keep the `defined( 'ABSPATH' ) || exit;` guard, and keep the
`wp_head()` / `wp_body_open()` / `wp_footer()` calls if you copy `header.php` or
`footer.php`.

### WooCommerce templates

The parent ships these overrides under `woocommerce/`:

```
woocommerce/archive-product.php      woocommerce/cart/cart.php
woocommerce/single-product.php       woocommerce/cart/cart-totals.php
woocommerce/content-product.php      woocommerce/cart/cart-empty.php
woocommerce/checkout/form-checkout.php
woocommerce/checkout/review-order.php
woocommerce/checkout/payment.php
```

Lookup order is `child/woocommerce/…` → `parent/woocommerce/…` → the plugin's
own template. So a copy in your child theme wins.

Two rules when you copy one:

1. **Keep every `do_action()` and `apply_filters()` call.** They are what other
   plugins hook into; dropping one silently removes a payment gateway's markup or
   a fee row.
2. **Keep the `@version` header, and re-check it after a WooCommerce update.**
   ```bash
   npm run check:templates
   ```
   The script (`bin/check-templates.php`) compares the header of every file in
   the *parent's* `woocommerce/` against the installed plugin. It does not see
   your child theme — check your own copies by hand, or point the script at your
   directory.

The parent also overrides two Probo Connect **plugin** templates under
`probo-connect/checkout/sections/`. Those are handed back to the plugin when the
checkout style is "Eén pagina" — see `probo_restore_connect_templates()` in
`inc/woocommerce.php`. Your child theme can override the same paths; the same
filter still applies, so your copy is used only in the stepped checkout.

## Callout templates

Callouts are the theme's purpose-built extension point: a short pitch with a
button, stored on a **product category** and rendered by a template file. They
appear on that category's archive and as tiles in the Categorietegels block.

The whole set is discovered from disk:

```
{theme}/templates/callouts/{placement}/{name}.php
```

Nothing is registered. Dropping a file in makes it appear in the **Template**
picker on the product-category edit screen. The **child theme is scanned first**,
so a copy at the same path shadows the parent's file
(`probo_callout_template_roots()` in `inc/category-callout.php`).

Shipped:

| Path | Placement | Where it lands |
| --- | --- | --- |
| `templates/callouts/category_top/top.php` | `category_top` | category page, above the products |
| `templates/callouts/grid/callout.php` | `grid` | between the products, and in the Categorietegels block |
| `templates/callouts/category_bottom/bottom.php` | `category_bottom` | category page, below the products |

### Restyling one

Copy the file to the same path in your child theme and edit it. The picker keeps
showing the same option; only the markup changes.

### Adding one

Drop a new `.php` file into a placement directory in your child theme:

```php
<?php
/**
 * Callout Template: Wide banner with image left
 *
 * @var array $callout Title, text, image, cta, url, tone.
 */

defined( 'ABSPATH' ) || exit;

$image = ! empty( $callout['image'] ) ? wp_get_attachment_image_url( (int) $callout['image'], 'large' ) : '';
?>
<section class="rounded-pp <?php echo esc_attr( probo_callout_tone_classes( $callout['tone'] ?? '' ) ); ?>">
	<h2><?php echo esc_html( $callout['title'] ); ?></h2>
	<?php if ( ! empty( $callout['text'] ) ) : ?>
		<p><?php echo esc_html( $callout['text'] ); ?></p>
	<?php endif; ?>
	<?php if ( ! empty( $callout['cta'] ) && ! empty( $callout['url'] ) ) : ?>
		<a class="pp-btn-accent" href="<?php echo esc_url( $callout['url'] ); ?>"><?php echo esc_html( $callout['cta'] ); ?></a>
	<?php endif; ?>
</section>
```

Things to know:

* The `Callout Template:` file header is the label in the picker. Without one the
  filename is humanised (`wide-banner.php` → "Wide banner").
* The **directory is the placement**. A directory that is not one of the three
  known slugs still works — its name is humanised as the group label, or you can
  label it through the `probo_callout_placements` filter.
* The template is included with **only `$callout` in scope**
  (`probo_callout_include_template()`), so it cannot depend on a caller's local
  variables. Prefix any variable you create, as the shipped templates do
  (`$probo_image_url`).
* `$callout` is normalised: `title`, `text`, `image` (attachment id as string),
  `cta`, `url`, `tone`, `template`, `interval`. A row with an empty `title` is
  never rendered.
* Escape everything yourself — nothing is escaped for you.
* A grid slot has nowhere to put a full-width band, so when a callout's chosen
  template has the wrong placement the first template of the *required* placement
  is used instead. Ship a `grid/` template if you want control over that fallback.

## Blocks

The eight homepage blocks (`probo/hero`, `probo/usp-bar`, `probo/category-grid`,
`probo/bento-grid`, `probo/testimonials`, `probo/logo-reel`, `probo/bestsellers`,
`probo/how-it-works`) are registered in `probo_register_blocks()` from
`get_template_directory() . '/blocks/' . $name`. **A child theme copy of a
`render.php` is not picked up** — the path is the parent's, not the stylesheet's.

Options, cheapest first:

1. **Style it.** Most block changes are colour, spacing or radius, which are
   Customizer tokens or CSS in your child's `style.css`.
2. **Filter the output.** `render_block` gives you the finished HTML plus the
   parsed block:
   ```php
   add_filter( 'render_block', function ( $html, $block ) {
       return 'probo/usp-bar' === ( $block['blockName'] ?? '' ) ? my_usp_bar( $block['attrs'] ) : $html;
   }, 10, 2 );
   ```
3. **Re-register it.** On `init` at a priority after the parent's (default 10),
   `unregister_block_type( 'probo/hero' )` and register your own directory in its
   place. You then own the editor script and the attribute schema too — only do
   this when the markup really has to change.

Don't rename the block; existing page content stores `<!-- wp:probo/hero -->` and
would break.

## Filters and actions

Theme-owned hooks. All are safe to use from a child theme's `functions.php` or a
plugin.

### Design tokens and styling

| Hook | Type | Use |
| --- | --- | --- |
| `probo_tokens` | filter | Every `--pp-*` custom property, after derivation. Add your own token or override a derived one. `array $tokens, array $overrides` |
| `probo_layered_style_handles` | filter | Stylesheets rewritten into the `plugins` cascade layer. Add another plugin's handle so the theme's rules win by construction. |
| `probo_dropped_style_handles` | filter | WooCommerce sheets dequeued entirely (`woocommerce-layout`, `woocommerce-smallscreen`). |

### Catalogue and product cards

| Hook | Type | Use |
| --- | --- | --- |
| `probo_product_badge` | filter | The badge on a product card. `string $badge, WC_Product $product` |
| `probo_excluded_category_ids` | filter | Categories hidden from menus and category listings. |
| `probo_menu_products_per_category` | filter | Products shown per category in the mega menu. `int $count, WP_Term $term` |
| `probo_is_configurable_product` | filter | Whether a product is treated as a Probo configurable. `bool $is, WC_Product $product` |

### Callouts

| Hook | Type | Use |
| --- | --- | --- |
| `probo_category_callouts` | filter | A category's normalised rows. `array $callouts, WP_Term $term` |
| `probo_callout_templates` | filter | The discovered set — for removing or relabelling; add files on disk instead of here. |
| `probo_callout_template_roots` | filter | Extra directories to scan. Earlier entries shadow later ones: prepend to override, append as fallback. A plugin shipping callout templates hooks here. |
| `probo_callout_template_dir` | filter | The path below each root (`templates/callouts`). |
| `probo_callout_placements` | filter | Placement slug → label, for the picker's groups. |
| `probo_callout_max_slots` | filter | Callout slots per category (default 3). |
| `probo_callout_legacy_template_map` | filter | Old `band` / `tile` values → template paths. |

### Products per customer

| Hook | Type | Use |
| --- | --- | --- |
| `probo_customer_can_access_product` | filter | The final say on one customer and one product — where a purchase history, a contract, an ERP lookup or a rule of your own (per role, per group) goes. `bool $allowed, int $product_id, int $user_id` |
| `probo_product_access_manage_cap` | filter | The capability that bypasses every restriction (default `edit_products`). |
| `probo_product_access_denied_action` | filter | `login`, `shop`, or anything else for a 404, when a product is opened by someone it is not for. `string $action, int $product_id` |
| `probo_product_access_denied_message` | filter | The wording that refusal gets. |
| `probo_product_access_profile_limit` | filter | How many restricted products a customer's profile screen lists (default 200). |

The theme itself grants per customer and nothing else; a role or group rule is
that first filter's job.

Read the rules rather than the meta: `probo_customer_can_access_product()`,
`probo_product_is_restricted()`, `probo_product_access_users()` and
`probo_hidden_product_ids()` all take a product id, post or `WC_Product`, and a
variation resolves to its parent. Writing goes through
`probo_product_access_set_restricted()` and `probo_product_access_set_users()`,
which keep the cached list of restricted products honest.

### Login required

| Hook | Type | Use |
| --- | --- | --- |
| `probo_login_required_scope` | filter | The wall's height regardless of the Customizer: `off`, `checkout`, `cart` or `site`. |
| `probo_login_required_message` | filter | What a walled visitor is told. `string $message, string $stage` (`cart`, `checkout` or `site`) |
| `probo_login_required_public_request` | filter | Which requests a closed portal still answers when logged out. The account page and robots.txt already do; this is where a public contact or privacy page is added. |
| `probo_login_required_is_staff` | filter | Who keeps wp-admin and the toolbar in a closed portal. `bool $staff, int $user_id` — `edit_posts` or `manage_woocommerce` by default. |

`probo_login_required_for( 'cart' | 'checkout' )` answers whether this visitor
still has to log in, `probo_login_required_site_closed()` whether the whole site
is shut to them, `probo_login_required_portal()` whether the shop is running as
a closed portal at all (whoever is asking), and
`probo_login_required_url( $return_to )` builds the login link that comes back
to where they were.

### Checkout

| Hook | Type | Use |
| --- | --- | --- |
| `probo_checkout_is_stepped` | filter | Force the stepped or one-page checkout regardless of the Customizer. |
| `probo_checkout_pickup_instance_ids` | filter | Shipping instances treated as pick-up. |
| `probo_checkout_address_summary_extra` | filter | Extra lines in a step's collapsed address summary. |
| `probo_checkout_shipping_selector` | **action** | Where the plugin's shipping selector is re-rendered inside the checkout layout. |
| `probo_configurator_band` | **action** | Where the configurator is re-rendered on the single-product page. |

The last two exist because `probo_move_configurator()` and
`probo_move_shipping_selector()` unhook the plugin's callbacks from the stock
WooCommerce actions and re-attach them here. If you need to add something beside
the configurator, hook `probo_configurator_band` — hooking
`woocommerce_single_product_summary` puts it where the configurator no longer is.

## Template tags

Public helpers in `inc/template-tags.php` you can call from your own templates:
`probo_logo()`, `probo_search_form()`, `probo_breadcrumb()`, `probo_product_card()`,
`probo_product_spec_line()`, `probo_product_badge()`, `probo_configure_cta()`,
`probo_placeholder()`, `probo_callout_tone_classes()`, `probo_menu_products()`,
`probo_cart_count()`, `probo_cart_url()`, `probo_account_url()`.
Plus `probo_get( 'setting_key' )` for any Customizer value (defaults in
`probo_defaults()`, `inc/settings.php`) and `probo_tokens()` for the derived
design tokens.

**None of them are pluggable** — there is no `function_exists()` wrapper, so
redeclaring one in a child theme is a fatal error. Change behaviour through the
filters above, or write your own function under your own prefix.

## Naming: nothing vendor-named reaches the browser

The theme keeps two prefixes apart, deliberately:

| Prefix | Where | Example |
| --- | --- | --- |
| `probo_` / `Probo_` | **PHP only** — functions, hooks, theme mods, text domain | `probo_get()`, `probo_tokens` |
| `pp-` / `--pp-` / `pp_` | **anything the browser sees** — classes, ids, `data-` attributes, CSS custom properties, stylesheet and script handles, form field names, storage keys | `pp-card`, `#pp-configurator`, `data-pp-step`, `--pp-accent`, `pp-theme` |

So the rendered HTML, the served CSS and the served JS carry no vendor name. The
blocks are registered as `probo/hero` and friends, but they declare
`"className": false`, so no `wp-block-probo-*` class is printed either, and the
block delimiters live in post content as comments the parser consumes.

Keep this up in a child theme: `probo_`-prefixed PHP is off limits anyway (see
[Template tags](#template-tags)), and anything you put in markup, CSS or JS
should use `pp-` or your own neutral prefix.

Two things are still vendor-named for reasons outside the theme's control, and
are worth knowing before someone reports them as a leak:

* **Asset URLs** contain the theme directory — `/wp-content/themes/probo-connect/…`.
  The directory name is what the release zip and the plugin's updater install as,
  so it cannot be changed from inside the theme.
* **`style.css`'s theme header** is fetched by the browser and carries the theme
  name, author and text domain. WordPress requires that header.

A child theme with its own directory name and its own `style.css` header
sidesteps both for everything it serves itself.

## Styling in a child theme

Your own CSS goes in the child's `style.css` — it is already enqueued after the
parent's stylesheet, so it wins on order without any extra code.

Write it against the tokens rather than against fixed values, so the Customizer
keeps working:

```css
.my-banner {
  background: var(--pp-secondary);
  color: var(--pp-secondary-fg);
  border-radius: var(--pp-radius);
  font-family: var(--pp-font-title), system-ui, sans-serif;
}
```

The full set of `--pp-*` properties is printed on `:root` by
`inc/dynamic-css.php`; `assets/css/src/theme.css` lists the static defaults with
a comment per group. `probo_tokens()` returns the same values in PHP.

The parent's own classes (`pp-card`, `pp-btn-accent`, `pp-container`,
`pp-eyebrow`, …) are available to reuse in your markup — they already carry the
design system's spacing, radius and colour guards.

### Three rules inherited from the parent

They are explained in [README → Three rules that keep this maintainable](../README.md#three-rules-that-keep-this-maintainable),
and they apply to your child theme too:

1. Plugin CSS lives in the lower `plugins` cascade layer. Never fight it with
   specificity or `!important` — if a plugin rule wins, its handle is missing
   from `probo_layered_style_handles`, and that is the bug.
2. Every colour goes through a token, never a hex in a template. Use the guarded
   pairs — `--pp-accent` only ever together with `--pp-accent-fg` on top of it,
   `--pp-accent-ink` for accent *as text* on white, and the panel-specific
   `--pp-footer-accent`, `--pp-bar-accent`, `--pp-hero-accent` inside the footer,
   the top bar and the hero. They are what keeps a light accent readable.
3. WooCommerce overrides are versioned — including yours.

## Checklist before you ship a child theme

* [ ] `Template: probo-connect` in `style.css`, spelled exactly.
* [ ] No `wp_enqueue_style()` for the parent's `theme.css` — it is already loaded,
      and your `style.css` already depends on it.
* [ ] No redeclared `probo_*` function.
* [ ] Copied WooCommerce templates keep every hook and their `@version`.
* [ ] No hardcoded hex where a `--pp-*` token exists.
* [ ] No vendor name in anything the browser sees — classes, ids, `data-`
      attributes, handles, JS. Use `pp-` or your own prefix.
* [ ] Tested with both header variants, both checkout styles, and a light accent
      colour — the contrast guards are the first thing custom markup breaks.
