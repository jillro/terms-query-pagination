<!--
  Hand-written, GitHub-only content placed at the bottom of README.md.
  Good place for contributor-facing docs that don't belong on WordPress.org.
  Edit freely — it is copied verbatim into README.md by `npm run readme`.
-->

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
