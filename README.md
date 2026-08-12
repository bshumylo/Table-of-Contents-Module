# Table of Contents (Joomla! module)

[Українською](README.uk.md)

A native Joomla! 6 site module that automatically builds a **table of contents** from the headings of the article being viewed. The list is assembled in the browser from the rendered article, so it works with any editor, any third-party content plugin and any heading markup — nothing is stored, nothing is parsed server-side.

## Features

- Table of contents built client-side from the article's own headings, so it always matches what the reader actually sees.
- Configurable heading levels (`H2`–`H6`) and a minimum number of headings below which the module hides itself completely — including its chrome.
- Three placements independent of the assigned module position: at the top of the article, floating to its left, or floating to its right.
- Optional numbered list, collapsible box (optionally collapsed on load), and smooth scrolling with a configurable offset for sticky site headers.
- Appearance options: width, background, border, text and link colours, font size, extra CSS class, and a per-instance Custom CSS field.
- Adjustable article-content CSS selector for templates that do not use standard Joomla article markup.
- No external dependencies and no remote requests — plain Joomla web assets (DI service provider, module dispatcher, overridable layout).
- Ships with `en-GB` and `uk-UA` translations.

## Requirements

- Joomla! 6.0 or later
- PHP 8.3+

## Installation

1. Download the latest `mod_toc-x.y.z.zip` from the [Releases](../../releases) page.
2. In the Joomla administrator go to **System → Install → Extensions** and upload the zip.
3. Go to **Content → Site Modules**, create a new **Table of Contents** module, assign it to a position and to the menu items with articles.

The module renders only on `com_content` article views; on any other page it produces no output at all.

## Configuration

### Basic

| Option | Default | Description |
| --- | --- | --- |
| Title | *(empty)* | Heading above the list. Empty = the default title. |
| Position | Floating, right of the article | `At the top of the article`, `Floating, left`, `Floating, right` — independent of the module position. |
| Heading levels | H2, H3, H4 | Which heading tags are picked up. |
| Minimum headings to show | 2 | The module hides itself when the article has fewer matching headings. |
| Numbered list | No | Numbers the entries instead of plain links. |
| Collapsible | Yes | Adds a toggle to the box. |
| Collapsed by default | No | Starts collapsed (only with *Collapsible*). |
| Smooth scrolling | Yes | Animated jump to the heading. |
| Scroll offset (px) | 20 | Vertical offset applied when jumping, for sticky headers. |

### Appearance

| Option | Default | Description |
| --- | --- | --- |
| Width | `280px` | Box width for the Left / Right positions. |
| Background colour | `#f8f9fa` | Box background. |
| Border colour | `#dee2e6` | Box border. |
| Text colour | *(empty)* | Inherited from the template when empty. |
| Link colour | *(empty)* | Inherited from the template when empty. |
| Font size | *(empty)* | e.g. `14px` or `0.9rem`; inherited when empty. |
| Custom CSS class | *(empty)* | Extra class on the wrapper. |
| Custom CSS | *(empty)* | Raw declarations applied to this module instance only — no selector and no braces needed. |

### Advanced

| Option | Default | Description |
| --- | --- | --- |
| Article content CSS selector | `.com-content-article__body, [itemprop='articleBody'], .item-page .article-content, .article-content, .uk-article [property='text']` | Selector matching the article body container. Adjust only for non-standard templates. |
| Alternative layout | `default` | Standard Joomla module layout selection. |

If the selector matches nothing usable, the module finds the article body on its own: it walks up from the first heading that is not part of the site header, footer, navigation, sidebar or another module, and stops at the first ancestor holding enough headings. Headings belonging to the page rather than the article — a related-posts block, for instance — are never picked up.

### Page builders (YOOtheme Pro)

On a YOOtheme Pro builder layout the article body has no stable CSS hook, so the automatic detection above is what places the table of contents. Two things are worth knowing:

- YOOtheme Pro does not render the `sidebar` position on builder pages. Assign the module to `top` or `bottom` instead — the module moves itself next to the article anyway, and the now-empty position section is removed from the page.
- The module keeps its YOOtheme panel wrapper (`module-<id>`) when it moves, so the position's styling follows it.

### Template override

The markup can be overridden per template by copying `tmpl/default.php` to:

```
templates/<your-template>/html/mod_toc/default.php
```

## Security note

The **Custom CSS** field is stored unfiltered by design — CSS is not HTML and Joomla's string filter would mangle valid declarations. The value is sanitised on every render before it is echoed: selector break-outs, braces, comments, at-rules and the legacy script-in-CSS vectors are stripped, so an operator with edit rights on the module cannot inject markup or script into the page.

## Changelog in the administrator

The manifest declares a `<changelogurl>`, so the version number in **System → Manage → Extensions** is a button that opens the release notes for the installed version. The data is read from [`changelog.xml`](changelog.xml) in this repository. `<updateservers>` points at [`update.xml`](update.xml), so Joomla also offers in-place updates under **System → Update → Extensions**.

## Development & testing

- Unit tests: `vendor/bin/phpunit -c phpunit.xml.dist` (needs Joomla's vendor autoloader; set `JOOMLA_ROOT` to a Joomla installation, or install `joomla/registry` locally).
- Build the installable zip: `bash build/build.sh` (or `powershell -File build/build.ps1` on Windows) — output lands in `dist/`.

## License

[GNU General Public License v2.0 or later](LICENSE) — © 2026 Bohdan Shumylo.
