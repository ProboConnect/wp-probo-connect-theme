# Probo Connect theme — merged audit plan

Merges two independent passes over the theme:

- **`review-findings.md`** — correctness / security / WP-convention / performance review
  (findings `A1–A11`, `B1–B4`, `C1–C9`, `D1–D5`, `E1–E10`).
- **`AUDIT.md`** — simplification & representation audit, 19 subsystems
  (findings `F1–F23`, plus 20 explicit reasoned skips).

Where both passes found the same thing the items are merged and carry both ids.
Nothing has been changed in the theme by this document.

---

## 0. Baseline

Merged and verified against `origin/main` (= `origin/audit`), commit `5d0e713` — the same
working copy both audits were written against. Every finding below was re-checked against
that tree: file paths, line anchors and code shapes match. Nothing is deferred for a
version mismatch.

Verification notes worth carrying into the work:

- The search field's id prefix is **`pp-search-`**, not `probo-search-` (`inc/template-tags.php:158-160`).
- `probo_callout_tile()` genuinely does **not** exist anywhere in the tree — `README.md:76`
  names it as the anti-drift guarantee. Confirmed (F23).
- `/bin/` **is** in `.gitignore` while `package.json` ships `check:templates`. Confirmed (C5).
- `templates/callouts/category_top/top.php:3` and `category_bottom/bottom.php:3` both declare
  `Callout Template: Banner`. Confirmed (E2).

---

## 1. Priority model

| Priority | Meaning | Gate to ship |
|---|---|---|
| **P0** | Wrong output a customer or admin sees today, or a silent data/state loss. Small, contained, high confidence. | none — do them first |
| **P1** | Real latent bugs and the representation fixes that make a class of bug unrepresentable. Needs care but no product decision. | code review |
| **P2** | Convention, i18n, performance, and hardening work. Larger but mechanical. | code review |
| **P3** | Product decisions, sweeps, and polish. Some should be *declined* rather than half-done. | maintainer decision |

There is **no test suite** in this repo (no PHPUnit, no JS runner). The only automated
check is `bin/check-templates.php` (`npm run check:templates`), which covers
`woocommerce/**` only — it does **not** cover `blocks/`, `assets/js/`, `inc/`, or
`probo-connect/`. Every validation step below is therefore manual, and a green
`check:templates` proves nothing about most of this work.

---

## 2. P0 — visible defects, ship first

Each is one file, XS–S effort, high confidence, and leaves working configurations
byte-identical.

### P0-1 · `F22` — Category-grid callout can render zero times
`blocks/category-grid/render.php:44-46`, `:64`, `:105`, `:112`.
Three ownership guards derived from one `$position` enum are mutually exclusive but
**not exhaustive**. With `calloutPosition === 'Interval'` and `count($terms) <= $interval`
none fires and the enabled callout vanishes. Reachable with shipped defaults
(`block.json:24` defaults `calloutInterval` to `4` → a shop with ≤4 top-level categories).
**Fix:** add `$callout_drawn`, set it at each emit site, change the tail guard to
`! $callout_drawn`. Category-owned callouts (`showTermCallouts`, `:90-98`) must **not**
set the flag.
**Validate:** 3 categories + Interval + interval 4 → callout appears once; 8 categories →
output unchanged from today.

### P0-2 · `F21` + `E3` — logo-reel glued Tailwind class + dead alt fallback
`blocks/logo-reel/render.php:36-49`.
(a) `class="w-auto object-contain%s"` is the only `class="…%s` in `blocks/`; README:232-233
says classes in PHP must be whole literals or they do not survive the Tailwind build.
`.object-contain` currently only survives because `inc/template-tags.php:51` uses it —
the logo bar's layout stands on an unrelated file.
(b) `isset( $logo['name'] )` is true for `''`, so a blank name yields `alt=""` and the
`_wp_attachment_image_alt` fallback is dead. Intent is `! empty()`.
(c) `rel="noopener"` on `:49` has no `target="_blank"` — drop it or add the target plus
`rel="noopener noreferrer"`.
**Fix:** emit the `<img>` inline as `blocks/bento-grid/render.php:60-66` does, each class
token a standalone literal; drop the `sprintf` + `wp_kses_post` round-trip; keep the
`(int)` height cast and `esc_attr()`.

### P0-3 · `A2` = `F7` — duplicate `id` on the search field
`inc/template-tags.php:158,160` — `probo_search_form()` derives both `for=` and `id=` from
`$size` alone (`id="pp-search-<size>"`), so `$size` conflates a *style key* with *DOM
identity*. Both headers render the form twice with the same argument:
`template-parts/header-ruim.php:54` + `:84` (`'header'`), `template-parts/header-compact.php:47`
+ `:81` (`'compact'`). `index.php:63`, `404.php:21` and `searchform.php:10` each emit
`'header'` too, so a search-results page under the spacious header can carry **three**
identical ids.
Duplicate `id` → the `sr-only` `<label for>` binds to the wrong input; clicking the label
focuses the hidden desktop field; autofill and any future `getElementById` hit the wrong one.
**Fix:** keep `$size` as a style key only; derive the id from a function-static counter:
```php
static $instance = 0;
$uid = 'pp-search-' . $size . '-' . ++$instance;
```
**Check before landing:** grep CSS/JS for `#pp-search-` (none found today). Ids become
render-order dependent; if a stable hook is ever needed add `data-probo-search="<size>"`
rather than reusing the id.

### P0-4 · `A1` — product page always draws five filled stars
`woocommerce/single-product.php:60` — `str_repeat( '★', 5 )` beside the real average.
A 3.2-rated product renders `★★★★★ 3,2 / 5`. The graphic contradicts the number and
overstates the rating.
**Fix (preferred):** `wc_get_rating_html( $product->get_average_rating(), $product->get_review_count() )`
so WooCommerce's accessible markup and translations apply. **Fix (minimal):** mirror
`blocks/testimonials/render.php:46-49` — `$filled = (int) round( $avg )`, clamp 0–5,
`str_repeat('★',$filled) . str_repeat('☆', 5-$filled)`. Keep `aria-hidden="true"` only
while the numeric text stays.
**Note:** this file is `@version`-tracked — re-run `npm run check:templates` after editing.

