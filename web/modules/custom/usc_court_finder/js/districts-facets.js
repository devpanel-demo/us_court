/**
 * @file
 * Court Finder Districts facet.
 */
(($, Drupal, once) => {

  /**
   * Initializes Court Finder Districts facet.
   *
   * @param {Element} facetItem - The Court Finder facet item.
   */
  function initDistrictFacet(facetItem) {

    var container = facetItem.closest('.widget-container');
    const activeCheckboxes = container.querySelectorAll('.facets-checkbox');
    if (activeCheckboxes && activeCheckboxes.length > 0) {
      
      activeCheckboxes.forEach(function (activeCheckbox) {  
        if (activeCheckbox.checked == true) {
          activeCheckbox.parentElement.classList.add('active-checkbox');
        }
        else{
          activeCheckbox.parentElement.classList.add('inactive-checkbox');
        }
      });
    }
    const collapsedChildren = facetItem.querySelectorAll('.fcf-secondary-option.collapsed');
    if (collapsedChildren && collapsedChildren.length > 0) {
      collapsedChildren.forEach(function (secondLevelElement) {
        secondLevelElement.parentElement.classList.add('collapsed-facet');
        var container = secondLevelElement.closest('.widget-container');
        container.classList.add('expandable-facet');
        var showMoreLink = container.querySelector('.show-more');
        showMoreLink.addEventListener('click', function () {
          event.preventDefault();
          container.classList.add('expanded-facet');
          container.classList.remove('expandable-facet');
          // Remove expandable classes.
          var hiddenLinks = container.querySelectorAll('.fcf-secondary-option.collapsed');
          hiddenLinks.forEach(function (hiddenLink) {
            hiddenLink.classList.remove('fcf-secondary-option');
            hiddenLink.classList.remove('collapsed');
            hiddenLink.closest('.collapsed-facet').classList.remove('collapsed-facet');
          });
        });
      });
    }
  }

  Drupal.behaviors.districtsFacets = {
    attach(context) {
      once('districts-facets', '.block-facet-blockusc-district-id .facets-widget-', context).forEach(initDistrictFacet);
    },
  };

})(jQuery, Drupal, once);
