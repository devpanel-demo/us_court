/**
 * @file
 * Showing / hiding of the primary menu at mobile widths.
 */
((Drupal, once) => {
  let mobileNavButton;
  let closeButton;
  let menuWrapper;
  let isNavOpen;
  let overlay;

  /**
   * Initializes everything.
   *
   * @param {Element} header - the <header> element.
   */
  function init(header) {
    const desktopNavigationBreakpoint = window.matchMedia('(min-width: 1400px)');
    mobileNavButton = header.querySelector('.header__menu-button');
    closeButton = header.querySelector('.header__menu-wrapper-close-button');
    menuWrapper = header.querySelector('.header__menu-wrapper');
    isNavOpen = () => mobileNavButton.getAttribute('aria-expanded') === 'true';
    overlay = header.querySelector('.header__menu-overlay');

    mobileNavButton.addEventListener('click', () => {
      changeMenuVisibility(!isNavOpen());
    });

    closeButton.addEventListener('click', () => {
      changeMenuVisibility(false);
    });

    overlay.addEventListener('click', () => {
      changeMenuVisibility(false);
    });

    document.addEventListener('keyup', (e) => {
      if (e.key === 'Escape') {
        changeMenuVisibility(false);
      }
    });

    // If browser is resized and there is a change in visibility of
    // desktop/mobile menu, then close all menu items.
    desktopNavigationBreakpoint.addEventListener('change', () => {
      menuWrapper.classList.toggle('is-active', false);
      mobileNavButton.setAttribute('aria-expanded', false);
      Drupal.uscgov.toggleFocusTrap(false);
      Drupal.uscgov.closeAllMenuItems();
    });
  }

  /**
   * Show/hide menu.
   *
   * @param {boolean} toState
   */
  function changeMenuVisibility(toState) {
    const menuWrapperContainsFocus = menuWrapper.contains(document.activeElement);

    menuWrapper.classList.toggle('is-active', toState);
    mobileNavButton.setAttribute('aria-expanded', toState);
    Drupal.uscgov.toggleFocusTrap(toState);

    // Close any open submenus when mobile menu is closed.
    if (toState === false && Drupal.uscgov.isMobileMenuSystem()) {
      Drupal.uscgov.closeAllMenuItems();
    }

    if (Drupal.uscgov.isMobileMenuSystem()) {
      // On opening, set the focus to the first focusable element in the menu.
      if (toState) {
        menuWrapper.addEventListener('transitionend', () => {
          menuWrapper.querySelector(Drupal.uscgov.focusableElementsSelector)?.focus();
        }, { once: true });
      }
      // On close. set focus back to button only if an element within the mobile menu has focus.
      else if (menuWrapperContainsFocus) {
        mobileNavButton.focus();
      }
    }
  }

  Drupal.behaviors.menuWrapper = {
    attach(context) {
      once('menu-wrapper', '.header', context).forEach(init);
    },
  };
})(Drupal, once);