### P0-5 · `F15` — mobile nav can open by itself, and lies to screen readers
`assets/js/theme.js:17-50`.
Open state lives in two places with no single writer: the `hidden` class on
`[data-probo-nav]` and `aria-expanded` on the toggle. `initNav` writes the class and
*derives* the flag; `initNavReset.sync()` reads `aria-expanded` as truth but on desktop
only removes `hidden` and never resets the flag.
**Reachable failure:** open burger on mobile → resize past 1024px → resize back → `sync()`
sees `aria-expanded === 'true'` and does not re-add `hidden` → **the nav is open with no
user action**, and the collapsed desktop nav was announced as expanded the whole time.
**Fix:** give the burger the same single `setOpen(open)` writer `initProductsMenu` already
uses (`:157-160`) — class and ARIA set together — and have `sync()` call it. Merges
`initNav`/`initNavReset`, which also query the same two elements separately.
**Validate:** the resize sequence twice; keyboard-only open/close; assert `aria-expanded`
matches visible state at both widths.

### P0-6 · `A11` — threaded comment replies are broken
Nothing enqueues `comment-reply` (grep: zero hits). `single.php` loads `comments_template()`
and `comments.php` calls `comment_form()`, so with threading on (the default) "Reply" jumps
to the page bottom instead of moving the form.
**Fix** in `probo_enqueue_assets()` (`functions.php`):
```php
if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
	wp_enqueue_script( 'comment-reply' );
}
```

### P0-7 · `A10` — `prose-pp` is used but never defined
`page.php:41`, `single.php:24` apply `.prose-pp`; it exists in neither
`assets/css/src/theme.css` nor the compiled `assets/css/theme.css`, and there is no
`@tailwindcss/typography` in `package.json`. Every long-form page and every blog post
renders `the_content()` with no heading scale, list markers, blockquote or link treatment.
**Fix:** add a `.prose-pp` component block to `assets/css/src/theme.css` (headings, `ul/ol`
markers, `a` underline, `blockquote`, `table`, `img` radius) **or** add
`@tailwindcss/typography` and map onto it. Do not silently drop the class.

### P0-8 · `A5` — "Choose a carrier yourself (N)" counts one too many
`inc/woocommerce.php:595-600` — the fold contains `$rest = array_slice( $ship, 1 )` but the
label counts `count( $ship )`. Three carriers → two rows under a label promising three.
**Fix:** `count( $rest )`. Leave the pickup fold at `:634` alone — "All %s pickup points"
with `count( $pickup )` is correct there.

### P0-9 · `A3` — top-bar USPs vanish on mobile when USP 1 is empty
Both headers, same root cause: "skip the empty ones" is mixed with an index that counts
positions in the *original* list.
- **Spacious** (`template-parts/header-ruim.php:20-27`): the loop `continue`s on an empty
  setting but picks the always-visible class with `0 === $index`, where `$index` is the
  position in the **key list**. Empty `topbar_usp_1` → index 0 is skipped and USPs 2 and 3
  both get `hidden … md:flex` → the top bar is blank below `md`.
- **Compact** (`template-parts/header-compact.php:127-131`):
  `array_filter( array_map( 'probo_get', [...] ) )` **preserves keys**, so with USP 1 empty
  the survivors are keys 1 and 2 and `0 === $index` is never true — same blank strip, at
  every width below `min-[1080px]`.
**Fix:** in both files build the filtered list first and **re-index** it
(`array_values( array_filter( … ) )`), keeping `$key` alongside the value for the
`data-probo-partial` attribute, then loop with a fresh index. The spacious header must stop
mixing "skip empty" with a key-position index.
`inc/template-tags.php:630` (`probo_checkout_reassurance`) uses the same
`array_filter(array_map(…))` idiom but iterates values only — **not affected**; leave it, or
normalise it as part of the F13 sweep (P3).

### P0-10 · `A6` — spec line is empty for custom (non-taxonomy) attributes
`inc/template-tags.php` (`probo_product_spec_line()`) calls `wc_get_product_terms()` for
every visible attribute. That only resolves **global** taxonomy attributes; a custom
per-product attribute — what most print shops type by hand — returns `[]` and the tile
shows no spec line.
**Fix:**
```php
$values = $attribute->is_taxonomy()
	? wc_get_product_terms( $product->get_id(), $attribute->get_name(), array( 'fields' => 'names' ) )
	: $attribute->get_options();
```

### P0-11 · `F16` — configurator-context fallback is inverted
`functions.php:196-202` returns `is_singular()` when `is_product()` is missing — i.e.
**broader** than the guarded branch. With WooCommerce active `print-connect.css` loads on
~3 page types; with WooCommerce absent, when the configurator provably cannot exist, it
loads on every post, page and attachment. `inc/woocommerce.php:769-775` uses the correct
opposite polarity.
**Fix:** `return function_exists( 'is_product' ) && ( is_product() || is_cart() || is_checkout() );`
Bleed risk is nil — `print-connect.css` selectors are all rooted in `.pp-configurator` /
`connect-*` / `.cart_item`.

---

## 3. P1 — latent bugs and representation fixes

### P1-1 · `F1` — delivery ownership decided by three different predicates
**The single most valuable finding.**
`inc/woocommerce.php:864` gates on `class_exists('Probo_Meta_Keys') && WC()->session`;
`:957` gates on the same **plus** `->get(SHIPPING_DELIVERY_DATE)`;
`woocommerce/checkout/form-checkout.php:125-132` decides by whether the plugin's renderer
produced output.
**Invalid state:** plugin active but the cart falls back to WooCommerce rates (freight /
oversized, `form-checkout.php:281-283`) → `probo_checkout_step_2_complete()` takes the
plugin branch and returns **false forever**, while `probo_checkout_delivery_summary_parts()`
falls through to the Woo branch and returns a **populated** summary. Step 2 shows a
confirmed answer that step 3 can never be reached from; every reload throws the customer
back.
**Fix:** one internal resolver returning `{ source: connect|woo|none, complete, parts }`;
both public functions become readings of it. Scope: `inc/woocommerce.php` only, both
signatures unchanged.
**Validate manually** against the real plugin on the freight/non-Connect path — this is the
one item that genuinely needs a live shop.

### P1-2 · `F2` — checkout `{current, completed[]}` should be one high-water mark
`assets/js/checkout-steps.js:25-28`, `:54-57`, `:73-81`, `:101-102`, `:532-534`.
`restore()` clamps `current` against the server's step but copies `completed` **verbatim**,
so a stored `{current:3, completed:[1,2]}` against server step 1 renders step 2 as done
("Wijzig") above an empty open step 1 — and `persist()` re-saves the contradiction.
`completed` is provably always the prefix `{1..k}`, so `furthest = max(current)` is exactly
equivalent and makes the contradiction unrepresentable.
**Fix:** replace with `furthest`; deletes `isCompleted`, `complete`, the seed loop and the
second clamp branch, and makes the JS agree by construction with `inc/template-tags.php`
(`probo_checkout_steps` consumer).
**Migration (required):** old `sessionStorage` blobs have no `furthest` — either
`parsed.furthest || parsed.current` or bump the storage key.

