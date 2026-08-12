if (!window.Joomla) {
  throw new Error('Joomla API was not properly initialised');
}

/**
 * Turns a heading's text into a unique, URL-safe id.
 */
function tocSlugify(text, used) {
  let base = text
    .toString()
    .trim()
    .toLowerCase()
    .replace(/\s+/g, '-')
    .replace(/[^\p{L}\p{N}-]+/gu, '')
    .replace(/-+/g, '-')
    .replace(/^-+|-+$/g, '');

  if (!base) {
    base = 'section';
  }

  let slug = base;
  let i = 2;

  while (used.has(slug)) {
    slug = `${base}-${i}`;
    i += 1;
  }

  used.add(slug);

  return slug;
}

/**
 * Groups a flat list of heading elements into a nested tree based on
 * heading level (h2 > h3 > h4 ...), independent of which levels are
 * actually enabled.
 */
function tocBuildTree(headings) {
  const root = { level: 1, children: [] };
  const stack = [root];

  headings.forEach((heading) => {
    const level = parseInt(heading.tagName.substring(1), 10);
    const node = { level, heading, children: [] };

    while (stack.length > 1 && stack[stack.length - 1].level >= level) {
      stack.pop();
    }

    stack[stack.length - 1].children.push(node);
    stack.push(node);
  });

  return root.children;
}

/**
 * Builds the link list. Heading text is inserted as text, never as markup,
 * so article content can never turn into executable HTML here.
 */
function tocRenderList(nodes, listTag) {
  const list = document.createElement(listTag);
  list.className = 'mod-toc__list';

  nodes.forEach((node) => {
    const li = document.createElement('li');
    li.className = 'mod-toc__item';

    const a = document.createElement('a');
    a.href = `#${encodeURIComponent(node.heading.id)}`;
    a.className = 'mod-toc__link';
    a.textContent = node.heading.textContent.trim();
    li.appendChild(a);

    if (node.children.length) {
      li.appendChild(tocRenderList(node.children, listTag));
    }

    list.appendChild(li);
  });

  return list;
}

/**
 * Wrappers a template may put around a module: Cassiopeia's card, the
 * classic moduletable chrome, and the generic `module-<id>` div used by
 * YOOtheme Pro and others.
 */
const TOC_CHROME = '.card, .moduletable, [class*="moduletable"], [id^="module-"]';

/**
 * Page regions whose headings never belong to the article body.
 */
const TOC_NOT_CONTENT = 'header, footer, nav, aside, .mod-toc, ' + TOC_CHROME;

/**
 * The element that should be moved next to the article: the module chrome
 * wrapper if the template rendered one, otherwise the module markup itself.
 * Moving only the inner element would leave an empty card behind.
 */
function tocMovableElement(container) {
  const wrapper = container.closest(TOC_CHROME);

  return wrapper && wrapper !== container ? wrapper : container;
}

/**
 * The headings of the given root that carry text.
 */
function tocHeadings(root, levels) {
  return Array.from(root.querySelectorAll(levels.join(',')))
    .filter((heading) => heading.textContent.trim() !== '');
}

/**
 * The same list, minus everything that sits in a page region rather than in
 * the article body — module wrappers (the TOC's own included), the header,
 * the footer, navigations and sidebars.
 */
function tocContentHeadings(root, levels) {
  return tocHeadings(root, levels)
    .filter((heading) => heading.closest(TOC_NOT_CONTENT) === null);
}

/**
 * Fallback for templates whose article markup the configured selector does
 * not describe — page builders such as YOOtheme Pro emit generic wrappers
 * with no stable hook. Starting at the first content heading, walk up until
 * an ancestor holds enough headings to build a table of contents from; that
 * element is the article body for all practical purposes.
 */
