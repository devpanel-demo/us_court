(function (Drupal, once) {
  Drupal.behaviors.gutenbergColorCustomizations = {
    attach: function (context, settings) {
      once('gutenbergColorCustomizations', context).forEach(function() {
        // Check if wp and wp.hooks are available.
        if (typeof wp !== 'undefined' && typeof wp.hooks !== 'undefined') {
          wp.hooks.addFilter(
            'blocks.registerBlockType',
            'usc-core/color-palette-section',
            function (settings, name) {
              if (name === 'core/button' && settings.supports) {
                settings.attributes = Object.assign(settings.attributes, {
                  backgroundColor: {
                    type: 'string',
                    default: 'primary',
                  },
                });
                settings.supports = Object.assign(settings.supports, {
                  "color": {
                    "text": false,
                    "background": true,
                    "custom": false
                  }
                });
              }
              return settings;
            }
          );

          // Remove color and typography options for core paragraph block.
          wp.hooks.addFilter(
            'blocks.registerBlockType',
            'usc-core/typography',
            function (settings, name) {
              if (name === 'core/paragraph' && settings.supports) {
                settings.supports = Object.assign(settings.supports, {
                  "color": false,
                  "typography": false
                });
              }
              return settings;
            }
          );

          // Remove options for core separator block.
          wp.hooks.addFilter(
            'blocks.registerBlockType',
            'usc-core/separator',
            function (settings, name) {
              if (name === 'core/separator') {
                // Removing the styles here. this does work.
                return Object.assign({}, settings, {
                  styles: [],
                });
              }
              return settings;
            }
          );
        }
      });
    }
  };
})(Drupal, once);
