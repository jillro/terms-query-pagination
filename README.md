<!-- AUTO-GENERATED FROM readme.txt — DO NOT EDIT BY HAND. -->
<!-- Edit readme.txt (synced body) or scripts/readme-parts/{header,footer}.md, then run `npm run readme`. -->

<h1 align="center">Terms Query Pagination Block</h1>

<p align="center"><em>Pagination blocks for the WordPress 6.9 Terms Query block, following the pattern of WordPress Core Query Pagination blocks.</em></p>

<p align="center">
  <img alt="Stable" src="https://img.shields.io/badge/Stable-1.0.0-0a7caf">
  <img alt="WordPress" src="https://img.shields.io/badge/WordPress-6.9%20tested-21759b">
  <img alt="PHP" src="https://img.shields.io/badge/PHP-7.4%2B-777bb4">
  <img alt="License" src="https://img.shields.io/badge/License-GPLv2%20or%20later-green">
</p>

<p align="center">
  <a href="https://wordpress.org/plugins/terms-query-pagination/">WordPress.org</a>
  ·
  <a href="https://github.com/jillro/terms-query-pagination/issues">Report a bug</a>
  ·
  <a href="#changelog">Changelog</a>
</p>

---

## Description
Terms Query Pagination adds pagination controls for the WordPress 6.9 Terms Query block. WordPress Core does not paginate terms natively, so this plugin emulates pagination by computing an offset from the current page and injecting it into the term query while the Terms Query block is rendered.

It mirrors the structure of the Core Query Pagination blocks, providing:

* **Terms Query Pagination** — the container block.
* **Terms Query Pagination Numbers** — a list of page numbers.
* **Terms Query Pagination Previous** — link to the previous page.
* **Terms Query Pagination Next** — link to the next page.

Pagination works with both plain (`?termspage=2`) and pretty (`/{taxonomy}-page/2`) permalinks. The plugin registers a rewrite endpoint per taxonomy and flushes rewrite rules on activation so pretty permalinks work immediately. Enhanced (client-side) pagination is supported through the Interactivity API when enabled on the Terms Query block.

## Installation
1. Upload the plugin files to the `/wp-content/plugins/terms-query-pagination` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the *Plugins* screen in WordPress.
3. In a block theme, edit a template or template part that contains a Terms Query block.
4. Add a **Terms Query Pagination** block inside the Terms Query block and insert the Numbers, Previous and/or Next child blocks.
5. Set a low per-page value on the Terms Query block to see pagination across pages.

## Frequently Asked Questions
### Does this require WordPress 6.9?
Yes. The Terms Query block was introduced in WordPress 6.9, and these pagination blocks build on it.

### Do pretty permalinks work?
Yes. The plugin registers a `{taxonomy}-page` rewrite endpoint and flushes rewrite rules on activation, so URLs like `/{taxonomy}-page/2` work without manually re-saving permalinks. Plain permalinks (`?termspage=2`) also work.

### Does it support client-side (enhanced) pagination?
Yes. When enhanced pagination is enabled on the Terms Query block, the pagination links use the Interactivity API for client-side navigation and prefetching.

## Changelog
### 1.0.0
* Initial release.

---

## Development

```sh
npm install            # install dependencies
npm run build          # build blocks into build/
npm run env:start      # start the wp-env WordPress instance
npm run test:e2e       # run the Playwright e2e suite
```

`readme.txt` is the single source of truth. **Do not edit `README.md` by
hand** — edit `readme.txt` (or the partials in `scripts/readme-parts/`) and
regenerate:

```sh
npm run readme
```

CI runs `npm run readme:check` and fails if the two are out of sync.
