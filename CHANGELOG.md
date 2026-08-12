# Changelog

All notable changes to this project are documented in this file.
The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
