/**
 * @file
 * Court Finder Google analytics events.
 */
(function ($, Drupal, drupalSettings, once) {
  Drupal.behaviors.googleAnalyticsEvents = {
    attach: function (context, settings) {

      // Handle click event for the link in the header.
      $(once('fcf-new-search-header', '#fcf-site-header', context)).on('click', function (event) {
        window.gtagEvent('fcf_new_search', 'fcf_site_header');
      });

      // Handle click event for the new search link on the location page.
      $(once('fcf-new-search-location', '#fcf-new-search', context)).on('click', function (event) {
        window.gtagEvent('fcf_new_search', 'fcf_location_page');
      });

      // Handle click event for the back to results link on the location page.
      $(once('fcf-back-to-results', '#back-to-results', context)).on('click', function (event) {
        window.gtagEvent('fcf_back_to_results', 'fcf_location_page');
      });

      // Handle click event for links in the useful links section.
      $(once('fcf-useful-links', '#fcf-useful-links', context)).on('click', function (event) {
        const parentDivClass = $(this).closest('div').attr('class');
        let eventLabel = '';

        if (parentDivClass === 'court-finder-court-page-link') {
          eventLabel = 'fcf_court_website';
        } else if (parentDivClass === 'court-finder-court-ecf-link') {
          eventLabel = 'fcf_court_ecf';
        } else if (parentDivClass === 'court-finder-court-ejuror-link') {
          eventLabel = 'fcf_court_ejuror';
        } else if (parentDivClass === 'court-finder-court-jury-info-link') {
          eventLabel = 'fcf_court_jury_info';
        }

        // Extract court name from the h1 tag.
        let eventValue = ''
        eventValue = $('h1.page-title').text().split(' - ')[0].trim();
        window.gtagEvent(eventLabel, 'fcf_location_page', eventValue);
      });

      // Handle click event for the back to top button.
      $(once('fcf-scroll-top', 'a.footer__back-to-top', context)).on('click', function (event) {
        if (drupalSettings.path.currentPath == "federal-court-finder/find") {
          window.gtagEvent('fcf_scroll_top', 'fcf_search_results');
        }
      });

      // Handle click event for the phone number on the search results page.
      $(once('fcf-search-results-court-phone', 'a.court-finder-result__phone', context)).on('click', function (event) {
        let phoneNumber = $(this).text().trim();
        window.gtagEvent('fcf_court_phone', 'fcf_search_results', phoneNumber);
      });

      // Handle click event for the phone number on on the location page.
      $(once('fcf-location-court-phone', 'a.court-finder-court__button-phone', context)).on('click', function (event) {
        let phoneNumber = $(this).text().trim();
        window.gtagEvent('fcf_court_phone', 'fcf_search_location', phoneNumber);
      });

      // Handle click event for court location link.
      $(once('fcf-location-click', '.court-finder-result h3 a', context)).on('click', function (event) {
        // Extract court name from the h1 tag.
        let courtName = ''
        courtName = $(this).text().split(' - ')[0].trim();
        window.gtagEvent('fcf_location_click', 'fcf_search_results', courtName);
      });

      // Handle click event for Get Directions link on the search results page.
      $(once('fcf-search-results-court-directions', '.court-finder-result .court-finder-result__directions', context)).on('click', function (event) {
        let address = $(this).closest('.court-finder-result').find('.court-finder-result__address .field__item').text().trim().replace(/\s+/g, ' ');
        window.gtagEvent('fcf_court_directions', 'fcf_search_results', address);
      });

      // Handle click event for Get Directions link on the location page.
      $(once('fcf-location-court-directions', 'a.court-finder-court__button-directions', context)).on('click', function (event) {
        let address = $(this).closest('.court-finder-court').find('.court-finder-court__address').text().trim().replace(/\s+/g, ' ');
        window.gtagEvent('fcf_court_directions', 'fcf_search_location', address);
      });

      // Handle autocomplete selection event for location.
      $(once('fcf-search-location', '.court-finder-block__form--location input.court-finder-block__input', context)).on('autocompleteselect', function (event, ui) {
        let eventPage = drupalSettings.path.isFront ? 'fcf_site_homepage' : 'fcf_search_results';
        window.gtagEvent('fcf_search_location', eventPage, ui.item.value);
      });

      // Handle autocomplete selection event for name.
      $(once('fcf-search-name', '.court-finder-block__form--name input.court-finder-block__input', context)).on('autocompleteselect', function (event, ui) {
        let eventPage = drupalSettings.path.isFront ? 'fcf_site_homepage' : 'fcf_search_results';
        window.gtagEvent('fcf_search_name', eventPage, ui.item.value);
      });

      // Handle click event for court type filters.
      const courtTypeFacetSelector = 'ul[data-drupal-facet-id="usc_court_type"] .facet-item .facets-checkbox';
      $(once('fcf-search-results-court-type', courtTypeFacetSelector, context)).on('change', function (event) {
        const isChecked = !$(this).is(':checked');
        const eventLabel = isChecked ? 'fcf_results_filter_unchecked' : 'fcf_results_filter_selected';
        const facetValue = $(this).closest('.facet-item').find('.facet-item__value').text().trim();

        // Delay to ensure the checkbox state is updated before tracking the event.
        setTimeout(() => {
          window.gtagEvent(eventLabel, 'fcf_search_results', facetValue);
        }, 100);
      });

      // Handle click event for district filters.
      const districtFacetSelector = 'ul[data-drupal-facet-id="usc_district_id"] ul .facet-item .facets-checkbox';
      $(once('fcf-search-results-district', districtFacetSelector, context)).on('change', function (event) {
        const isChecked = !$(this).is(':checked');
        const eventLabel = isChecked ? 'fcf_results_filter_unchecked' : 'fcf_results_filter_selected';
        const facetValue = $(this).closest('.facet-item').find('.facet-item__value').text().trim();

        // Delay to ensure the checkbox state is updated before tracking the event
        setTimeout(() => {
          window.gtagEvent(eventLabel, 'fcf_search_results', facetValue);
        }, 100);
      });

      // Handle click event for select all dictricts filters.
      const allDistrictsFacetSelector = 'ul[data-drupal-facet-id="usc_district_id"] > div > li.facet-item > .facets-checkbox';
      $(once('fcf-search-results-all-districs', allDistrictsFacetSelector, context)).on('change', function (event) {
        const isChecked = !$(this).is(':checked');
        const eventLabel = isChecked ? 'fcf_results_deselect_all_filters' : 'fcf_results_select_all_filters';
        const facetValue = $(this).closest('.facet-item').find('.facet-item__value').text().trim();

        // Delay to ensure the checkbox state is updated before tracking the event
        setTimeout(() => {
          window.gtagEvent(eventLabel, 'fcf_search_results', facetValue);
        }, 100);
      });

      // Handle click event for Reset link.
      $(once('fcf-results-reset-filters', '[data-drupal-facets-summary-id="court_finder"] .facet-summary-item--clear a', context)).on('click', function (event) {
        window.gtagEvent('fcf_results_reset_filters', 'fcf_search_results');
      });

      // Handle click event for individual filter removal.
      $(once('fcf-results-filter_-removed', '[data-drupal-facets-summary-id="court_finder"] .facet-summary-item--facet a', context)).on('click', function (event) {
        const facetValue = $(this).find('.facet-item__value').text().trim();
        window.gtagEvent('fcf_results_filter_removed', 'fcf_search_results', facetValue);
      });

      // Handle pagination events.
      const paginationSelector = '.view-usc-court-finder-locations .usa-pagination__list a';
      $(once('fcf-results-page-number', paginationSelector, context)).on('click', function (event) {
        const href = $(this).attr('href');
        const urlParams = new URLSearchParams(href.split('?')[1]);
        const page = urlParams.get('page');

        if ($(this).hasClass('usa-pagination__next-page')) {
          window.gtagEvent('fcf_results_next_page', 'fcf_search_results');
        } else if ($(this).hasClass('usa-pagination__previous-page')) {
          window.gtagEvent('fcf_results_previous_page', 'fcf_search_results');
        } else if (page !== null) {
          window.gtagEvent('fcf_results_page_number', 'fcf_search_results', page);
        }
      });

    }
  };
})(jQuery, Drupal, drupalSettings, once);
