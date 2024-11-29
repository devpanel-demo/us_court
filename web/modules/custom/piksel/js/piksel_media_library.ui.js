(function ($, Drupal, window, _ref) {
  var tabbable = _ref.tabbable;

  /**
   * When a user interacts with the media library we want the selection to
   * persist as long as the media library modal is opened. We temporarily
   * store the selected items while the user filters the media library view or
   * navigates to different tabs.
   */
  Drupal.MediaLibrary.importSelection = [];

  /**
   * Update the media library import selection when media items are selected.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches behavior to import media items.
   */
  Drupal.behaviors.MediaLibraryImportItemSelection = {
    attach: function attach(context, settings) {
      // The form is outside the context when using the results pager, so we
      // unfortunately can't use context here.
      var $form = $('.js-media-library-add-form-piksel-media');
      var importSelection = Drupal.MediaLibrary.importSelection;

      if (!$form.length) {
        return;
      }

      var $mediaItems = $('.js-media-library-import-item input[type="checkbox"]', $form);

      // Update the selection array and the hidden form field when a media item
      // is selected.
      $(once('media-item-import-change', $mediaItems)).on('change', function (e) {
        var id = e.currentTarget.value;
        var position = importSelection.indexOf(id);

        // Update the selection.
        if (e.currentTarget.checked) {
          if (position === -1) {
            importSelection.push(id);
          }
        } else if (position !== -1) {
          importSelection.splice(position, 1);
        }

        // Set the selection in the media library add form.
        $('.js-media-library-add-form-import-selection', $form).val(importSelection.join());
      });

      // Clear the media library selection when the selection field is not part
      // of the form.
      if (!$('.js-media-library-add-form-import-selection', $form).length) {
        Drupal.MediaLibrary.importSelection = [];
      }

      // Apply the import selection to the add form. Changing the checkbox
      // values triggers the change event for the media items. The change event
      // handles updating the hidden selection field for the form.
      importSelection.forEach(function (value) {
        $form.find("input[type=\"checkbox\"][value=\"".concat(value, "\"]")).prop('checked', true).trigger('change');
      });
    }
  };

  /**
   * Clear the import selection when the media library closes.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches behavior to clear the selection when the library modal closes.
   */
  Drupal.behaviors.MediaLibraryModalClearImportSelection = {
    attach: function attach() {
      if (!once('media-library-clear-import-selection', 'html').length) {
        return;
      }
      $(window).on('dialog:afterclose', function () {
        Drupal.MediaLibrary.importSelection = [];
      });
    }
  };

  /**
   * Load piksel media result pages through AJAX.
   *
   * Standard AJAX links (using the 'use-ajax' class) replace the entire library
   * dialog. When navigating to a media type through the vertical tabs, we only
   * want to load the changed library content. This is not only more efficient,
   * but also provides a more accessible user experience for screen readers.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches behavior to the pager in the piksel media search results.
   *
   * @todo Refactor when the AJAX system adds support for replacing a specific
   *   selector via a link.
   *   https://www.drupal.org/project/drupal/issues/3026636
   */
  Drupal.behaviors.MediaLibraryPikselResultPager = {
    attach: function attach(context) {
      var $pager = $('.js-media-library-add-form-piksel-media .js-pager__items');
      $(once('media-library-search-pager', $pager.find('a'))).on('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var ajaxObject = Drupal.ajax({
          wrapper: 'media-library-results',
          url: e.currentTarget.href,
          dialogType: 'ajax',
          progress: {
            type: 'fullscreen',
            message: Drupal.t('Please wait...')
          }
        });

        // Override the AJAX success callback to shift focus to the media
        // library content.
        ajaxObject.success = function (response, status) {
          var _this = this;
          if (this.progress.element) {
            $(this.progress.element).remove();
          }
          if (this.progress.object) {
            this.progress.object.stopMonitoring();
          }

          // Execute the AJAX commands.
          $(this.element).prop('disabled', false);
          Object.keys(response || {}).forEach(function (i) {
            if (response[i].command && _this.commands[response[i].command]) {
              _this.commands[response[i].command](_this, response[i], status);
            }
          });

          // Set focus to the first tabbable element in the results.
          var mediaLibraryContent = document.getElementById('media-library-results');
          if (mediaLibraryContent) {
            var tabbableContent = tabbable(mediaLibraryContent);
            if (tabbableContent.length) {
              tabbableContent[0].focus();
            }
          }

          // Remove any response-specific settings so they don't get used on
          // the next call by mistake.
          this.settings = null;
        };
        ajaxObject.execute();

        // Announce the updated content.
        Drupal.announce(Drupal.t('Results updated.'));
      });
    }
  };

})(jQuery, Drupal, window, window.tabbable);
