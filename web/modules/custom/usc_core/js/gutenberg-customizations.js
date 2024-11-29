(function (Drupal, once) {
  Drupal.behaviors.gutenbergCustomizations = {
    attach: function (context, settings) {
      once('gutenbergCustomizations', context).forEach(function() {

        const allBlocks = wp.blocks.getBlockTypes();
        // Check if wp and wp.hooks are available.
        if (typeof wp !== 'undefined' && typeof wp.hooks !== 'undefined') {
          wp.hooks.addFilter(
            'blocks.registerBlockType',
            'usc-core/remove-advanced-section',
            function (settings, name) {
              // Remove the "Advanced" section by filtering out the attributes.
              if (settings.supports) {
                settings.supports.customClassName = false;
                settings.supports.className = false;
              }
              return settings;
            }
          );
        }

        allBlocks.forEach(function (block) {
          const blockName = block.name;
          const blockSettings = block;    

          // Re-apply the filter for all blocks (custom and core)
          wp.hooks.applyFilters(
            'blocks.registerBlockType',
            blockSettings,
            blockName
          );
        });
      });
    }
  };
})(Drupal, once);