### P1-3 · `A4` + `F6` — primary-menu fallback: false "current page", messy node shape
One function, `inc/template-tags.php` `probo_primary_menu_fallback()`, two defects:
- **`A4` (bug):** `if ( 0 === $index ) { $classes[] = 'current-menu-item'; }` flags the
  first category as current on **every** page of the site — home, cart, a blog post, a
  different category. `current-menu-item` is what themes and plugins map to `aria-current`,
  so this is a false a11y signal too.
  **Fix:** carry `term_id` into `$items[]` (dropped today) and compare:
  `$current = is_tax('product_cat') ? get_queried_object_id() : 0;`
- **`F6` (shape):** `url` is normalised eagerly for children but not for parents (a
  `WP_Error` can be stored and is only unwrapped at render); `children` is optional and
  guarded at every read; **`$child['self']` is read but no producer exists anywhere in the
  repo** — dead, and the matching CSS in `assets/css/src/theme.css` is dead with it.
  **Fix:** one URL helper so `url` is `string` by construction, `children`/`items` always
  present, drop `self` and its CSS.

### P1-4 · `D1` — the same fallback menu runs ~79 uncached queries per request
Same function. With no menu assigned to `primary` — the state of any fresh install, and the
README presents the fallback as a feature — every front-end page (including cart and
checkout) runs 1 × `get_terms` → 6 × `get_terms` → up to **72 × `get_posts`**.
**Fix:** transient-wrap the assembled `$items`, keyed on last-changed so it self-invalidates:
```php
$key = 'probo_menu_fallback_' . wp_cache_get_last_changed( 'terms' ) . '_' . wp_cache_get_last_changed( 'posts' );
```
`get_transient` → build → `set_transient( …, DAY_IN_SECONDS )`. No purge hook needed.
**Do P1-3 and P1-4 as one change** — same function, and F6's single-`get_terms`/`$by_parent`
restructure is the natural place to add the cache. Apply `exclude` at the same level or the
excluded default category reappears as a child, and pass `orderby`/`order` explicitly to
preserve ordering.

### P1-5 · `F8` → `F10` → `F9` — the token pipeline (sequenced, land in this order)
Hard dependency; all three touch `inc/dynamic-css.php`.

- **`F8` — `probo_get()` cannot distinguish "empty" from "unset".**
  `inc/settings.php:51-56`: `get_theme_mod()` substitutes the default only when the mod is
  *absent*, so a stored-but-empty value passes through. Reachable — `inc/customizer.php:83`
  registers `accent_color` with bare `sanitize_hex_color` (returns `null` on bad input),
  unlike `bar_color` at `:171`. Consumers paper over it: `inc/dynamic-css.php:29-30`
  `?: '#1B4DFF'` / `?: '#0B0B0C'`, a second copy of `probo_defaults()`'s brand hexes, with a
  third in `assets/css/src/theme.css:26,30`. Applied **non-uniformly**:
  `blocks/hero/render.php:21-22` passes `probo_get('accent_color')` raw, so a cleared accent
  makes the block and the page `:root` emit disagreeing values for the same token in one
  request.
  **Fix:** a narrow `probo_get_color( $key )` in `inc/settings.php`; use it at
  `dynamic-css.php:29-30` and `hero/render.php:21-22`.
  **Do not** change `probo_get()` itself — `checkout_phone` is documented "leave empty to
  hide" yet has a non-empty default; a blanket rule would resurrect a deleted phone number.

- **`F10` — `probo_tokens()`'s `$overrides` machinery is dead.** Both callers
  (`dynamic-css.php:282`, `:294`) are argument-less. The docblock names the hero block as
  the consumer, but `blocks/hero/render.php` bypasses it entirely and calls
  `probo_hero_tokens()` with a *different* fallback rule — so the docblock actively
  misdirects anyone tracing hero styling. Twelve reads are indirected through a closure that
  provably never overrides anything.
  **Fix:** delete the closure and the parameter; call `probo_get()`/`probo_get_color()`
  directly. **Keep** `apply_filters( 'probo_tokens', $tokens, array() )` with a literal empty
  array for one release — a third-party callback declaring `$overrides` would fatal.

- **`F9` — `dynamic-css.php` re-implements `color.php` in three spellings.** The helper is
  already used in the same file (`:39`, `:113`, `:174`, `:221`, `:251`), yet the same
  decision is hand-rolled at `:45`, `:95`, `:230` (`> 0.6 ? '#0B0B0C' : '#FFFFFF'`, i.e.
  `probo_contrast_fg`), and the darkness predicate appears as `$lum <= 0.6` (`:135`),
  `probo_is_dark($sec)` (`:193`), and `'#0B0B0C' === $fg` (`:114`, `:175` — inferring
  darkness by string-comparing a hex it just computed). `:236` hand-rolls
  `probo_readable_accent($title,$bg,$fg,0.12)` — the exact reason `$threshold` is a parameter.
  **Live consequence:** retuning the 0.6 threshold in `color.php` changes
  `--pp-accent-fg`/`--pp-hero-accent-fg` while leaving
  `--pp-bar-fg`/`--pp-footer-fg`/`--pp-secondary-fg`/`--pp-hero-fg` on the old value.
  **Fix:** call the existing helpers; drop `$lum` from `probo_bar_tokens()` (sole caller
  `:61`). Muted/line literals stay. `probo_hero_tokens()`'s signature must **not** change —
  `blocks/hero/render.php:18` calls it. `inc/color.php` is untouched.
  **Validate:** dump `:root{…}` before and after for a matrix of accent/secondary colours and
  diff for byte equality.

**Explicitly rejected here and worth restating:** unifying the bar/footer/hero surface
descriptors. Their token sets differ (5/6/6) and values for the same background are
deliberately unequal — muted is `rgba(255,255,255,.7)` / `#9A9A9E` / `#A6A6AC`; accent-bg
alphas `.74`/`.72`/`.78`; `line` keys off `lum > 0.85` in one and `is_dark` in another.
Unifying forces a visible regression. **Leave as is.**

### P1-6 · `A9` — cart badge is never updated by Woo's AJAX fragments
`template-parts/header-ruim.php:66-71` and `header-compact.php:64-69`, via `probo_cart_count()` (`inc/template-tags.php:78`). No `wc_add_to_cart_fragments` registration and
no wrapping selector, so any AJAX add-to-cart (Woo's loop button, the Probo configurator, a
mini-cart plugin) leaves a stale number until a full page load — and under page caching the
number is wrong on arrival.
**Fix:** wrap the badge in a stable selector (`.pp-cart-count`), render it from **one shared
helper** so the markup cannot drift, and register:
```php
add_filter( 'woocommerce_add_to_cart_fragments', function ( $fragments ) {
	ob_start(); probo_cart_badge(); $fragments['.pp-cart-count'] = ob_get_clean();
	return $fragments;
} );
```

