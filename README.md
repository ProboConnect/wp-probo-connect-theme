# Probo Connect — Free WordPress theme

This theme is free to use and acts as a kick starter for yout a print-on-demand 
shop running WooCommerce and the Probo Connect plugin. 

With the power of AI we where able to set up an easy to use starterkit that works wel with our Probo Connect plug-in 


## Support and usage
Please be aware, that Probo supplies the theme "as-is". You are free to customize the theme or change it.
If you found a bug you can submit a pull request, that would greatly be appricaited. 

You may not sell the theme as your own. Whiles using it you do not have to mention Probo.


## Install

Copy (or symlink) this folder into `wp-content/themes/probo-connect` and activate
it. WooCommerce is optional for the theme to load, but the shop templates only
do anything with it active.

The compiled CSS is committed, so the theme works without running npm.

## Child themes

A standard WordPress child theme works, nothing theme-specific to wire up —
just `Template: probo-connect` in the child's `style.css` header.

`probo_enqueue_assets()` already chains the parent's compiled CSS (`pp-theme`)
and the active theme's own `style.css` (`pp-style`, via `get_stylesheet_uri()`)
as dependencies, so a child theme does not need to enqueue the parent
stylesheet itself — just write rules into its own `style.css`, or enqueue
further stylesheets after `pp-theme`.

What a child theme's file copies shadow, same as core template overrides:

* Any template WordPress' template hierarchy covers — `single-product.php`,
  `header.php`, `footer.php`, `woocommerce/*` overrides, and so on.
* Callout templates, `templates/callouts/{placement}/{name}.php` — looked up
  child-first by `probo_callout_template_roots()`.

