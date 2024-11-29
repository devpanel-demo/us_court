/**
 * @file
 * Search box interaction (at desktop widths).
 */
((Drupal, once) => {
  let desktopSearchButton;
  let searchWrapper;
  let isSearchOpen;
  let keysSearch;
  let submitSearch;

  /**
   * Initializes everything.
   *
   * @param {Element} header - the <header> element.
   */
  function init(header) {
    desktopSearchButton = header.querySelector('.desktop-search__button');
    searchWrapper = header.querySelector('.desktop-search__wrapper');
    isSearchOpen = () => desktopSearchButton.getAttribute('aria-expanded') === 'true';

    keysSearch = searchWrapper.querySelector('.desktop-search__input');
    submitSearch = searchWrapper.querySelector('.desktop-search__submit-button');

    desktopSearchButton.addEventListener('click', () => {
      changeSearchVisibility(!isSearchOpen());
    });

    document.addEventListener('keyup', (e) => {
      if (e.key === 'Escape') {
        changeSearchVisibility(false);
      }
    });

    keysSearch.addEventListener('keyup', handleKeywordsChange, true);
    desktopSearchButton.addEventListener('focusout', handleSearchFocusOut, true);
    searchWrapper.addEventListener('focusout', handleSearchFocusOut, true);

    handleKeywordsChange();
  }

  function handleKeywordsChange() {
    const keys = keysSearch.value;
    if (keys.length < 3) {
      submitSearch.setAttribute('disabled', 'disabled');
    } else {
      submitSearch.removeAttribute('disabled');
    }
  }

  /**
   * Closes the search wrapper when focus shifts away from the search wrapper
   * and search button.
   *
   * @param {Event} e - Focusout event object.
   */
  function handleSearchFocusOut(e) {
    if (!Drupal.uscgov.isMobileMenuSystem()) {
      if (!searchWrapper.contains(e.relatedTarget) && e.relatedTarget !== desktopSearchButton) {
        changeSearchVisibility(false);
      }
    }
  }

  /**
   * Show/hide search.
   *
   * @param {boolean} toState
   */
  function changeSearchVisibility(toState) {
    const searchWrapperContainsFocus = searchWrapper.contains(document.activeElement);

    searchWrapper.classList.toggle('is-active', toState);
    desktopSearchButton.setAttribute('aria-expanded', toState);

    if (toState === false && !Drupal.uscgov.isMobileMenuSystem() && searchWrapperContainsFocus) {
      desktopSearchButton.focus();
    }

    if (toState === true && !Drupal.uscgov.isMobileMenuSystem()) {
      searchWrapper.querySelector('input')?.focus();
    }
  }

  Drupal.behaviors.desktopSearch = {
    attach(context) {
      once('desktop-search', '.header', context).forEach(init);
    },
  };
})(Drupal, once);