### P1-7 · `B1` — callout capability check is inverted
`inc/category-callout.php` `probo_callout_capability()`:
```php
return current_user_can( 'manage_product_terms' ) ? 'manage_product_terms' : 'manage_categories';
```
Self-defeating — it asks whether the user has the strong cap and, if they do **not**, returns
the weaker one, which is then what the edit-fields and save handlers check. Net effect: the
guard passes for anyone with `manage_categories` (Editors, by default on stock WP), not just
users WooCommerce trusts with product terms. Exposure is limited (you still need
`term.php?taxonomy=product_cat` and `check_admin_referer()`), but a nonce is not an
authorization check and the code does not enforce its stated intent.
**Fix:**
```php
$tax = get_taxonomy( 'product_cat' );
$cap = $tax ? $tax->cap->edit_terms : 'manage_categories';
```
and consider `current_user_can( 'edit_term', $term_id )` for the per-term check in the save
handler.

### P1-8 · `B3` — `probo_tokens` filter output is written into `:root{}` unescaped
`inc/dynamic-css.php` (`probo_tokens_to_css`, `probo_print_tokens`). Every value is
concatenated straight into an inline `:root{ … }`. **Not exploitable today** — all current
producers are safe (hex from `sanitize_hex_color`, ints, whitelisted font names) — but the
filter is documented and published, and a value containing `}` closes the rule and admits
arbitrary CSS, which is a real defacement/exfil-by-CSS vector on a shop page.
**Fix:** sanitize in `probo_tokens_to_css()` — strip `;{}<>` and anything after `/*`, or run
values through a small allow-list validator (hex / `rgb()` / `color-mix()` / px / quoted font
name). Cheap insurance. **Sequence after `F10`** — same function region.

---

## 4. P2 — conventions, i18n, performance, hardening

### P2-1 · `C2` — i18n is half-done, inconsistent, and has no catalogue
The biggest single job; do it in one pass.
1. **No catalogue at all.** `functions.php` calls `load_theme_textdomain( 'probo-connect',
   get_template_directory() . '/languages' )` — **the directory does not exist**, and there
   is no `.pot`. A Dutch shop sees English UI ("Cart", "Search", "Place order", "Change")
   next to Dutch block content, and nothing is translatable until a POT exists.
2. **JS strings are untranslatable.** `MESSAGES` in `assets/js/checkout-steps.js:138-146`
   (the checkout field-validation copy — the most customer-facing text in the theme) and
   `'(' + count + ' locaties)'` at `:449` are hardcoded Dutch in a file that never calls
   `wp.i18n`. `wp_set_script_translations()` is never called for any handle.
3. **Core strings re-translated under the theme's domain**, throwing away WooCommerce's own
   translations: `inc/woocommerce.php:1041` (`__( 'Place order', 'probo-connect' )` — core
   uses the `woocommerce` domain), `woocommerce/cart/cart.php:151`
   (`__( 'Remove %s from cart', … )`), and `woocommerce/cart/cart-empty.php:36`.
4. **Source language is inconsistent** — PHP is English `__()`, every `blocks/*/block.json`
   has Dutch `title`/`description` and Dutch default attribute content, and
   `bin/check-templates.php` prints Dutch.
**Fix:** create `languages/` and generate `probo-connect.pot`
(`wp i18n make-pot . languages/probo-connect.pot`); add an `i18n` npm script beside
`check:templates`. Move `MESSAGES` and the pickup-count string into PHP behind
`wp_localize_script( 'probo-checkout-steps', 'proboCheckoutL10n', … )` with each value in
`__()`, or convert the file to `wp.i18n.__()` + `wp_set_script_translations()`. Use the
`woocommerce` domain for strings that exist in core. Pick **English source + Dutch
translation** and move the Dutch block defaults into translated defaults.

### P2-2 · `C1` — the theme has no `theme.json`
Absent from the theme root; `functions.php:24-63` uses `add_theme_support` for `align-wide`,
`editor-styles`, `wp-block-styles`. For a theme whose homepage is **assembled from blocks**,
this means core injects its default palette, gradients and duotone CSS the design never
uses; the editor offers colours that do not exist in the design; block spacing/layout are
core defaults; and none of the `--pp-*` tokens reach the editor's controls.
**Fix:** a v3 `theme.json` declaring `settings.color.palette` from the theme's tokens,
`defaultPalette: false`, `defaultGradients: false`, `settings.layout.contentSize/wideSize`
matching `.pp-container`, and `settings.typography` with the two font families.
**This will change how blocks look in the editor** — schedule it with a visual pass.

