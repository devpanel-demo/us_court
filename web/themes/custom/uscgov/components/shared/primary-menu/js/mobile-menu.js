/**
 * @file
 * Mobile menu interactions.
 */
((Drupal, once) => {
  /**
   * Initialize the mobile menu.
   *
   * @param {Element} menu - The top-level primary menu <ul>.
   */
  function init(menu) {
    const menuButtons = menu.querySelectorAll('.primary-menu__button');
    const backButtons = menu.querySelectorAll('.primary-menu__mobile-back-button');

    menuButtons.forEach(menuButton => {
      menuButton.addEventListener('click', () => {
        if (Drupal.uscgov.isMobileMenuSystem()) {
          expandMenu(menuButton);
        }
      });
    });

    backButtons.forEach(backButton => {
      if (backButton.closest('.primary-menu__level-2-wrapper')) {
        parentMenuText =
          backButton
            .closest('.primary-menu__dropdown')
            .querySelector('.primary-menu__dropdown-title')
            .textContent
            .trim();
        backButton.innerHTML = `
          <span class="visually-hidden">${ Drupal.t('Back to') }</span>
          ${ parentMenuText }
        `;
      }

      backButton.addEventListener('click', () => {
        if (Drupal.uscgov.isMobileMenuSystem()) {
          navigateToParent(backButton);
        }
      });
    });
  }

  /**
   * Expands the submenu controlled by passed in <button> element and sets focus
   * to the first focusable element within the submenu.
   *
   * @param {Element} button - The clicked button element that will be expanded.
   */
  function expandMenu(button) {
    const subMenu = document.getElementById(button.getAttribute('aria-controls'));

    button.setAttribute('aria-expanded', true);
    adjustFocusTrap(button);

    subMenu.addEventListener('transitionend', () => {
      subMenu.querySelector(Drupal.uscgov.focusableElementsSelector).focus();
    }, { once: true });
  }

  /**
   * Closes the submenu that is controlled by a passed in <button> element.
   *
   * @param {Element} backButton
   */
  function navigateToParent(backButton) {
    const closestMenuItem = backButton.closest('.primary-menu__menu-item');
    const parentMenuButton = closestMenuItem.querySelector('.primary-menu__button');

    parentMenuButton.setAttribute('aria-expanded', false);
    adjustFocusTrap();
    parentMenuButton.focus();
  }

  /**
   * Returns the currently visible submenu in the mobile menu.
   *
   * @returns {Element|boolean}
   */
  function getActiveSubMenu() {
    let activeSubmenu = false;
    const secondaryActiveSubmenu = document.querySelector('[aria-expanded="true"] ~ .primary-menu__dropdown');
    const tertiaryActiveSubmenu = document.querySelector('[aria-expanded="true"] ~ .primary-menu__level-2-wrapper');

    if (tertiaryActiveSubmenu) {
      activeSubmenu = tertiaryActiveSubmenu;
    }
    else if (secondaryActiveSubmenu) {
      activeSubmenu = secondaryActiveSubmenu;
    }

    return activeSubmenu;
  }

  /**
   * Creates (or removes) a focus trap on the visible mobile submenu.
   */
  function adjustFocusTrap() {
    const activeSubmenu = getActiveSubMenu();

    // Remove any previously set inert attributes within the menu wrapper.
    document.querySelectorAll('.header__menu-wrapper [data-mobile-menu-submenu-inert]').forEach(el => {
      el.inert = false;
      delete el.dataset.mobileMenuSubmenuInert;
    });

    // Loop through all mobile menu elements (but not close button). If they're not
    // under the currently active submenu, set them to inert.
    document.querySelectorAll(`.header__menu-wrapper ${Drupal.uscgov.focusableElementsSelector}:not(.header__menu-wrapper-close-button)`).forEach(focusableElement => {
      if (activeSubmenu && !activeSubmenu.contains(focusableElement)) {
        focusableElement.inert = true;
        focusableElement.dataset.mobileMenuSubmenuInert = true;
      }
    });
  }

  Drupal.behaviors.mobileMenu = {
    attach(context) {
      once('mobile-menu', '.primary-menu--level-0', context).forEach(init);
    },
  };
})(Drupal, once);
