/**
 * @file
 * Initializes the "tocbot" library.
 * @see https://github.com/tscanlin/tocbot
 */
((once, tocbot) => {

  // Where to render the table of contents.
  const tocSelector = '#usc-toc-content';
  // Where to grab the headings to build the table of contents.
  const contentSelector = '#block-uscgov-content';
  // List of any selectors or parent containers where we do not want ToC to list headings.
  const parentIgnoreSelector = [
    '.visually-hidden',
    '.usc-toc__heading'
  ];
  // Which headings to grab inside of the contentSelector element.
  const headingSelector = `:is(h2, h3):not(:is(${parentIgnoreSelector.join()}) *):not(:is(${parentIgnoreSelector.join()}))`;
  // For headings inside relative or absolute positioned containers within content.
  const hasInnerContainers = true;
  // Offset scrolling if toolbar present.
  const headingsOffset = document.body.classList.contains('toolbar-horizontal') ? 80 : 10;
  const scrollSmoothOffset = -1 * headingsOffset;
  const ids = []; // Array of IDs to help ensure no conflicts.
  const onClick = (e) => {
    const activeClassName = 'toc-active-click';
    document.querySelectorAll(`${tocSelector} .${activeClassName}`).forEach(item => item.classList.remove(activeClassName));
    e.target.classList.add(activeClassName);
  }

  /**
   * Format the ID and ensure it's unique.
   *
   * This takes the heading text and formats it into a standard ID, while
   * ensuring that we don't add a duplicate ID.
   *
   * @param {string} - the text that we want to format into the ID
   */
  function formatId(string) {
    const regex = /[^[a-zA-Z-]|\d]/gm; // Matches everything except numbers, letters, and dashes.
    let i = 0;
    let hash = 'id-' + string
      .trim()
      .replaceAll(' ', '-')
      .replaceAll(regex, '')
      .substring(0, 60)
      .toLowerCase();

    // If multiple headings have the same text, or that exact ID already exists
    // somewhere in the DOM, we append a `-n` to it to ensure that our ID is
    // unique.
    while (document.querySelectorAll(`#${hash}`).length || ids.includes(hash)) {
      i++;
      hash = `${hash}-${i}`;
    }

    return hash;
  }

  // All headings must have id prior to initialization.
  function addIdsToHeadings() {
    document.querySelectorAll(`${contentSelector} ${headingSelector}`).forEach((heading) => {
      if (!heading.hasAttribute('id')) {
        const formattedId = formatId(heading.textContent);

        heading.setAttribute('id', formattedId);
        ids.push(formattedId);
      }
    });
  }

  function init() {
    addIdsToHeadings();
    tocbot.init({
      tocSelector,
      contentSelector,
      headingSelector,
      hasInnerContainers,
      headingsOffset,
      scrollSmoothOffset,
      scrollSmooth: false,
      onClick,
    });
  }

  Drupal.behaviors.tocBot = {
    attach(context) {
      once('toc', '#block-uscgov-content', context).forEach(init);
    },
  };
})(once, tocbot);
