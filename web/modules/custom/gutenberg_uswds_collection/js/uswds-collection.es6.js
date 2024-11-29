/* eslint func-names: ["error", "never"] */
(function(wp, Drupal, drupalSettings) {
  const { data, blocks, blockEditor } = wp;
  const { Fragment } = wp.element;
  const { UswdsCollectionContent } = window.DrupalGutenberg.Components;
  const allowedContentTypes = drupalSettings.gutenbergCollectionEmbed.allowed;



  function registerBlock(id, label, viewmodes, widthControl) {
    const blockId = `uswdscollection/${id.replace(/_/g, "-")}`;
    blocks.registerBlockType(blockId, {
      title: Drupal.t('Collection') + ': ' + label,
      icon: 'index-card',
      category: 'uswdscollection',
      supports: {
        align: true,
        html: false,
        reusable: true,
      },
      attributes: {
        nodeIds: {
          type: 'array',
        },
        viewMode: {
          type: 'string',
        default: false
        },
        collectionRange: {
          type: 'number',
          default: 1
        },
        useLatest: {
          type: 'boolean',
          default: false
        },
      },
      edit({ attributes, className, setAttributes, isSelected }) {
        const { viewMode } = attributes;
        if (viewMode == false) {
          setAttributes({ viewMode: Object.keys(viewmodes)[0] });
        }        
        return (
          <UswdsCollectionContent
            attributes={attributes}
            className={className + ' gutenberg-content-embed'}
            contentType={id}
            contentTypeLabel={label}
            setAttributes={setAttributes}
            viewModes={viewmodes}
            isSelected={isSelected}
          />
        );
      },
      save() {
        return null;
      },
    });
  }

  function registerDrupalContent() {
    const category = {
      slug: 'uswdscollection',
      title: Drupal.t('USWDS Collection'),
    };

    const categories = [
      ...data.select('core/blocks').getCategories(),
      category,
    ];

    data.dispatch('core/blocks').setCategories(categories);
    if (drupalSettings.gutenbergCollectionEmbed.allowed !== undefined) {      
      for (const key in allowedContentTypes) {
        if (Object.hasOwnProperty.call(allowedContentTypes, key)) {
          const viewmodes = allowedContentTypes[key]['viewModes'];
          const label = allowedContentTypes[key]['label'];
          registerBlock(key, label, viewmodes)
        }
      }
    }
  }
  registerDrupalContent();
})(wp, Drupal, drupalSettings);
