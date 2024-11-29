/**
 * @file
 * Show hide desktop submenus.
 */
((Drupal, once) => {
  /**
   * Closes all menu buttons in a list.
   *
   * @param {NodeList|Array} menuButtons - List of menu buttons to close.
   */
  function closeAllMenus(menuButtons) {
    menuButtons.forEach(el => toggleVisibility(el, false));
  }

  /**
   * Initialize the desktop menu.
   *
   * @param {Element} menu - The top-level primary menu <ul>.
   */
  function init(menu) {
    const menuButtons = menu.querySelectorAll('.primary-menu__button');
    const menuIsOpen = currentButton => currentButton.getAttribute('aria-expanded') === 'true';

    menuButtons.forEach(button => {
      button.closest('.primary-menu__menu-item--level-0').addEventListener('focusout', (e) => {
        if (!Drupal.uscgov.isMobileMenuSystem()) {
          const parentItem = e.currentTarget.closest('.primary-menu__menu-item--level-0');
          if (!parentItem.contains(e.relatedTarget)) {
            toggleVisibility(button, false);
          }
        }
      }, true);

      button.addEventListener('click', () => {
        if (!Drupal.uscgov.isMobileMenuSystem()) {
          const toState = !menuIsOpen(button);

          // Safari doesn't focus <button> elements on 'click', so we have to
          // manually ensure all other menu items are closed.
          closeAllMenus(menuButtons);
          toggleVisibility(button, toState);
        }
      });
    });

    // Ensure menu closes when clicking outside. This is necessary for Safari.
    document.addEventListener('click', (e) => {
      if (!Drupal.uscgov.isMobileMenuSystem()) {
        if (!menu.contains(e.target)) {
          closeAllMenus(menuButtons);
        }
      }
    });
  }

  /**
   * Toggle visibility of a button's megamenu.
   *
   * @param {Element} button - the button that will have its submenu expanded.
   * @param {boolean} toState - State where the submenu will end up.
   */
  function toggleVisibility(button, toState) {
    button.setAttribute('aria-expanded', toState)
    const menuItem = button.closest('.primary-menu__menu-item--level-0');

    if (toState === false && menuItem.contains(document.activeElement)) {
      button.focus();
    }
  }

  Drupal.behaviors.showHideDesktopDropdown = {
    attach(context) {
      once('show-hide-desktop-dropdown', '.primary-menu--level-0', context).forEach(init);
    },
  };
})(Drupal, once);
