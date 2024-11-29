/**
 * @file
 * Sets inline style with height of iframe. Used for Tableau embeds.
 */
((Drupal, once) => {
  function init(iframe) {
    const height = parseInt(iframe.getAttribute('height'), 10);

    if (height && !isNaN(height)) {
      iframe.style.height = `${height + 200}px`;
    }
  }

  Drupal.behaviors.iframeHelper = {
    attach(context) {
      once('iframe-helper', 'iframe', context).forEach(init);
    },
  };
})(Drupal, once);
