/**
 * @file
 * Interactions for court finder search. Most of this is file is to provide
 * keyboard navigation for the "Court name" search functionality (where the JS
 * is beyond broke).
 */
((Drupal, once, $) => {

  /**
   * Returns the key of a value if it exists in an object. This is used to find
   * the position of the currently selected item within the jQuery collection of
   * items.
   *
   * @param {Object} object
   * @param {*} value
   * @returns Number
   */
  function getKeyByValue(object, value) {
    return parseInt(Object.keys(object).find(key => object[key] === value));
  }

  /**
   * Handles the keyup event from the court finder location input.
   *
   * @param {Event} e - The keyup event object.
   */
  function handleKeyup(e) {
    const $menu = $('[data-input-ref="usc_court_finder_search_homepage"]');
    const $currentItem = $menu.find('li.ui-state-active');
    const $selectableItems = $menu.find('li.ui-menu-item:has(> a)');

    /**
     * Direction the selection will travel.
     */
    let direction = '';

    /**
     * The element that will be selected after the keyup.
     */
    let $newSelection = false;

    switch (e.originalEvent.key) {
      case 'ArrowUp':
        direction = 'up';
        break;
      case 'ArrowDown':
        direction = 'down';
        break;
      case 'Enter':
        const formPath = e.currentTarget.closest('form').getAttribute('action');
        const key = e.currentTarget.getAttribute('name');
        const value = e.currentTarget.value

        window.location.href = `${formPath}?${key}=${value}`;
        break;
    }

    if (direction === 'up') {
      if ($currentItem.length) {
        if ($currentItem.is($selectableItems.first())) {
          $newSelection = $selectableItems.last();
        }
        else {
          const positionInSelectableItems = getKeyByValue($selectableItems, $currentItem[0]);
          $newSelection = $($selectableItems[positionInSelectableItems - 1]);
        }
      }
      else {
        $newSelection = $selectableItems.last();
      }
    }

    if (direction === 'down') {
      if ($currentItem.length) {
        if ($currentItem.is($selectableItems.last())) {
          $newSelection = $selectableItems.first();
        }
        else {
          const positionInSelectableItems = getKeyByValue($selectableItems, $currentItem[0]);
          $newSelection = $($selectableItems[positionInSelectableItems + 1]);
        }
      }
      else {
        $newSelection = $selectableItems.first();
      }
    }

    if ($newSelection.length) {
      $('.ui-state-active', $menu).removeClass('ui-state-active');
      $newSelection.addClass('ui-state-active');
      e.currentTarget.value = $newSelection.text();
      $menu.find('.ui-state-active')[0].scrollIntoView({
        behavior: 'instant',
        block: 'nearest',
        inline: 'nearest'
      });
    }
  }

  /**
   * Triggers on jQueryUI Autocomplete's `open` event.
   */
  $('#court-finder-by-name').autocomplete({
    open: function () {
      $(this).on('keyup', handleKeyup);
    }
  });

  /**
   * Triggers on jQueryUI Autocomplete's `close` event.
   */
  $('#court-finder-by-name').autocomplete({
    close: function () {
      $(this).off('keyup', handleKeyup);
      $('[data-input-ref="usc_court_finder_search_homepage"]').find('.ui-state-active').removeClass('ui-state-active');
    }
  });

  /**
   * Prevent form submission from refreshing page. Needed to prevent
   * autocomplete's defective JS from interfering.
   */
  $('#court-finder-by-name').parents('form').on('submit', (e) => e.preventDefault());

  /**
   * Initializes switch.
   *
   * @param {Element} header - the toggle element.
   */
  function init(toggle) {
    toggle.querySelectorAll('input').forEach(checkbox => {
      checkbox.addEventListener('click', () => {
        document.querySelector('.court-finder-block').setAttribute('data-active', checkbox.getAttribute('id'));
      });
    });
  }

  Drupal.behaviors.courtFinderSwitch = {
    attach(context) {
      once('court-finder-switch', '.court-finder-block__toggle', context).forEach(init);
    },
  };
})(Drupal, once, jQuery);
