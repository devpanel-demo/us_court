/*
 * @file
 * Moves the distance value to inside of the court finder results component.
 */

((once) => {
  function init(el) {
    const distance = el.closest('.views-row').querySelector('.views-field-field-geofield-distance').textContent;

    if (distance.trim().length) {
      const newDistanceContainer = el.querySelector('.court-finder-result__distance');
      newDistanceContainer.textContent = distance;
      newDistanceContainer.classList.add('court-finder-result__distance--processed');
    }
  }

  Drupal.behaviors.courtFinderResults = {
    attach(context) {
      once('court-finder-results', '.court-finder-result', context).forEach(init);
    },
  };
})(once);
