# Changelog

All notable changes to this project are documented in this file.
The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.2.0] - 2026-08-12

### Changed

- **Appearance follows the template.** The *Background colour*, *Border colour*, *Text colour*, *Link colour* and *Font size* parameters are gone. The box inherits background, text colour, link colour and font size from the template around it, so it fits a light and a dark design without being configured; only the border keeps a neutral grey fallback so the box stays outlined. The module no longer writes an inline `style` attribute at all.
- **Custom CSS is now the documented styling hook.** The field's description lists the custom properties the module reads — `--toc-bg`, `--toc-border`, `--toc-text`, `--toc-link`, `--toc-font-size` — and the field itself carries a placeholder showing them in place, so the replacement for the removed colour pickers is visible where it is needed. A note at the top of the *Appearance* tab says where the styling comes from.

### Removed

- `bg_color`, `border_color`, `text_color`, `link_color` and `font_size` module parameters and their language strings, together with the inline style the module used to emit. Values already saved for existing modules are ignored, so nothing has to be cleaned up before updating.

### Note

- A module whose colours were set through the removed pickers now takes the template's colours instead. To keep the old look, paste the corresponding custom properties into Custom CSS.

## [1.1.0] - 2026-08-12

### Changed

- **Placement is now Joomla's own module position.** The *Position* parameter (top of the article / floating left / floating right) is gone, along with the script that moved the module next to the article body: it duplicated the module Position selector every Joomla module already has, and the DOM move was the source of the wrapper and title problems fixed in 1.0.3. The module renders where the template renders it; assign it to the position you want it in.
- **The box no longer carries a width.** The fixed *Width* parameter (`280px`) is gone; the module fills the position it sits in (`width: 100%`, plus `flex: 1 1 auto` and `min-width: 0` so it also behaves as a flex or grid item inside a builder panel or a card grid). Size it from the position, the template, or the module's Custom CSS field.

### Removed

- `position` and `width` module parameters, the `--toc-width` custom property, the `mod-toc--top` / `mod-toc--left` / `mod-toc--right` and `mod-toc-holder*` CSS classes, and their language strings. Values already saved for existing modules are ignored, so nothing has to be cleaned up before updating.

### Note

- Modules that relied on the floating placement now appear in their assigned template position instead. To keep a table of contents beside the article text, assign the module to a sidebar position of the template.

## [1.0.3] - 2026-08-12

### Fixed

- Unreadable box on dark templates. `bg_color` defaulted to the near-white `#f8f9fa` while text and link colours are inherited from the template, so a dark template put light text on a light box. `bg_color` and `border_color` now default to empty: the box takes the template's own background, and the border falls back to a neutral `rgba(128,128,128,0.4)` that stays visible on light and dark alike. Joomla substitutes a field's manifest default whenever the value is cleared, so this could not be fixed from the module's own settings — the default itself had to change.
- Existing module instances keep the colours already saved for them; clear the Background and Border colour fields to adopt the new behaviour.
- Orphaned module title when the module moved out of a wrapper the chrome selectors did not recognise. YOOtheme Pro's Builder *Module* element renders a bare `<div class="uk-panel">` holding the title and the module, so the title stayed behind in the layout column. A parent is now treated as the module's own wrapper when it holds nothing but this module, its title and the `noscript` fallback — tight enough that a position wrapper shared with other modules is never swallowed.

## [1.0.0] - 2026-08-12

### Added

- Initial release for Joomla! 6.
- Table of contents built client-side from the headings of the article being viewed.
- Configurable heading levels (H2–H6) and a minimum heading count below which the module hides itself entirely.
- Three placements independent of the module position: top of the article, floating left, floating right.
- Optional numbered list, collapsible box (optionally collapsed on load), smooth scrolling with a configurable scroll offset.
- Appearance options: width, background, border, text and link colours, font size, custom wrapper class, per-instance Custom CSS.
- Adjustable article-content CSS selector, plus automatic detection of the article body when the selector matches nothing: the module walks up from the first content heading to the first ancestor holding enough headings. This is what makes it work on page builder layouts that emit no recognisable article markup, YOOtheme Pro builder pages in particular.
- Module chrome recognised for templates that wrap modules in a `card`, a `moduletable` or a `module-<id>` element, so the whole box moves next to the article; wrappers left empty by the moved or hidden module are removed with it.
- Headings in the site header, footer, navigations, sidebars and other modules are never mistaken for article headings.
- Overridable layout (`tmpl/default.php`) and alternative module layout support.
- `en-GB` and `uk-UA` translations.
- Changelog shown in the administrator via `<changelogurl>`, and in-place updates via `<updateservers>`.
- PHPUnit test suite and a Docker live-install test run.

### Security

- The `filter="raw"` Custom CSS parameter is sanitised on every render: selector break-outs, braces, comments, at-rules and legacy script-in-CSS vectors are stripped before output.

### Verified on

- Joomla! 6.1.2 with Cassiopeia.
- Joomla! 6.1.2 with YOOtheme Pro 5.0.39, both the plain article template and a Builder layout.
