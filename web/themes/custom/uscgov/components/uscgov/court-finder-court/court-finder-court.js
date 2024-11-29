((once) => {
	function init(el) {
    if (window.history.length > 1) {
		  document.getElementById('back-to-results').style.display = 'block';
		} else {
		  document.getElementById('back-to-results').style.display = 'none';
		}
		document.getElementById('back-to-results').addEventListener('click', function() {
	    if (window.history.length > 1) {
	    	event.preventDefault();
	      window.history.back();
	    }
		});
  }
  Drupal.behaviors.courtFinderCourt = {
    attach(context) {
      once('court-finder-court', '.usc-location-links', context).forEach(init);
    },
  };
})(once);