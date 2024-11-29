/**
 * @file
 * Court Finder Google analytics service.
 */
(function (window) {
  'use strict';

  // Function to get the current route.
  function getRoute() {
    const path = window.location.pathname;
    if (path.includes('search')) {
      return 'search';
    } else if (path.includes('find')) {
      return 'find';
    } else if (path.includes('location')) {
      return 'location';
    }
    return '';
  }

  // Main function to push events to GTM.
  function gtag(label, category, arg1) {

    if (category === '') {
      const route = getRoute();

      switch (route) {
        case 'search':
          category = 'fcf_search_page';
          break;
        case 'find':
          category = 'fcf_search_results';
          break;
        case 'location':
          category = 'fcf_location_page';
          break;
      }
    }

    if (typeof window.gtagEvent !== 'undefined') {
      const dataLayer = window.dataLayer || [];

      if (['fcf_court_website', 'fcf_court_ecf', 'fcf_court_ejuror', 'fcf_court_jury_info', 'fcf_court_search_name'].includes(label)) {
        dataLayer.push({
          'event': label,
          'fcf_page': category,
          'fcf_court_name': arg1
        });
      } else if (label === 'fcf_court_phone') {
        dataLayer.push({
          'event': label,
          'fcf_page': category,
          'phone_number': arg1,
          'contact_type': 'phone'
        });
      } else if (label === 'fcf_court_directions') {
        dataLayer.push({
          'event': label,
          'fcf_page': category,
          'location_address': arg1
        });
      } else if (label === 'fcf_search_location') {
        dataLayer.push({
          'event': label,
          'fcf_page': category,
          'fcf_location_searched': arg1
        });
      } else if (label === 'fcf_search_name') {
        dataLayer.push({
          'event': label,
          'fcf_page': category,
          'fcf_court_name': arg1
        });
      } else if (label === 'fcf_location_click') {
        dataLayer.push({
          'event': label,
          'fcf_page': 'fcf_search_results',
          'fcf_court_name': arg1
        });
      } else if (['fcf_results_filter_selected', 'fcf_results_filter_removed', 'fcf_results_filter_unchecked'].includes(label)) {
        dataLayer.push({
          'event': label,
          'fcf_page': 'fcf_search_results',
          'fcf_filter': arg1
        });
      } else if (label === 'fcf_results_select_all_filters') {
        dataLayer.push({
          'event': label,
          'fcf_page': 'fcf_search_results',
          'fcf_filter_category': arg1
        });
      } else if (label === 'fcf_results_page_number') {
        dataLayer.push({
          'event': label,
          'fcf_page': 'fcf_search_results',
          'page_number': arg1
        });
      } else if (label === 'fcf_results_shown') {
        dataLayer.push({
          'event': label,
          'fcf_page': 'fcf_search_results',
          'results_shown': arg1
        });
      } else if (label === 'fcf_results_reset_filters') {
        dataLayer.push({
          'event': label,
          'fcf_page': 'fcf_search_results'
        });
      } else {
        dataLayer.push({
          'event': label,
          'fcf_page': category
        });
      }
    }
  }

  // Expose the gtag function globally
  window.gtagEvent = gtag;
})(window);
