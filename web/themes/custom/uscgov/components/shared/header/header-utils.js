((Drupal) => {
  Drupal.uscgov = Drupal.uscgov || {};

  /**
   * A selector that will return all focusable elements.
   */
  Drupal.uscgov.focusableElementsSelector = ':is(audio, button, canvas, details, iframe, input, select, summary, textarea, video, [accesskey], [contenteditable], [href], [tabindex]:not([tabindex*="-"])):not(:is([disabled], [inert]))';

  /**
   * Is the mobile navigation system active?
   *
   * @returns boolean
   */
  Drupal.uscgov.isMobileMenuSystem = () => {
    const mobileNavButton = document.querySelector('.header__menu-button');
    return mobileNavButton?.clientHeight !== 0;
  }

  /**
   * Enables/disables a focus trap on the mobile menu wrapper.
   *
   * @param {boolean} focusTrapEnabled - True if the focus trap should be enabled,
   * otherwise false.
   */
  Drupal.uscgov.toggleFocusTrap = focusTrapEnabled => {
    const menuWrapper = document.querySelector('.header__menu-wrapper');

    if (Drupal.uscgov.isMobileMenuSystem() && focusTrapEnabled === true) {
      document.querySelectorAll(Drupal.uscgov.focusableElementsSelector).forEach(focusableElement => {
        if (!menuWrapper.contains(focusableElement)) {
          focusableElement.inert = true;
          focusableElement.dataset.mobileMenuInert = true;
        }
      });
    }
    else {
      document.querySelectorAll('[data-mobile-menu-inert], [data-mobile-menu-submenu-inert]').forEach(el => {
        el.inert = false;
        delete el.dataset.mobileMenuInert;
        delete el.dataset.mobileMenuSubmenuInert;
      });
    }
  }

  /**
   * Variables to support the "slimming" of the USCourts header on scroll.
   */
  let lastKnownScrollPosition = 0;
  let waitingForFrame = false;
  const header = document.querySelector('.header--fixed');
  const backToTopWrapper = document.querySelector('.back-to-top-wrapper');

  /**
   * Toggles a CSS class to the header when scroll position is met.
   * @param {Number} scrollPos - The scroll position.
   */
  function modifyHeaderOnScroll(scrollPos) {
    if (scrollPos > 100) {
      header.classList.add('is-slim');
    }
    else if (scrollPos < 20) {
      header.classList.remove('is-slim');
    }
  }

  /**
   * Toggles visibility of the back to top button.
   * @param {Number} scrollPos - The scroll position.
   */
  function showHideBackToTopButton(scrollPos) {
    backToTopWrapper.classList.toggle('is-hidden', scrollPos < 200);
  }

  /**
   * Interactions for USCourts header on page scroll.
   */
  function handlePageScroll() {
    lastKnownScrollPosition = window.scrollY;

    if (!waitingForFrame) {
      window.requestAnimationFrame(() => {
        modifyHeaderOnScroll(lastKnownScrollPosition);
        showHideBackToTopButton(lastKnownScrollPosition);
        waitingForFrame = false;
      });

      waitingForFrame = true;
    }
  }

  if (header) {
    document.addEventListener('scroll', handlePageScroll);
  }

  /**
   * Close any and all open menu items. This DOES NOT close the mobile navigation.
   */
  Drupal.uscgov.closeAllMenuItems = () => {
    const openMenuItems = document.querySelectorAll('.primary-menu--level-0 [aria-expanded="true"]');
    openMenuItems.forEach(item => item.setAttribute('aria-expanded', false));

    document.querySelectorAll('[data-mobile-menu-submenu-inert]').forEach(el => {
      el.inert = false;
      delete el.dataset.mobileMenuSubmenuInert;
    });
  }

})(Drupal);
