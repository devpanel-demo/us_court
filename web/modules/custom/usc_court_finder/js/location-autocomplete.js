/**
 * @file
 * Court Finder Location autocomplete.
 */
(($, Drupal, once) => {

  /**
   * Initializes Court Finder location autocomplete.
   *
   * @param {Element} locationAutocomplete - The Court Finder location field.
   */
  function initAutocomplete(locationAutocomplete) {
    let sessionToken;
    const autocompleteService = new google.maps.places.AutocompleteService();
    const placesService = new google.maps.places.PlacesService(document.createElement('div'));
    const geocoder = new google.maps.Geocoder();
    let options = {
      input: '',
      types: ['geocode'],
      sessionToken: sessionToken,
      componentRestrictions: {
        country: ['us', 'gu', 'pr', 'mp', 'vi']
      },
    };

    // Set JQuery autocomplete.
    $(locationAutocomplete).autocomplete({
      source: function(request, response) {
        if (!sessionToken) {
          // Generate a new session token only if not already generated.
          sessionToken = new google.maps.places.AutocompleteSessionToken();
        }
        options.input = request.term;
        autocompleteService.getPlacePredictions(options, (predictions, status) => {
          if (status === google.maps.places.PlacesServiceStatus.OK) {
            let data = [];
            predictions.forEach((prediction) => {
              data.push({
                label: prediction.description,
                value: prediction.description,
                id: prediction.place_id
              })
            });
            response(data);
          } else {
            console.error('Places Autocomplete API request failed:', status);
          }
        });
      },
      minLength: 2,
      select: function(event, ui) {
        const placeId = ui.item.id;
        placesService.getDetails({
          placeId,
          sessionToken: sessionToken
        },
        (place, status) => {
          if (status === google.maps.places.PlacesServiceStatus.OK) {
            const latitude = place.geometry.location.lat();
            const longitude = place.geometry.location.lng();
            const formattedAddress = place.formatted_address;

            if (place.address_components.length === 2) {
              // Extract address components directly from place.address_components
              let country = '';
              let state = '';

              place.address_components.forEach((component) => {
                switch (component.types[0]) {
                  case 'country':
                    country = component.short_name;
                    break;
                  case 'administrative_area_level_1': // State.
                    state = component.short_name;
                    break;
                }
              });

              // Construct redirect URL with query parameters.
              let redirectUrl = `/federal-court-finder/find?coordinates=${latitude},${longitude}&location=${encodeURIComponent(formattedAddress)}&filter=default`;

              if (country) redirectUrl += `&country=${encodeURIComponent(country)}`;
              if (country === 'US' && state) redirectUrl += `&state=${encodeURIComponent(state)}`;

              // Reset the session token after user selection.
              sessionToken = null;

              // Redirect to the Court Finder page.
              document.location.href = redirectUrl;

            } else {
              // Perform a reverse geocoding request to get address components.
              geocoder.geocode({ location: { lat: latitude, lng: longitude } }, (results, status) => {
                if (status === google.maps.GeocoderStatus.OK && results[0]) {
                  const addressComponents = results[0].address_components;
                  let country = '';
                  let state = '';
                  let county = '';
                  let zip = '';

                  addressComponents.forEach((component) => {
                    switch (component.types[0]) {
                      case 'country':
                        country = component.short_name;
                        break;
                      case 'administrative_area_level_1': // State.
                        state = component.short_name;
                        break;
                      case 'administrative_area_level_2': // County.
                        county = component.long_name.replace(/( County| Parish)$/, ''); // Remove " County" or " Parish".
                        break;
                      case 'postal_code': // Zip.
                        zip = component.short_name;
                        break;
                    }
                  });

                  // Construct redirect URL with query parameters.
                  let redirectUrl = `/federal-court-finder/find?coordinates=${latitude},${longitude}&location=${encodeURIComponent(formattedAddress)}&filter=default`;

                  if (country) redirectUrl += `&country=${encodeURIComponent(country)}`;
                  if (country === 'US') {
                    if (state) redirectUrl += `&state=${encodeURIComponent(state)}`;
                    if (county) redirectUrl += `&county=${encodeURIComponent(county)}`;
                  }
                  if (zip) redirectUrl += `&zip=${encodeURIComponent(zip)}`;

                  // Reset the session token after user selection.
                  sessionToken = null;

                  // Redirect to the Court Finder page.
                  document.location.href = redirectUrl;

                } else {
                  console.error('Geocoding API request failed:', status);
                }
              });
            }
          } else {
            console.error('Place Details API request failed:', status);
          }
        });
      }
    });
  }

  Drupal.behaviors.placesAutocomplete = {
    attach(context) {
      once('places-autocomplete', '.court-finder-block__form--location .court-finder-block__input', context).forEach(initAutocomplete);
    },
  };

})(jQuery, Drupal, once);