### P2-3 · `D2` — no responsive images anywhere
`blocks/hero/render.php:83`, `blocks/bento-grid/render.php:62-67`,
`blocks/category-grid/render.php:80`, `blocks/logo-reel/render.php:38-46`,
`inc/template-tags.php:53-57` (the logo) — and `templates/callouts/*`.
All resolve an attachment to a single URL with `wp_get_attachment_image_url()` and
hand-write the `<img>`: no `srcset`/`sizes` (mobile downloads the full file — the hero pulls
`'full'`, which on a print shop's media library is routinely multi-MB), no `width`/`height`
so every one causes layout shift, no `decoding`/`fetchpriority`. The hero has no `loading`
attribute at all while below-the-fold images correctly have `loading="lazy"`.
**Fix:** `wp_get_attachment_image( $id, $size, false, array( 'class' => …, 'alt' => … ) )`,
which emits `srcset`, `sizes`, `width`, `height` and `loading` for free — **the pattern
already exists correctly in `probo_product_card()`** (`inc/template-tags.php:509-516`). For
the hero add `fetchpriority="high"` and `loading="eager"` (it is the LCP element) and use
`large`/`1536x1536` rather than `full`.
**Sequence with P0-2** — both rewrite `logo-reel/render.php`'s `<img>`.

### P2-4 · `D3` — an unused checkout fragment is computed on every AJAX update
`inc/woocommerce.php:1074-1076` always adds `$fragments['.pp-checkout-shipping']`. With
Probo Connect active, `form-checkout.php:130` sets `$probo_shipping = ''` and the element is
never in the DOM — but `probo_checkout_shipping_html()` still runs
`WC()->shipping()->get_packages()` and renders the whole carrier list on **every**
`update_order_review` call: every address keystroke, every delivery choice. This is the
hottest AJAX path in the shop.
**Fix:** add the fragment only when the Connect selector is absent — a `has_action(
'probo_checkout_shipping_selector' )` check is enough. **Depends on P1-1** — reuse that
resolver rather than adding a fourth predicate.

### P2-5 · `C8` + `F5` — dropped style handles: two mechanisms, one list
Two findings that must be resolved **together**, and in `F5`'s direction:
- `inc/woocommerce.php:112-116` hardcodes `wp_dequeue_style()` for two handles; `:129-136`
  returns the same two behind the documented `probo_dropped_style_handles` filter, consumed
  at `:170`. A shop using the filter gets the print-time drop but **not** the dequeue → the
  handle stays queued, still resolves as a dependency, still prints attached inline styles.
  **Fix:** have `probo_dequeue_woocommerce_layout()` iterate `probo_dropped_style_handles()`.
- **Do not delete the dequeue** (as `C8` suggested) — the print-time drop is the weaker
  mechanism for inline styles and dependency resolution.
- **Do take `C8`'s other half:** the `style_loader_tag` filter runs in the admin too. Early-
  return `$tag` when `is_admin()` — suppressing a stylesheet by handle everywhere,
  unconditionally, is a wide net for a front-end-only rule.

### P2-6 · `C6` — version defined twice, CI checks one
`functions.php:10` (`define( 'PROBO_VERSION', '1.0.0' )`) and `style.css:6`
(`Version: 1.0.0`); `.github/workflows/release.yml` verifies the git tag against `style.css`
only. A release that bumps `style.css` and forgets `functions.php` passes CI and ships a
wrong asset-version fallback.
**Fix:** `define( 'PROBO_VERSION', wp_get_theme( get_template() )->get( 'Version' ) );` —
single source of truth, and the existing CI check then covers everything.

### P2-7 · `C7` — release zip ships development files
`.github/workflows/release.yml` excludes `.git`, `.github`, `node_modules`, `.idea`,
`.claude`, `.DS_Store`, `build` — but still ships `package.json`, `package-lock.json`,
`bin/`, `docs/`, and **`assets/css/src/theme.css`**, a second unminified copy of the design
system in the web root.
**Fix:** add `--exclude='package*.json' --exclude='assets/css/src' --exclude='bin' --exclude='docs'`.

### P2-8 · `D5` — Tailwind `@source` scans the whole theme root
`assets/css/src/theme.css:13` — `@source "../../../**/*.php";` resolves to the theme root,
which contains `node_modules/`. Slows every `npm run dev`/`build` and risks class names
being harvested from dependency files.
**Fix:** enumerate real source roots —
`@source "../../../{inc,blocks,template-parts,templates,woocommerce,probo-connect}/**/*.php";`
plus the root-level
templates.

### P2-9 · `C9` — `remove_action()` calls inside a template
`woocommerce/archive-product.php:104-105` removes the result count and catalog ordering
permanently for the rest of the request, so a second product loop later on the same page (a
related-products widget, a shortcode) silently loses both.
`woocommerce/checkout/form-checkout.php:317` + `:324` is at least symmetric but misbehaves
if anything between throws.
**Fix:** move the archive's two calls into `probo_trim_woocommerce_loop()` in
`inc/woocommerce.php` where the rest of the unhooking lives. For the checkout, prefer a
one-shot closure on `woocommerce_checkout_order_review`.
**Note:** both files are `@version`-tracked; re-run `npm run check:templates`.

### P2-10 · `C3` + `C4` — Customizer preview transports
- **`C3`:** `search_placeholder` is registered `'postMessage'` (`inc/customizer.php:213-221`)
  but appears in neither the selective-refresh partial loop (`:334-352`, which covers
  `topbar_usp_1..3`, `checkout_phone`, `footer_description`, `footer_legal`) nor any
  `customize_preview_init` script — there is no preview JS in the theme at all. Typing a new
  placeholder does nothing until some other setting forces a refresh, which reads as broken.
  **Fix:** either a small `customize-preview.js` doing
  `$('[id^=probo-search-]').attr('placeholder', to)`, or change the transport back to
  `'refresh'`. `footer_payments` (`:236-245`) is correctly on `refresh` — match it if in doubt.
- **`C4`:** the generic partial callback (`:341-348`) returns `esc_html( probo_get( $key ) )`.
  It happens to be right for `footer_description` because `footer.php` prints the raw value
  inside the same `<p>` — but any future partial whose template applies `nl2br`, `wpautop` or
  a wrapper will silently differ between first paint and live edit. Add a per-key render map,
  or at minimum a comment stating the constraint.

### P2-11 · `F4` — two hook-relocation routines are one algorithm twice
`inc/woocommerce.php:270-308` and `:324-359` are structurally identical, varying only by
(class, method, option, default hook, destination hook). `$callback['accepted_args']` is read
from `$wp_filter` and then **discarded** — `add_action()` re-registers with the default `1`,
harmless only because both destination `do_action()` calls pass no arguments.
**Fix:** one private helper + two 4-line callers, each keeping its own doc block. Reject a
data table — it would separate the prose from what it explains. ~35 net lines and one class
of future divergence; also fixes the discarded `accepted_args`.

### P2-12 · `F11` — `probo-connect/` overrides have no drift detection — **decision, not a refactor**
`bin/check-templates.php:18` sets `$template_root = $theme_dir . '/woocommerce';` — the
walker never looks in `probo-connect/`. The plugin's own originals carry **no `@version`
header at all**, so `probo-connect/checkout/sections/shipping-blocks-shipping-dates.php`
(357 lines, a full redesign of a 54-line original) and `-methods.php` can diverge silently:
nothing warns if the plugin changes the shape of `$date['shipping_methods']`,
`rush_surcharge`, or the radio names the overrides depend on. The theme has a rigorous
versioning contract for one dependency and none for the other, while the README presents
both as the same mechanism.
**This is an ownership decision, not a refactor.** Options: extend the walker to
`probo-connect/` with a theme-maintained `@probo-version` header; record a checksum of the
plugin originals; or document explicitly that these two files are unversioned and must be
re-diffed on every plugin upgrade.
**`npm run check:templates` reports green today and will keep doing so regardless.**

### P2-13 · `C5` — `bin/` is git-ignored but is a documented, shipped npm script
`.gitignore:2` ignores `/bin/`, yet `package.json:9` ships
`"check:templates": "php bin/check-templates.php"` and the README documents the command.
The template-drift checker is therefore never committed: nobody else on the team, and no CI
run, can execute the command the README tells them to run.
**Fix:** un-ignore `/bin/` (or narrow the ignore to whatever it was actually meant to hide)
and commit `bin/check-templates.php`. Its Dutch output strings (`:105`, `:135`, `:160`) vs.
the codebase's English source are part of P2-1.

### P2-14 · `F3` — callout legacy-compat rules implemented twice, already drifted
`inc/category-callout.php:476-519` (read path) vs `:762-774` (admin fix-ups). The admin copy
re-implements the `enabled` default and `resolve_template_key`, but **not** the
`display` → template inference.
**Confirmed consequence:** a row with `display => 'Tegel'` and no `template` renders as a
tile on the front end, but the edit screen's `<select>` matches no option, so the browser
selects the first (`category_bottom/bottom` — discovery sorts by placement+label, `:194-199`).
The next "Update" **silently rewrites the tile into a bottom band.**
**Fix:** one `probo_callout_hydrate_row()` owning all storage-level legacy translation;
`normalize_row()` becomes hydrate + gate + term-derived defaults; `edit_fields()` becomes
`array_map( 'probo_callout_hydrate_row', probo_callout_raw_rows() )`, deleting `:762-774`.
Scope: `inc/category-callout.php` only; no signature or storage change.
**Size the blast radius first:** `probo_callout_fields()` has no `display` key, so
`probo_callout_raw_rows():544` never reads legacy `probo_callout_display` — the `display`
branch is reachable only from array-meta rows. **Check production term meta for `display`
keys before starting.**

### P2-15 · `D4` — callout template discovery globs the disk on every request
`inc/category-callout.php:157-192` (`probo_callout_discover_templates`), cached per-request
only at `:200-210`. Two `glob()` calls per root (theme + child theme) on every category
archive and every page containing a Category Tiles block — filesystem I/O on a
cached-object site where nothing else touches the disk.
**Fix:** wrap the discovery result in a transient invalidated on `switch_theme` and
`upgrader_process_complete`; or keep the live re-scan only when
`wp_get_environment_type() !== 'production'` and use the transient in production.

---

## 5. P3 — decisions, sweeps, polish

### Needs a product decision before any code

- **`A7` — `probo_search_form()` forces every site search to be a product search.**
  The hidden `post_type=product` field is emitted whenever WooCommerce is active. This form
  is also `searchform.php` (so it is what `get_search_form()` returns **everywhere**), the
  404 search, and the "nothing found" search on `index.php:63`. Posts and pages can never be
  found through any search box on the site — including the one on the search-results page,
  making it impossible to broaden a failed search.
  **Decide:** products-only in the header (then add a `$products_only` parameter defaulting
  to true only for header calls), or site-wide search with products sorted first.
- **`A8` — cart/checkout hardcode "excl. VAT" / "incl. VAT".**
  `woocommerce/cart/cart.php:144`, `cart-totals.php:73`,
  `woocommerce/checkout/review-order.php:75` assert a tax mode the shop may not be in. With
  `wc_tax_enabled() === false`, or prices displayed including tax, these labels are simply
  false — a legal, consumer-facing statement about price.
  **Fix once decided:** print the suffix only when `wc_tax_enabled()`, and choose incl./excl.
  from `WC()->cart->display_prices_including_tax()`; core has
  `WC()->countries->inc_tax_or_vat()` / `ex_tax_or_vat()` for exactly this.
- **`B4` — Google Fonts loaded from Google's CDN on every page.**
  `inc/settings.php:104-125` (`probo_fonts_url`), enqueued at `functions.php:107`. For a
  Dutch/EU shop this is the well-known GDPR problem: every visitor's IP reaches Google before
  any consent, and a WooCommerce checkout is not the page to argue it on.
  **Options:** self-host the two chosen faces (the catalogue is a fixed nine families), or
  make "Google fonts / system fonts" a Customizer choice. If it stays remote, at minimum add
  `<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>`.

### Worthwhile, lower urgency

- **`E1` + `F18` — `probo_block_wrapper()`.** `inc/blocks.php:140-148` accepts `$attributes`,
  documents it, and **never reads it**; all 8 render templates pass it. Hero is the clearest
  misread — `blocks/hero/render.php:47` passes both `$attributes` *and* the attribute-derived
  style. Separately (`E1`) hero wraps that style in `esc_attr()` before
  `get_block_wrapper_attributes()` escapes it **again**, contradicting the helper's own
  docblock; harmless until the first token carrying an `&`.
  **Fix:** `probo_block_wrapper( $classes, $style = '' )`; drop the `esc_attr()` at the hero
  call site and correct the docblock.
  **All-or-nothing:** a missed call site silently *shifts* arguments — an array lands in
  `$classes` → `class="Array"` and the section loses all styling. Check the Probo Connect
  plugin for external callers first; it is a public theme-global function.
  **Land last among block changes** to avoid rebasing P0-1/P0-2/P2-3.
- **`F12` — `$probo_best` recomputed at every read.**
  `probo-connect/checkout/sections/shipping-blocks-shipping-dates.php` — the pure closure
  (`:51-68`) is invoked at `:76`, `:84` (**twice in one expression**), `:124` (itself called
  per preset at `:168`, `:249`), `:148` (twice), `:236`, `:338`. Cost is irrelevant;
  **readability** is the point — `:84` is the hardest line in the file because the incumbent's
  total is a function call on an index rather than a value.
  **Fix:** `$probo_bests = array_map( $probo_best, $probo_dates );` after the closure. Integer
  keys align by construction (`array_values(...)` at `:38`) — comment that; it is load-bearing.
  Radio `name`/`value`/`checked` triples (`:189-195`, `:241-250`) must stay byte-identical.
  Callers guard on `['method']` (`:208`, `:340-352`) because `total` is `0.0 + rush_surcharge`
  when no method exists — memoizing must not tempt anyone into dropping those guards.
  **Not covered by `check:templates`.** Manual validation only.
- **`F17` — widget `before_title`/`after_title` invariant.** `functions.php:86-88` derives
  `$is_shop`/`$is_nav`/`$is_column`; `:103` and `:104` each restate `( $is_nav || $is_column )`.
  A non-empty opener requires the matching `</div>` and nothing ties them together — edit one
  line, forget the other, and the sidebar emits an unclosed `<div>` that swallows the rest of
  the footer column. Secondary: classification mixes prefix matching (`shop-`, `nav-`) with an
  explicit id list, so a hypothetical `footer-4` falls through and gets an eyebrow title *on
  top of* the heading `footer.php:52-56` already prints.
  **Fix:** widen the existing `$id => $name` map to carry a variant; resolve through four
  complete literal descriptors, each holding opener + closer as one entry. Tailwind strings
  stay whole literals; sidebar ids and names unchanged so DB widget assignments survive.
  **Minimum defensible alternative** if the team declines: `$after_title = $before_title ? '</div>' : '';`
- **`F14` — six enum sanitizers restate their own `choices`.** `inc/customizer.php:405-457`;
  each duplicates the keys registered for the same setting (`header_variant`, `bar_style`,
  `footer_style`, `hero_style`, `card_style`, `checkout_style`). **No drift today** — all six
  match. The strength of the finding is that the file already demonstrates the right pattern:
  `probo_sanitize_title_font`/`_body_font` derive from `probo_font_choices()`.
  **Fix:** the `$add` closure (`:49-70`) already receives both `$control['choices']` and the
  sanitizer — have it default to a generated `isset($choices[$v]) ? $v : $defaults[$key]`.
  Keep the six as thin wrappers for one release (they are theme-global; a child theme could
  reference one), or confirm no external callers.
- **`E5` — `isset()` on a function-call result.** `inc/customizer.php:379`, `:389` —
  `isset( probo_font_choices( 'title' )[ $value ] )` calls the function on every sanitize pass
  and, if `$value` arrives as an array from a crafted Customizer POST, yields a silent `false`
  rather than a validation error. Assign to a variable and use
  `is_string( $value ) && isset( $choices[ $value ] )`. **Fold into `F14`** — same file, same pass.
- **`F19` — bento-grid and logo-reel hand-roll `shared/repeater.js`'s row chrome.** Three
  copies (`blocks/shared/repeater.js:129-205`, `bento-grid/index.js:40-94`,
  `logo-reel/index.js:24-78`): `move()` is byte-identical apart from a variable name, the row
  wrapper style object is identical, and the up/down/trash `Button` triple with its
  `disabled: index === 0` / `index === list.length - 1` guards is repeated verbatim.
  **Latent shared hazard:** `move()` is only safe *because* those flags are correct in all
  three — `splice(index + offset, …)` with `index === 0, offset === -1` computes `splice(-1, …)`
  and inserts before the *last* element rather than throwing. That invariant is maintained
  independently in three places.
  **Fix:** export `probo.rowList({ items, onChange, renderFields, addLabel })` from
  `repeater.js`, re-express `RepeaterControl` on top of it (so the three line-format blocks
  need no edit), and add `'probo-block-repeater'` to the two blocks' `index.asset.php` deps.
  **Risk:** `repeater.js:239-241` deliberately *returns* `el(RepeaterControl, config)` rather
  than calling it, so hooks belong to it and not the host `edit` — `rowList` must do the same
  or the two blocks throw hook-order errors on add/remove. `logo-reel` has a second mutation
  path (`index.js:115-131`), so `rowList` must be controlled and must not cache `items`.
- **`E8` — `initFlyouts()` caches "coarse pointer" once at load.** `assets/js/theme.js:63`
  evaluates `matchMedia('(hover: none)').matches` at load; on a hybrid/touchscreen laptop the
  value can change and the tap-to-open behaviour stays wrong for the session.
  **Fix:** read `.matches` inside the click handler, as `desktop.matches` already is on the
  next line. **Fold into P0-5** — same file, same pass.
- **`E9` — Escape on the products megamenu does not return focus.** `theme.js:161-165` closes
  the panel but leaves focus inside now-hidden content. `initFlyouts()` gets this right
  (`:117-129`); mirror it — on Escape, if `panel.contains(document.activeElement)`, call
  `toggle.focus()`. **Fold into P0-5.**
- **`E4` — shipping-rate meta keys dropped.** `inc/woocommerce.php:470` (and `:988`) maps
  `wp_strip_all_tags` over `$rate->get_meta_data()` and implodes the **values** only. A rate
  with `items => "3 items"` reads fine; one with `pickup_location => "Dokkum"` renders as a
  bare "Dokkum" with no indication of what it is. Format as `key: value` (as core's
  `woocommerce_after_shipping_rate` markup does), or document that only value-only meta is
  supported.
- **`E10` — `shop-tip` sidebar has no render-once guard.** Rendered at
  `woocommerce/single-product.php:104-106` and `archive-product.php:41-45`. Not a bug today
  (different pages), but the sidebar is registered once with no guard. Document where it
  appears, in a comment on the registration in `functions.php:78-80`.
- **Docblock nit:** `probo_checkout_steps()` (`inc/woocommerce.php:781`) declares
  `array{title,intro}` but the array also carries `short` and `next`, both consumed
  (`inc/template-tags.php:601`, `form-checkout.php:91-95`). Fix inside whichever change lands
  there — most likely P1-1 or P1-2.

### Sweep-or-nothing / decline

- **`F13` — USP list derived in several places across three idioms.** Byte-identical 7-line
  `array_filter(array(probo_get('topbar_usp_1'), …))` at `woocommerce/cart/cart-totals.php:16-22`
  and `woocommerce/single-product.php:79-85`; a second idiom at `inc/template-tags.php:630`;
  a third inline key loop in `header.php:44-48` (which legitimately differs — it needs `$key`
  for `data-probo-partial` and `$index` for the responsive class). The key triple is also
  literal at `inc/customizer.php:204`, `:334` and `inc/settings.php:34-36`.
  **Conditional by its own terms:** introduce `probo_usps()` in `inc/template-tags.php` and
  migrate **every** call site in one change, or **do not start** — a partial migration adds a
  fourth idiom and the payoff drops to line count. Coordinate with P0-9, which rewrites the
  header loop.
- **`F20` — four RangeControl resets restate a `block.json` default.** `hero/index.js:106`
  (`55` vs `block.json:26`), `bento-grid/index.js:192` (`240` vs `:15`),
  `logo-reel/index.js:182` (`32` vs `:15`), `category-grid/index.js:115` (`4` vs `:24`).
  Change `block.json` and the editor's reset silently restores the old value. Range *bounds*
  are correct on both sides — only the reset default is duplicated.
  **Fix:** delete the ternaries and pass `value` through; `setAttributes({height: undefined})`
  omits the attribute and both the editor and `prepare_attributes_for_render()` fall back to
  `block.json`. **Tradeoff:** after a reset the control renders in its empty state until
  reload (the preview stays correct — the server supplies the default).
  **If that UI difference is unwanted, decline this finding rather than half-fixing it** with
  `resetFallbackValue`, which restates the constant again and so does not solve the drift.
- **`E7` — `probo_is_configurable_product()` reads `$GLOBALS['product']` unguarded.**
  `inc/woocommerce.php:236`. The `instanceof` check below already covers it, and the
  nullable-global-fallback parameter is a WooCommerce template convention. **Both audits'
  net verdict: decline.** (Optionally prefer argument-less `wc_get_product()`.)

---

## 6. Documentation and remaining polish

### P3-doc · `F23` — README documents a function that does not exist
`README.md:76` — "Both draw through `probo_callout_tile()` so they cannot drift apart."
**`probo_callout_tile()` exists nowhere in the codebase** (verified by grep across all
`*.php`). The mechanism it describes is real and still correct — both the category's own
callout and the block's own callout do render through one shared path — but that path is now
`probo_callout_render()` (`inc/category-callout.php:373`) via `probo_callout_locate_template()`.
The function was renamed when callouts became file-based templates; the README was not.
This matters more than a typo: the sentence is the *justification* for why the two callout
kinds cannot diverge, so a maintainer grepping for the named guarantee finds nothing and may
conclude it was removed.
**Also stale:** `README.md:309-311` lists `screenshot.png` under "Not included" and asks for
one before shipping — the file exists (453,861 bytes).
**Fix:** rename to `probo_callout_render()` in that sentence; drop the screenshot bullet.
Doc-only, minutes. **P0-grade cheapness, P3-grade urgency — bundle it with any batch.**

### `E2` — two callout templates share the label "Banner"
`templates/callouts/category_top/top.php:3` and `templates/callouts/category_bottom/bottom.php:3`
both declare `Callout Template: Banner`. The picker groups by placement so they are
distinguishable in context, but the two files are otherwise byte-identical duplicates — any
fix to one must be remembered for the other.
**Fix:** label them distinctly ("Banner — above" / "Banner — below") and have `bottom.php`
include a shared partial so the markup exists once.

### `E6` — `probo_callout_normalize_row()` leaks legacy fallbacks that can never match
`inc/category-callout.php:496-501`. When a row has no `template` it assigns the pre-file
slugs `'tile'` / `'band'`, translated at `:507` by `probo_callout_resolve_template_key()`.
That works, but the map at `:246-256` is the only thing keeping it alive: if someone filters
`probo_callout_legacy_template_map` to remove an entry the fallback silently becomes "first
template discovered", which may be a `grid` tile where a band was meant.
**Fix:** resolve directly to `'category_top/top'` / `'grid/callout'`, keeping the legacy map
only for *stored* values. **Fold into P2-14** — same function, same pass.

## 7. Execution batches

Batches are disjoint by file so they can run in parallel. Sequencing constraints are the
only hard ordering.

| Batch | Items | Files touched |
|---|---|---|
| **B1** | P0-1, P0-2 | `blocks/category-grid/render.php`, `blocks/logo-reel/render.php` |
| **B2** | P0-3, P0-9, P0-10 | `inc/template-tags.php`, `template-parts/header-ruim.php`, `template-parts/header-compact.php` |
| **B3** | P0-4, P0-8 | `woocommerce/single-product.php`, `inc/woocommerce.php` (rate-fold label only) |
| **B4** | P0-5, `E8`, `E9` | `assets/js/theme.js` |
| **B5** | P0-6, P0-7, P0-11 | `functions.php`, `assets/css/src/theme.css` |
| **B6** | P1-3 + P1-4 | `inc/template-tags.php` (menu fallback), `assets/css/src/theme.css` (dead `self` rules) |
| **B7** | P1-5 (`F8`→`F10`→`F9`) then P1-8 (`B3`) | `inc/settings.php`, `inc/dynamic-css.php`, `blocks/hero/render.php` |
| **B8** | P1-1, then P2-4 | `inc/woocommerce.php`, `woocommerce/checkout/form-checkout.php` |
| **B9** | P1-2 | `assets/js/checkout-steps.js` |
| **B10** | P1-6, P1-7 | `template-parts/header-*.php` + `inc/template-tags.php` (badge helper), `inc/category-callout.php` |
| **B17** | P2-13 (`C5`), `F23`, `E2` | `.gitignore`, `README.md`, `templates/callouts/**` |
| **B18** | P2-14 (`F3`) + `E6`, then P2-15 (`D4`) | `inc/category-callout.php` |
| **B11** | P2-5, P2-6, P2-7, P2-8, P2-9, P2-11 | `inc/woocommerce.php`, `functions.php`, `.github/`, `assets/css/src/theme.css`, `woocommerce/archive-product.php` |
| **B12** | P2-1 (i18n, one pass) | `languages/`, `assets/js/checkout-steps.js`, `blocks/*/block.json`, `package.json`, `bin/` |
| **B13** | P2-2 (`theme.json`) | `theme.json`, `functions.php` |
| **B14** | P2-3 (responsive images) | all `blocks/*/render.php`, `inc/template-tags.php` |
| **B15** | P2-10, `F14`, `E5` | `inc/customizer.php`, new `assets/js/customize-preview.js` |
| **B16** | `E1` + `F18` — **land last** | `inc/blocks.php`, all 8 `blocks/*/render.php` |

**Hard ordering:** B7 internal (`F8` → `F10` → `F9` → `B3`); B8 internal (P1-1 → P2-4);
B18 internal (`F3`+`E6` → `D4`); B16 after B1 and B14.
**File conflicts — run these groups sequentially, not in parallel:**
B2 / B6 / B14 all touch `inc/template-tags.php`; B3 / B8 / B10 / B11 all touch
`inc/woocommerce.php`; B1 / B14 / B16 all touch `blocks/*/render.php`; B10 / B18 both
touch `inc/category-callout.php`; B2 / B10 both touch `template-parts/header-*.php`;
B5 / B6 / B11 all touch `assets/css/src/theme.css`.

## 8. Global validation checklist

Applies to every batch — there is no test suite to lean on.

- [ ] `php -l` on every changed PHP file.
- [ ] `npm run check:templates` after any `woocommerce/**` edit (P0-4, P2-9) — and remember
      it covers **nothing else**.
- [ ] `npm run build` after any Tailwind class or `@source` change (P0-2, P0-7, P2-3, P2-8);
      confirm new class literals survive into `assets/css/theme.css`.
- [ ] Escaping unchanged: every new `echo` through `esc_html`/`esc_attr`/`esc_url`/`wp_kses_post`.
- [ ] No public function signature changed without a checked call-site sweep —
      `probo_hero_tokens`, `probo_block_wrapper`, the six enum sanitizers and
      `probo_dropped_style_handles` are theme-global and the plugin may call them.
- [ ] Persisted state migrations named and handled: `F2` (sessionStorage), `F20` (block
      attributes in post content), `F14` (theme_mods).
- [ ] Manual smoke: home, category archive, product, cart, checkout (both Connect and
      WooCommerce-rate paths), a blog post, a page, 404, search results — at mobile and
      desktop widths, with and without a menu assigned to `primary`.