What file copies **cannot** shadow: block registration
(`inc/blocks.php`) and the `assets/` JS/CSS always load from the parent
theme's directory. Change those from the child's `functions.php` (which loads
after the parent's) via filters instead — the ones most likely to matter:

| Filter | Controls |
| --- | --- |
| `probo_layered_style_handles` | which plugin stylesheets get moved into the `plugins` cascade layer |
| `probo_checkout_is_stepped` | one-page vs. stepped checkout |
| `probo_checkout_pickup_instance_ids` | which shipping methods count as pickup |
| `probo_callout_template_roots`, `_dir`, `_placements`, `probo_callout_max_slots` | callout template discovery |

## Customizing without code

Everything the design session iterated on is a Customizer control, under
**Weergave → Customizer → Thema-instellingen**:

| Section | What it does |
| --- | --- |
| Merk | Accent colour, secondary colour, corner radius |
| Typografie | Title font and body font (specs and prices stay IBM Plex Mono by design) |
| Header & footer | Header style (see [Header](#header)), top-bar style and optional own colour, footer style, light logo, the three USP lines, the checkout phone number, footer texts |
| Componenten | Card style: Rand / Schaduw / Vlak, the checkout style — see [Checkout](#checkout) — and how much of the shop needs a login, see [Login required](#login-required) |

The hero's own band — dark, accent or light, and its title colour — is **not**
here: a front page has one hero, and what it looks like is a decision about that
block on that page, so it lives in the block's sidebar. The Customizer only
supplies the brand colours the band is derived from.

Two behaviours are deliberate and worth knowing about:

* **Secondary drives every dark surface** — hero, top bar, footer, cart button,
  price bars, summary frames. Text and rules flip to whatever contrasts, so a
  pale secondary does not produce white-on-white.
* **A hero title colour too close to the hero background is ignored.** The title
  falls back to the contrasting colour rather than disappearing. (Set on the
  hero block, not in the Customizer.)

### Header

Three headers, chosen under **Header & footer → Header-stijl**. The setting is
opt-in in the strict sense: `header.php` matches the stored value against its
own allowlist and falls back to **Ruim** for anything else, so neither of the
other two can load without an explicit choice.

| Value | What it is |
| --- | --- |
| **Ruim** (default) | Three rows: USP top bar, logo + search + account, primary nav with flyouts. ~180px. |
| **Compact** | One dark bar — logo, products megamenu, search, account, cart — over a thin light USP strip. ~114px, so a category page keeps its hero and first product row above the fold. |
| **Portal** | One light bar: logo, flat account navigation, the signed-in company and a log-out link. 68px. |

**Portal has no search and no cart, by design.** It is the chrome for a B2B
account area, where the questions are "where am I in my account" and "who am I
signed in as" — not "what do I buy". It is a whole-site header like the other
two, so a shop that still needs the cart in the header on shop pages wants
**Ruim** or **Compact**; Portal suits an installation whose front end *is* the
portal. Two more things follow from that:

* **It shows the billing company, not the person** — `probo_portal_account_name()`
  reads `billing_company` and only falls back to the display name. The avatar
  beside it is that name's initials, and is decorative: the name is spelled out
  next to it (and stays spelled out from 640px up). A signed-out visitor gets
  the plain log-in link instead of the chip and the log-out link.
* **Its nav is `Hoofdnavigatie`, flattened to one level.** The design draws four
  flat links with the current one in accent, so the portal header renders the
  same menu the other variants use at `depth => 1` and drops the flyouts. Assign
  the portal's own items (orders, invoices, addresses) to `Hoofdnavigatie`.

The portal bar is a 1120px measure, not the 1280px `.pp-container` gives the
rest of the theme — the design's own narrower column, on the grounds that four
nav items and an account chip do not need the full width. Below 1024px the nav
collapses behind the burger and opens as a panel under the bar, through the
same `data-pp-nav` hook Variant A uses.

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
`probo/how-it-works`, `probo/contact`, `probo/faq` — all in the **Probo
Connect** inserter category.

A few of them carry options worth knowing about:

* **Hero** has ten variants, A–J, named after the design handoff's own gallery:
  a search hero (A, dark; I, light with tags), an editorial split with the image
  left, right or off (B), a full-bleed photo (C), category tiles (D), a minimal
  centred band (E), a B2B rail of figures (F), a split with a review card (G), a
  promotion band with a countdown (H) and a showreel with a play button (J).
  They share one attribute set — the copy survives a switch — and each variant
  reads the part of it that its layout has a place for, which is also all the
  sidebar shows. One variant per file in `blocks/hero/variants/`.
  Only variant A picks its own band — dark, accent or light, with a matching
  title colour, both in the block's sidebar; the other nine are bands the design
  fixes. A takes an optional gradient over its image — from the bottom, from the left, or as a vignette — mixed from the
  hero's own background colour, so it follows the Customizer instead of baking
  in a black scrim.
* **USP-balk** draws as the hairline bar under the hero or as four cards
  (**Stijl → Kaarten**) for use further down a page. Each line takes an optional
  third field: the glyph in its icon tile.
* **Contact** puts the contact details beside a real form. Left empty, the form
  posts to the theme's own handler (`inc/contact.php`): nonce, honeypot,
  server-side validation and one `wp_mail()` to the site administrator — filter
  `probo_contact_recipient` to send it somewhere else. A shop that already runs
  Contact Form 7 or WPForms puts that shortcode in the block's *Form shortcode*
  field instead, and the theme's handler is never reached.
* **FAQ** is a real accordion: `<details>` rows that work before any script
  loads, with `assets/js/theme.js` adding the one thing the element cannot do
  itself — closing the row that was open.
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

## Products per customer

Some products are not for the whole shop. A product's edit screen has a
**Customer access** tab (`inc/product-access.php`): switch **Limit to selected
customers** on, name the customers, and the product flips from public to
invitation-only.

For everyone who was not named it stops existing rather than merely becoming
unbuyable. It is dropped from the shop, from category pages, from search, from
related products and from the XML sitemap; its own URL stops resolving (a
logged-out visitor is sent to the login form with an explanation, a logged-in
one gets a 404, because naming a product someone may not have leaks the
catalogue one URL at a time); it cannot be added to the cart, and a line that
was already in a cart is dropped again the next time the cart or checkout is
drawn — access can be withdrawn between filling a cart and paying for it.

Anyone who may edit products sees the whole catalogue as it really is, otherwise
the products they just restricted would disappear from under them.

The same grants are editable from the other end: a customer's profile screen
lists every restricted product with a checkbox. Both screens write the same
place — one `_probo_access_user` meta row per customer on the product — so there
is no second source of truth to drift. One row each rather than one array is
what makes "which products may this customer see?" an ordinary indexed lookup.

Variations are never restricted on their own; they inherit their parent
product's rule.

### A page with the customer's own products

The shop already shows a customer only what they may see, their own products
among everything public. A portal usually wants the other page too — *your*
products, and nothing else. Put this on any page:

```
[probo_my_products]
```

It draws the same tiles the shop does, for whoever is logged in.
`limit`, `orderby` and `order` narrow it down, and `empty` replaces the line
shown to a customer who has not been given anything yet:

```
[probo_my_products limit="6" orderby="date" order="DESC" empty="Nothing set up for you yet."]
```

Logged out it shows a login prompt instead, which on a closed portal nobody
ever reaches — the wall gets there first.

Listed are the products that are limited *and* granted to this customer: a
product that was opened back up to the whole shop is in the catalogue like any
other, and calling it theirs alone would be a lie. From a template,
`probo_customer_product_ids()` gives the same list as ids and
`probo_render_product_grid( $ids )` draws it.

Four filters if you need something else:

| Filter | Does |
| --- | --- |
| `probo_customer_can_access_product` | The final say on one customer and one product — where a plugin, a purchase history, an ERP lookup or a rule of your own (per role, per contract) goes. |
| `probo_product_access_manage_cap` | The capability that sees everything; `edit_products` by default. |
| `probo_product_access_denied_action` | `login`, `shop` or a 404 when someone opens a product that is not theirs. |
| `probo_product_access_denied_message` | The wording they get. |

`probo_product_access_profile_limit` sets how many products the profile screen
lists before it sends you to the product's own tab instead.

Access is per customer and nothing else — no role or group axis. Groups are the
kind of thing that looks like a shortcut and turns into a second rulebook to
keep in sync; a shop that genuinely needs one adds it in a few lines on
`probo_customer_can_access_product` without this screen growing a list of
WordPress roles that mean nothing to a customer.

## Login required

Off by default — the shop sells to whoever walks in. Under **Customizer →
Thema-instellingen → Componenten → Login required** the wall goes up at one of
three heights (`inc/login-required.php`):

| Setting | What a logged-out visitor can do |
| --- | --- |
| `Uit` | Everything. Browse, fill a cart, order. |
| `Kassa` | Browse and fill a cart, but log in to order it. The cart survives the login. |
| `Winkelwagen` | Browse, and nothing more — the cart itself needs an account. |
| `Hele site` | Nothing. Every page sends them to the login form: a closed order portal. |

Under `Hele site` a customer's whole world is the front end: they log in there,
see the products they were given and the orders they placed, finish a checkout,
and that is the portal. wp-admin is not part of it — see below.

The first three leave browsing open on purpose. Which customer may see which
product is a per-product decision and lives in
[Products per customer](#products-per-customer); this setting is about how much
of the shop needs an account at all.

### The closed portal

`Hele site` is the setting for a portal that is not a public shop. A logged-out
visitor is turned away on `template_redirect` at priority 1 — ahead of the
sitemap and the feeds, which render on that same hook, so a closed portal does
not hand out its catalogue on the way out. `wp-json` closes with it, through
`rest_authentication_errors`; leaving it open would serve posts, products and
users to anyone who asks, which is the whole point being missed. And the site
goes `noindex`, because a portal nobody may read is not one to list.

Two things stay open, and only two: the **My account page**, which carries the
login form, the registration form and the password reset — closing it would
close the portal to its own members — and **robots.txt**, which holds no content
of its own and is the one file that tells a crawler to stay out. A portal that
needs a public page of its own (a contact page, or one explaining how to get an
account) opens it through `probo_login_required_public_request`.

**wp-admin closes too.** A logged-in customer who lands there is sent back to
the account page, and the toolbar disappears from the front end with it — it is
a strip of links into an admin they cannot open. Staff keep both: anyone with
`edit_posts` or `manage_woocommerce`, so an editor, a shop manager and a
shop-floor role that was never given the post capabilities all still work.
`probo_login_required_is_staff` decides, and is filterable.

Three doors in wp-admin stay open regardless, because closing them would break
the front end the portal is made of: `admin-ajax.php`, which is how the shop's
own front-end requests come back; `admin-post.php`, where the theme's contact
form posts (`inc/contact.php`); and cron.

### Both walls

The visitor is told where the button is, not where it is not: the cart carries a
prompt with a **Log in** button while it is being filled, and under the cart wall
so does the product page, next to the add-to-cart it replaces. Above the login
form itself, one line says why they are looking at it.

Following any of it comes back to the page they were on. The return trip rides
in a `probo_redirect` argument that WooCommerce's own login form carries through
the POST, and both the outbound URL and the way back run past
`wp_validate_redirect()`, so a spoofed `Host` header cannot turn the login link
into an off-site one.

No message is parked in a WooCommerce session to say any of this. A closed
portal turns away every crawler that ever finds it, and a session notice would
mean a session row and a cookie each, for a message nobody reads.

What actually holds is server-side. The redirects are a courtesy; the refusals
sit on `woocommerce_checkout_process`, on the add-to-cart validation and on
`rest_authentication_errors`, where a hand-made request lands too. The
order-received page and the pay-for-order link stay open under the order walls:
both belong to an order that already exists, and a customer paying an invoice
link is not placing a new one.

`probo_login_required_scope` overrides the setting from code (`off`, `checkout`,
`cart` or `site`), `probo_login_required_message` the wording.

One thing this setting deliberately does not touch: who may create an account.
Whether a closed portal lets people register themselves or only admits accounts
the shop makes for them is WooCommerce's own setting, under **WooCommerce →
Settings → Accounts & Privacy**.

## Checkout

Two layouts, chosen under **Customizer → Thema-instellingen → Componenten →
Checkout-stijl**. The setting is opt-in: a shop that upgrades keeps the checkout
it had.

| Value | What it is |
| --- | --- |
| **Eén pagina** (default) | The classic layout: five numbered sections under each other, order button under "Te betalen" in the summary column. |
| **Stappen** | The accordion: 1 Gegevens & adres, 2 Bezorging, 3 Betalen. One step open, the rest collapsed to a summary line with "Wijzig". |

**Eén pagina** got its own pass from the checkout design handoff, on the four
things that were wrong with the live page:

* **Labels no longer sit against the field above them.** The billing, shipping
  and order-note wrappers are a two-column grid with a rhythm of their own
  (`gap:14px 12px`), full width for country, street and email, postcode and city
  side by side. Those rules are not scoped to a layout — the accordion asks for
  the same addresses.
* **Eleven delivery days became a chip grid** of four columns (two below 760px),
  and the carriers one bordered block with hairline dividers and an accent bar
  in the gutter of the selected row, instead of fifteen loose cards. Both are
  scoped to `.pp-checkout-delivery:not(.pp-delivery)`: the accordion answers the
  same question with its presets, and hides the lists behind them.
* **A step bar above section 1** — Gegevens · Bezorging · Betalen — names what is
  about to be asked and marks the first thing not answered yet. It reads the
  same `probo_checkout_steps()` as the accordion's progress line, so the two
  cannot drift apart in what they call the steps.
* **The coupon notice stays inside the container.** It ran outside it and lost
  its left half.

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
directory — the plugin reads GitHub releases directly.

Releases are automatic: every push to `main` runs
`.github/workflows/auto-version.yml`, which bumps `Version` in
`style.css` to the next whole number (`1.0.0` → `2.0.0` → `3.0.0` → ...),
runs `npm run build`, commits that bump back to `main`, tags it, and
publishes a GitHub release with a `probo-connect.zip` asset — root folder
named `probo-connect`, the theme's stylesheet slug, which is what
WordPress installs it as. The plugin's update check expects exactly that:
one asset, that folder name, on `releases/latest`. The zip is also
attached to the workflow run itself as a build artifact.

For a manual/hotfix release off another ref, `.github/workflows/release.yml`
still works the old way:

1. Bump `Version` in `style.css`.
2. Run `npm run build` and commit the resulting `assets/css/theme.css`.
3. Tag and push: `git tag vX.Y.Z && git push origin vX.Y.Z`.
