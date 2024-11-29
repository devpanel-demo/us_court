/**
 * @file
 * Attaches behaviors for Galleries.
 */

(function (Drupal) {

  /**
   * Provides gallery functionality.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attach gallery functionality.
   */
  Drupal.behaviors.splideGallery = {
    attach(context) {
      context.querySelectorAll('.usc-gallery:not(.gallery-processed)').forEach(galleryField => {
        // Slider Options.
        const mainSlider = new Splide(galleryField.querySelector('.main-slider'), {
          perPage: 2,
          gap: '1.5rem',
          arrows: true,
          padding: { left: 0, right: '10%'},
          breakpoints: {
            1400: {
              perPage: 2,
            },
            800: {
              perPage: 1,
            }
          }
        });
        // Mounting slider.
        mainSlider.mount( window.splide.Extensions );
        // Zoom.
        const lightbox = GLightbox({});
        galleryField.querySelectorAll('.main-slider .splide__slide__container a').forEach(image => {
          image.addEventListener('click', function(e) {
            let item_number = 0;
            e.preventDefault();

            // Load slider container.
            var slider = this.closest(".splide__list");
            var slideritems = [];
            // Get list of slides.
            slider.querySelectorAll('.splide__slide .slide-content-wrapper').forEach(item => {
              let current_slide = item.querySelector('.splide__slide__container a');
              // Get current item.
              if (current_slide == image) {
                item_number = slideritems.length;
              }
              let targetHref = current_slide.getAttribute('href');
              let asset_url = item.querySelector('.splide__slide__container').getAttribute('asset-url');
              if  (asset_url != null && asset_url != '') {
                targetHref = asset_url;
              }
              // Get items description.
              let title = '';
              let description = '';
              if (item.querySelector('.readmore-text') != null) {
                description = item.querySelector('.readmore-text').innerHTML;
              }
              else if (item.querySelector('p') != null)  {
                description = item.querySelector('p').outerHTML;
              }

              if (item.querySelector('.details-box strong') != null) {
                title = item.querySelector('.details-box strong').outerHTML;
              }
              slideritems.push({
                'href': targetHref,
                'description': title + description,
              });
            });

            lightbox.setElements(slideritems);

            lightbox.openAt(item_number);
          })
        });
        // Add processed class.
        galleryField.classList.add('gallery-processed');
      });
    },
  };
})(Drupal);
