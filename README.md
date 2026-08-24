# Probo Connect — WordPress theme

Classic WordPress theme for a print-on-demand shop running WooCommerce and the
Probo Connect plugin. Built from the Claude Design handoff in `../project/`.

## Install

Copy (or symlink) this folder into `wp-content/themes/probo-connect` and activate
it. WooCommerce is optional for the theme to load, but the shop templates only
do anything with it active.

The compiled CSS is committed, so the theme works without running npm.

## Customizing without code

Everything the design session iterated on is a Customizer control, under
**Weergave → Customizer → Thema-instellingen**:

| Section | What it does |
| --- | --- |
| Merk | Accent colour, secondary colour, corner radius |
| Typografie | Title font and body font (specs and prices stay IBM Plex Mono by design) |
| Header & footer | Top-bar style and optional own colour, footer style, light logo, the three USP lines, the checkout phone number, footer texts |
| Hero | Hero style and title colour (defaults for the hero block) |
| Componenten | Card style: Rand / Schaduw / Vlak, and the checkout style — see [Checkout](#checkout) |

Two behaviours are deliberate and worth knowing about:

* **Secondary drives every dark surface** — hero, top bar, footer, cart button,
  price bars, summary frames. Text and rules flip to whatever contrasts, so a
  pale secondary does not produce white-on-white.
* **A hero title colour too close to the hero background is ignored.** The title
  falls back to the contrasting colour rather than disappearing.

### Logo

Upload under **Customizer → Site-identiteit → Logo**; it is reused in the
header, the footer and the checkout header. WordPress core stores only one logo
and has no light/dark variant, so **Header & footer → Logo (licht)** adds an
optional second image for dark backgrounds. Leave it empty to use the main logo
everywhere. With no logo at all, the theme renders the site title as a text
lockup, with everything from the first dot onwards in the accent colour.

### Menus and widgets

* Menus: `Hoofdnavigatie`, `Bovenbalk (rechts)`, `Footer — juridisch`. A
  `Hoofdnavigatie` item with children opens as a full-width flyout panel from
  1024px up, and as an indented list inside the burger below that. Hover and
  focus open it in CSS alone; the script adds Escape-to-close and a first tap
  that opens rather than follows the link on touch. With no menu assigned, the
  fallback builds one from the product categories, subcategories included.
* Widget areas: three footer columns, plus `Shop filters` (the category page's
  filter column — drop WooCommerce's attribute and price filter widgets here)
  and `Shop tipblok` (the "twijfel je over de maat?" card).

## Homepage

The homepage is composed of blocks, not hardcoded, so sections can be
reordered, removed or reused on other pages:

`probo/hero`, `probo/usp-bar`, `probo/category-grid`, `probo/bento-grid`,
`probo/testimonials`, `probo/logo-reel`, `probo/bestsellers`,
`probo/how-it-works` — all in the **Probo Connect** inserter category.

A few of them carry options worth knowing about:

* **Hero** takes an optional gradient over its image — from the bottom, from the
  left, or as a vignette — mixed from the hero's own background colour, so it
  follows the Customizer instead of baking in a black scrim.
* **Categorietegels** shows two kinds of callout. A category's own — filled in
  under **Producten → Categorieën → bewerken**, section *Callout* — follows that
  category into every grid that lists it, as a tile right behind it, and also
  appears as a band at the top of that category's archive page. On top of that
  the block can carry one callout of its own, for a pitch that belongs to no
  category — placed at the start, at the end, or repeated after every N tiles.
  Both draw through `probo_callout_tile()` so they cannot drift apart. A
  category's callout has its own on/off checkbox, so the text can stay while the
  callout is switched off; a category saved before that checkbox existed falls
  back to "a title means it is on".
* **Bento-grid** and **Logobalk** store their images as attachment ids, so they
  are picked from the media library rather than typed. Bento tiles choose their
  own size (normal, wide, tall, large) within a four-column grid.
* **Klantquotes** uses the same line format as the USP bar, four fields:
  quote, name, company, score out of five.

They are dynamic blocks previewed with ServerSideRender, so the editor canvas
shows exactly what the front end renders — and nothing is editable in the canvas
itself: every setting lives in the block sidebar. The `Probo Connect homepage`
pattern composes the original five in order, and a fresh install gets it as
starter content.

Repeating content (the USP lines, the steps, the quotes) is **stored** as one
line per item in the form `Titel | Toelichting`, which `probo_parse_lines()`
reads. It is **edited** through `blocks/shared/repeater.js`: per-field inputs
with add, remove and reorder. One control, shared by all three — point a fourth
block at it by adding `pp-block-repeater` to its `index.asset.php`
dependencies and calling `probo.repeater()` with one `fields` entry per column.

## Probo Connect

The theme **styles** the plugin's configurator; it does not reimplement it.
`single-product.php` moves WooCommerce's add-to-cart area into the grey
configurator band and wraps it in `.pp-configurator`, which is what
`assets/css/print-connect.css` hangs its skin off.

That stylesheet has since been retargeted against the plugin's real output. What
matters if you touch it:

* `<connect-product-configurator>` **uses shadow DOM**. No descendant selector
  reaches inside it. Two channels do: inherited custom properties (the token
  block on the wrapper) and `::part()` — and the component exposes exactly one
  part, `configurator`.
* Everything around it is light DOM with `connect-`-prefixed classes, styled by
  their real names: `connect-configurator-summary`, `-columns`, `-column`,
  `-card`, `-footer`, plus `connect-options-list`, `connect-delivery-moment`,
  `connect-price-*`, `connect-filespecs-*` and `#connect-addtocart`.
* Match those names exactly, never a substring. An earlier `[class*="options"]`
  guess also caught `connect-options-list` and turned the summary's spec rows
  into four squeezed columns.

The configurator itself is rendered in the theme's own band: the plugin hangs it
on `woocommerce_single_product_summary` (priority 25), and
`probo_move_configurator()` in `inc/woocommerce.php` moves that callback to
`probo_configurator_band`, which `single-product.php` fires inside the grey
band. The hook it is taken off comes from the plugin's own
`probo_configurator_render_action` option, so a shop that repointed it keeps
working.

The cart line has no upload button of the theme's own: Probo Connect prints the
real one on `woocommerce_after_cart_item_name`.

## Checkout

Two layouts, chosen under **Customizer → Thema-instellingen → Componenten →
Checkout-stijl**. The setting is opt-in: a shop that upgrades keeps the checkout
it had.

| Value | What it is |
| --- | --- |
| **Eén pagina** (default) | The classic layout: five numbered sections under each other, order button under "Te betalen" in the summary column. |
| **Stappen** | The accordion: 1 Gegevens & adres, 2 Bezorging, 3 Betalen. One step open, the rest collapsed to a summary line with "Wijzig". |

Everything below describes the **Stappen** layout. Switching back to **Eén
pagina** restores the previous behaviour everywhere — the template, the header
and footer, the carrier list, the plugin's own delivery templates and the order
button's label all follow the same switch, through
`probo_checkout_is_stepped()` in `inc/woocommerce.php` (filterable as
`probo_checkout_is_stepped`).

### What changes

* **One step open.** A logged-in customer with a complete profile arrives on
  step 2, because that is the first thing they still have to answer.
  `probo_checkout_initial_step()` decides that server-side.
* **Bezorging is one decision, not two.** The plugin asks for a day and then for
  a carrier; the step asks *Snelste*, *Voordeligste* or *Kies zelf*. A preset is
  a day **and** a carrier at once — the first available day with its cheapest
  carrier, or the lowest day total with the carrier that makes it so — so
  picking one hands the pair to `window.connectShippingBlocks.set()` + `.store()`
  rather than to a radio. "Kies zelf" reveals the full day list and the full
  carrier list; that reveal is a `:has(:checked)` rule, not script.
* **"Kies zelf" is a request, not a selection.** It changes nothing the server
  stores, so nothing on the server can remember it: every re-render of the block
  would hand back the matching preset and fold the carrier list away under the
  customer. `checkout-steps.js` keeps the flag and re-checks the radio on
  `section:updated` until a preset answers for them again.
* **A preset is never a claim.** It is only rendered checked when the pair the
  plugin has stored *is* that preset's pair, which the template reads back from
  `$date['selected']` and `$method['selected']`. Anything the presets do not
  cover — a later day, another carrier, a pickup point — renders as "Kies zelf"
  with the lists open on the actual choice.
* **Ophalen is a tab**, not six rows at the bottom of the carrier list. The tabs
  are two radios and a `:has(:checked)` rule, so they work without JavaScript.
* **The order button moves to step 3**, with the amount in its label
  (`Bestelling plaatsen · € 216,28`), so nothing can be ordered on a half-made
  delivery choice.
* **The page loses its chrome**: no search, navigation, account links or footer
  columns on the checkout — `header.php` and `footer.php` branch on
  `probo_is_checkout_flow()`. The header instead carries the logo, the three
  step names and the phone number from **Header & footer → Telefoonnummer op de
  checkout**.

### How it holds together

* **The accordion is progressive enhancement.** `form-checkout.php` renders every
  step open; `assets/js/checkout-steps.js` adds `.is-enhanced` and closes the
  rest. Without the script the checkout is the long form it always was.
* **Summary lines are built in PHP**, not in JavaScript
  (`probo_checkout_step_summary()`), so they are also right after a non-JS
  refresh and after a server-side validation round. They refresh over AJAX as
  `.pp-summary-1/2/3` fragments.
* **The button label is not a fragment.** WooCommerce's own checkout script
  resets `#place_order` to its `data-value` every time it initialises the payment
  methods, which eats any element nested in the button — so the amount is part of
  the label, and the script rewrites both on `updated_checkout`.
* **The delivery data stays the plugin's.** Two of its section templates are
  overridden in `probo-connect/checkout/sections/` — the same
  `wc_get_template()` mechanism the theme uses for WooCommerce's templates. The
  radios, their names, their values and every price still come from Probo
  Connect; "cheapest" is computed from the `shipping_methods` it already passes
  in (its price plus that day's `rush_surcharge`), never recalculated. The step-2
  summary reads the plugin's own session values (`Probo_Meta_Keys::SHIPPING_*`).
* **Pickup is split on method id**, never on the word "afhalen" in a label —
  `probo_checkout_pickup_instance_ids()`, filterable. That only applies to the
  WooCommerce-rates fallback; with the plugin active its own `is_pickup` flag is
  the authority.

Two places where the built checkout differs from the design prototype, both on
purpose:

* The carrier is not a fold under one visible row. Since the presets answer the
  carrier question in the normal case, the list only appears once someone asked
  to choose one — and then folding four of the five away is the wrong answer.
  Pickup keeps its fold (three points, then the rest); it has no preset, so it
  is always the list you are being asked about.
* The "kies zelf" day list is a vertical list, not a horizontally scrolling date
  strip on mobile. Full-width rows carry the day, carrier and price that make the
  days comparable in the first place; a strip cannot.

## Working on the styles

```bash
npm install
npm run dev     # watch
npm run build   # minified, writes assets/css/theme.css
```

Tailwind v4. The theme's colours are mapped with `@theme inline` onto the
`--pp-*` custom properties that `inc/dynamic-css.php` prints from the Customizer,
so `bg-accent` follows the accent picker at runtime rather than baking in a hex.

Class names in PHP must be **literal strings** — Tailwind scans the source and a
class assembled by concatenation will not survive the build.

`assets/css/print-connect.css` is plain CSS and is not part of the build, so it
can be edited directly against a live configurator.

### Three rules that keep this maintainable

Most of the odd bugs in this theme came from the same three places. Each one now
has a single mechanism, so the fix is always in one spot rather than sprinkled
across templates.

**1. Plugin CSS lives in a lower cascade layer. Never fight it with specificity.**

WooCommerce ships its stylesheets unlayered, and unlayered CSS beats *any*
layered rule regardless of specificity — which is why the plugin's purple
buttons, green prices and square thumbnails kept winning. `probo_layer_plugin_styles()`
in `inc/woocommerce.php` rewrites those `<link>` tags into
`@import url(…) layer(plugins)`, and `assets/css/src/theme.css` declares
`@layer plugins;` before Tailwind's own layers. Theme rules therefore win by
construction.

* Another plugin doing the same thing? Add its handle:
  `add_filter( 'probo_layered_style_handles', fn( $h ) => [ ...$h, 'their-handle' ] );`
* Never reach for `!important` or `body.woocommerce div.product …` chains. If a
  plugin rule is winning, its sheet is not in the layer yet — that is the bug.

**2. Every colour decision goes through a token, never a hex in a template.**

`inc/dynamic-css.php` derives all `--pp-*` properties from the Customizer and
`inc/color.php` holds the contrast maths. Templates only ever use the mapped
utilities. The guarded pairs, and when to use which:

| Use | Where |
| --- | --- |
| `bg-accent` + `text-accent-fg` | filled accent surfaces (buttons, badges) |
| `text-accent-ink` | accent *as text* on white or near-white |
| `text-footer-accent` | anything accent-coloured inside the footer |
| `bg-hero-accent` + `text-hero-accent-fg` | the hero's eyebrow chip |
| `text-bar-accent` | accent inside the top bar |

`probo_readable_accent()` is what makes those safe: when accent and background
sit within 0.25 luminance of each other, it falls back to the panel's own
foreground. Adding a new surface the shop can recolour? Derive a `…-accent`
token for it in the same way instead of using `text-accent` and hoping.

**3. WooCommerce template overrides are versioned. Check them after a WC update.**

```bash
npm run check:templates
```

Compares the `@version` header of every file in `woocommerce/` against the
installed WooCommerce and prints what drifted — the same thing WooCommerce →
Status → Templates reports, but before the shop owner sees the warning. When one
is flagged: diff the plugin's current template against ours, port anything new
(usually a hook or a filter), and bump the header.

## Releasing

Shop owners install and update this theme through the companion Probo
Connect plugin (`Probo_Theme_Installer`), not the WordPress.org theme
directory — the plugin reads GitHub releases directly. To cut one:

1. Bump `Version` in `style.css`.
2. Run `npm run build` and commit the resulting `assets/css/theme.css`.
3. Tag and push: `git tag vX.Y.Z && git push origin vX.Y.Z`.

`.github/workflows/release.yml` then builds the theme, checks the tag
against `style.css`'s `Version` header, and publishes a GitHub release
with a `probo-connect.zip` asset — root folder named `probo-connect`, the
theme's stylesheet slug, which is what WordPress installs it as. The
plugin's update check expects exactly that: one asset, that folder name,
on `releases/latest`.

## Not included

* Product photography and the logo file — placeholders throughout, as in the
  prototype.
* `screenshot.png` — add a 1200×900 render of the homepage before shipping the
  theme anywhere it will be browsed in the theme picker.