function tocDetectArticle(levels, minItems, movable) {
  const headings = tocContentHeadings(document.body, levels);

  if (headings.length < minItems) {
    return null;
  }

  let element = headings[0].parentElement;

  for (let i = 0; i < 8 && element && element !== document.body; i += 1) {
    // Once the candidate spans page structure it is the page, not the
    // article — a second article's headings would be pulled in from a
    // related-posts block or another module.
    if (
      element.tagName === 'MAIN'
      || element.contains(movable)
      || element.querySelector('header, footer, main, nav') !== null
    ) {
      return null;
    }

    if (tocContentHeadings(element, levels).length >= minItems) {
      return element;
    }

    element = element.parentElement;
  }

  return null;
}

/**
 * Drop the wrappers the moved module left behind. A template position is
 * often a section plus a container plus the module chrome, all of which
 * would otherwise stay on the page as empty boxes with their own spacing.
 */
function tocPruneEmpty(node) {
  let element = node;

  for (let i = 0; i < 5 && element && element.parentElement; i += 1) {
    if (
      element.children.length
      || element.textContent.trim() !== ''
      || ['BODY', 'MAIN', 'ARTICLE'].indexOf(element.tagName) !== -1
    ) {
      return;
    }

    const parent = element.parentElement;

    element.remove();
    element = parent;
  }
}

/**
 * Removes the module (chrome included) from the page.
 */
function tocRemove(container) {
  const movable = tocMovableElement(container);
  const origin  = movable.parentElement;

  movable.remove();

  if (origin) {
    tocPruneEmpty(origin);
  }
}

function tocInit(container, config) {
  const levels = Array.isArray(config.levels) && config.levels.length
    ? config.levels
    : ['h2', 'h3', 'h4'];
  const minItems = config.minItems || 1;
  const movable  = tocMovableElement(container);

  let article = null;

  // The selector comes from a module param and may be anything at all.
  try {
    article = document.querySelector(config.selector);
  } catch (e) {
    article = null;
  }

  let headings = article ? tocContentHeadings(article, levels) : [];

  // Nothing usable behind the selector: find the article body ourselves.
  if (headings.length < minItems) {
    const detected = tocDetectArticle(levels, minItems, movable);

    if (detected) {
      article = detected;
      headings = tocContentHeadings(detected, levels);
    }
  }

  if (!article || headings.length < minItems) {
    tocRemove(container);

    return;
  }

  const used = new Set(
    Array.from(document.querySelectorAll('[id]')).map((el) => el.id)
  );

  headings.forEach((heading) => {
    if (heading.id) {
      used.add(heading.id);
    } else {
      heading.id = tocSlugify(heading.textContent, used);
    }
  });

  const nav = container.querySelector('.mod-toc__nav');

  if (!nav) {
    tocRemove(container);

    return;
  }

  const tree = tocBuildTree(headings);
  const listTag = config.numbered ? 'ol' : 'ul';
  nav.appendChild(tocRenderList(tree, listTag));

  if (config.smoothScroll) {
    nav.addEventListener('click', (event) => {
      const link = event.target.closest('a');

      if (!link) {
        return;
      }

      const target = document.getElementById(
        decodeURIComponent(link.getAttribute('href').slice(1))
      );

      if (!target) {
        return;
      }

      event.preventDefault();

      const offset = Number.isFinite(config.scrollOffset) ? config.scrollOffset : 0;
      const top = target.getBoundingClientRect().top + window.scrollY - offset;

      window.scrollTo({ top, behavior: 'smooth' });

      if (window.history && window.history.pushState) {
        window.history.pushState(null, '', `#${encodeURIComponent(target.id)}`);
      }
    });
  }

  const origin = movable.parentElement;

  if (config.position === 'top') {
    article.insertBefore(movable, article.firstChild);
  } else {
    article.parentNode.insertBefore(movable, article);
  }

  if (origin) {
    tocPruneEmpty(origin);
  }

  container.classList.add('mod-toc--ready');
}

function tocInitAll() {
  const options = Joomla.getOptions('mod_toc', {});

  Object.keys(options).forEach((id) => {
    const container = document.getElementById(id);

    if (container) {
      tocInit(container, options[id]);
    }
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', tocInitAll);
} else {
  tocInitAll();
}
