const { blocks, data, element, components, editor, blockEditor } = wp;
const { registerBlockType } = blocks;
const { dispatch, select, useSelect } = data;
const { Fragment } = element;
const { Toolbar, Placeholder, ToggleControl, PanelBody, TextareaControl, RangeControl } = components;
const { RichText, InspectorControls, BlockIcon, TextControl, InnerBlocks, PanelColorSettings, AlignmentToolbar } = blockEditor;
const __ = Drupal.t;

const iconList = {
  title: __('Icon List'),
  description: __('Icon List with theme variants'),
  icon: 'editor-ul',
  attributes: {
    size: {
      type: 'string',
      default: 'default'
    },
  },

  edit({ attributes, setAttributes }) {
    const { size } = attributes;
    const ICON_TEMPLATE = [
      [ 'uswds/icon-list-item', {} ],
    ];
    let classList = 'usa-icon-list';
    if (size != 'default') {
      classList += ' usa-icon-list--size-' + size;
    }

    return (
      <Fragment>
        <ul className={ classList }>
          <InnerBlocks allowedBlocks={ ['uswds/icon-list-item'] } template={ ICON_TEMPLATE } />
        </ul>
        <InspectorControls>
          <PanelBody title={ __('List Size') }>
            <SelectControl
              value={ size }
              options={[
                { label: 'Default', value: 'default' },
                { label: 'Extra Small', value: 'xs' },
                { label: 'Small', value: 'sm' },
                { label: 'Medium', value: 'md' },
                { label: 'Large', value: 'lg' },
                { label: 'Extra Large', value: 'xl' },
              ]}
              onChange={ newSize => {
                setAttributes({ size: newSize });
              }}
            />
          </PanelBody>
        </InspectorControls>
      </Fragment>
    );
  },

  save({ attributes }) {
    const { size } = attributes;
    return (
      <InnerBlocks.Content />
    );
  }
};

const iconListItem = {
  title: __('Icon list Item'),
  description: __('Icon List with theme variants'),
  icon: 'editor-ul',
  attributes: {
    mediaEntity: {
      type: 'object',
      default: {},
    },
    mediaEntityId: {
      type: 'integer',
    },
    allowedTypes: {
      type: 'array',
      default: ['image'],
    },
    svgColor: {
      type: 'string',
      default: '#000000',
    },
    viewBox: {
      type: 'string',
      default: '0 0 44 44',
    },
  },

  edit({ attributes, className, setAttributes, isSelected, clientId }) {
    const { mediaEntityId, mediaEntity, svgColor, viewBox} = attributes;
    const isParentOfSelectedBlock = useSelect( ( select ) => select( 'core/block-editor' ).hasSelectedInnerBlock( clientId, true ) );
    const isSelectedOrChild = isSelected || isParentOfSelectedBlock;

    let descriptionClass = ' usa-icon-list__icon';

    let loading = false;

    const instructions = __(
      'Select a media icon from the media library.',
    );
    const placeholderClassName = [
      'block-editor-media-placeholder',
      'editor-media-placeholder',
    ].join(' ');

    /**
     * Return a media entity given the media id.
     * @param {Number} item The media id
     * @returns {Object} The media entity
     */
    const getIconEntity = async (item) => {
      const response = await fetch(
        Drupal.url(`gutenberg/media/load-media/${item}`),
      );
    
      if (response.ok) {
        const mediaEntity = await response.json();
    
        if (mediaEntity) {
          return mediaEntity;
        }
      }
    
      if (response.status === 404) {
        Drupal.notifyError("Media entity couldn't be found.");
        return null;
      }
    
      if (!response.ok) {
        Drupal.notifyError('An error occurred while fetching data.');
        return null;
      }
    };

    /**
     * Set the mediaEntity attribute when the media upload is updated.
     * @param {Number} mediaEntityId The media entity
     */
    const insertIconMedia = mediaEntityId => {

      if (isNaN(mediaEntityId)) {
        const regex = /\((\d*)\)$/;
        const match = regex.exec(mediaEntityId);
        mediaEntityId = match[1];
      }

      getIconEntity(mediaEntityId).then(media => {
        if (media.viewbox) {
          setAttributes({
            viewBox: media.viewbox,
          });
        }
        setAttributes({
          mediaEntityId: Number(media.id),
          mediaEntity: media,
        });
      });

    }

    let objectClass = 'usa-icon';
    let uniqueId = 'media-icon_list-' + mediaEntityId;

    return (
      <Fragment>
        <li class="usa-icon-list__item">
        { mediaEntityId ?
          <div 
            className={ className + descriptionClass }
          >
            <Fragment>
              <svg
                class={objectClass}
                aria-hidden="true"
                aria-labelledby={uniqueId}
                dangerouslySetInnerHTML={{__html:mediaEntity.svg}}
                viewBox={viewBox}
                fillColor={svgColor}
                style={{color:svgColor}}
              />                            
            </Fragment>
            <InspectorControls>
              <Fragment>
                <PanelColorSettings
                  title={ __('Color Settings') } initialOpen={ true }                  
                  colorSettings = { 
                    [{
                      value: svgColor,
                      onChange: value => setAttributes({ svgColor: value }),
                      label: __('Icon Color'),
                      disableCustomColors:true,                      
                    }]
                  }
                />
              </Fragment>
            </InspectorControls>
            <BlockControls>
              <Toolbar
                controls={[
                  {
                    icon: 'no',
                    title: __('Clear media'),
                    onClick: () => setAttributes({
                      mediaEntityId: null,
                      mediaEntity: null,
                    }),
                  },
                ]}
              >
                {loading && (
                  <div className="ajax-progress ajax-progress-throbber">
                    <div className="throbber">&nbsp;</div>
                  </div>
                )}
              </Toolbar>
            </BlockControls>
          </div>          
        :
          <Placeholder
            icon={<BlockIcon icon="admin-media" />}
            label={__('Icon')}
            instructions={instructions}
            className={placeholderClassName}
          >
            <MediaUpload
              onSelect={insertIconMedia}
              allowedTypes={attributes.allowedTypes}
              allowedBundles={['icon']}
              multiple={false}
              handlesMediaEntity={true}
            />
          </Placeholder>
        }
        <div class="usa-icon-list__content">
          <InnerBlocks allowedBlocks={ ['core/paragraph'] } />
        </div>
        </li>
      </Fragment>
    );
  },

  save({ className, attributes }) {
    const { mediaEntityId, mediaEntity, svgColor, viewBox } = attributes;
    return (
      <InnerBlocks.Content />
    );
  }
};

const category = {
  slug: 'uswds',
  title: __('USWDS'),
};

const currentCategories = select('core/blocks').getCategories().filter(item => item.slug !== category.slug);
dispatch('core/blocks').setCategories([ category, ...currentCategories ]);

registerBlockType(`${category.slug}/icon-list-item`, { category: category.slug, parent: ['uswds/icon-list'], ...iconListItem });
registerBlockType(`${category.slug}/icon-list`, { category: category.slug, ...iconList });
