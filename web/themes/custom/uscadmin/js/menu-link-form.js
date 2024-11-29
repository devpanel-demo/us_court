/**
 * @file
 * Customization of the menu link form.
 */

((Drupal, once) => {
  /**
   * Initialize the JS.
   *
   * @param {Element} form - The form element
   */
  function init(form) {
    const parentSelect = form.querySelector('[data-drupal-selector="edit-menu-parent"]');
    const enableMegamenuCheckbox = form.querySelector('[data-drupal-selector="edit-field-enable-megamenu-value"]');

    parentSelect.addEventListener('change', (e) => {
      const optionText = parentSelect.querySelector(`[value="${e.target.value}"]`).textContent;
      calculateMenuLevel(optionText);
    });

    enableMegamenuCheckbox.addEventListener('change', (e) => {
      const optionText = parentSelect.querySelector(`[value="${parentSelect.value}"]`).textContent;
      calculateMenuLevel(optionText)
    });

    // Fire parentSelect's change event on page load.
    parentSelect.dispatchEvent(new Event('change', { 'bubbles': true }));
  }

  /**
   * Looks at the <option> element's string of text, and calculate's the current
   * menu level based on the number of preceding dashes (-).
   *
   * @param {string} optionText - A string of text
   */
  function calculateMenuLevel(optionText) {
    const leadingDashes = optionText.replace(/[^-]|(?<=[a-zA-Z\d])-/g, "");
    const menuLevel = leadingDashes.length / 2 + 1;
    showHideItems(menuLevel);
  }

  /**
   * Shows and hides various form elements based on what the current menu level
   * is.
   *
   * @param {number} menuLevel - The current menu level (starts at 1)
   */
  function showHideItems(menuLevel) {
    const enableMegaMenu = document.querySelector('.form-item--field-enable-megamenu-value');
    const enableMegaMenuCheckbox = enableMegaMenu.querySelector('[type="checkbox"]');
    const megaMenuOptions = document.querySelector('#edit-group-megamenu-settings');
    const popularLinks = document.querySelector('#field-popular-links-add-more-wrapper');

    // Hidden by default.
    enableMegaMenu.classList.toggle('menu-link-form-hidden', true);
    megaMenuOptions.classList.toggle('menu-link-form-hidden', true);
    popularLinks.classList.toggle('menu-link-form-hidden', true);

    if (menuLevel == 1) {
      enableMegaMenu.classList.remove('menu-link-form-hidden');
      popularLinks.classList.remove('menu-link-form-hidden');
      megaMenuOptions.classList.toggle('menu-link-form-hidden', !enableMegaMenuCheckbox.checked);
    }
    else {
      megaMenuOptions.classList.remove('menu-link-form-hidden');
    }
  }

  Drupal.behaviors.menuLinkForm = {
    attach(context) {
      once('menu-link-form', '[data-drupal-selector="menu-link-content-main-form"]', context).forEach(
        init,
      );
    },
  };
})(Drupal, once);
