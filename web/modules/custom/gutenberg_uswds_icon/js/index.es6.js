const { blocks, data, element, components, editor, blockEditor } = wp;
const { registerBlockType } = blocks;
const { dispatch, select, useSelect } = data;
const { Fragment } = element;
const { Toolbar, Placeholder, ToggleControl, PanelBody, TextareaControl, RangeControl } = components;
const { RichText, InspectorControls, BlockIcon, TextControl, InnerBlocks, PanelColorSettings, AlignmentToolbar } = blockEditor;
const __ = Drupal.t;

const icon = {
  title: __('Icon'),
  description: __('Icon with theme variants'),
  icon: 'format-image',
  attributes: {
    iconType: {
      type: "string",
      default: 'usa-icon--size-3'
    },
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
    imageAlt: {
      type: 'string',
      default: '',
    },
    svgColor: {
      type: 'string',
      default: '#000000',
    },
    backgroundColor: {
      type: 'string',
      type: 'transparent',
    },
    align: {
      type: 'string',
      default: 'left',
    },
    viewBox: {
      type: 'string',
      default: '0 0 44 44',
    },
  },

  edit({ attributes, className, setAttributes, isSelected, clientId }) {
    const { align, iconType, imageAlt, mediaEntityId, mediaEntity, svgColor, backgroundColor, viewBox} = attributes;
    const isParentOfSelectedBlock = useSelect( ( select ) => select( 'core/block-editor' ).hasSelectedInnerBlock( clientId, true ) );
    const isSelectedOrChild = isSelected || isParentOfSelectedBlock;

    let descriptionClass = ' usa-icon-container';
    let bodyClass = 'usa-icon__body';

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

    let objectClass = 'usa-icon ' + iconType;
    let uniqueId = 'media-icon-' + mediaEntityId;
    let title = '<title id="' + uniqueId + '">'+ imageAlt +'</title>';

    return (
      <Fragment>
        { mediaEntityId ?
          <div 
            className={ className + descriptionClass }
            style={{textAlign: align}}
          >
            <Fragment>
              <svg
                class={objectClass}
                aria-hidden="true"
                aria-labelledby={uniqueId}
                dangerouslySetInnerHTML={{__html:mediaEntity.svg + title}}
                viewBox={viewBox}
                fillColor={svgColor}
                style={{backgroundColor: backgroundColor, color:svgColor}}
              />
            </Fragment>
            <InspectorControls>
              <Fragment>              
                <PanelBody title={ __('Layout') }>
                  <SelectControl
                    label={ __('Icon Size') }
                    value={ iconType }
                    options={ [
                        { label: '3 unit', value: 'usa-icon--size-3' },
                        { label: '4 unit', value: 'usa-icon--size-4' },
                        { label: '5 unit', value: 'usa-icon--size-5' },
                        { label: '6 unit', value: 'usa-icon--size-6' },
                        { label: '7 unit', value: 'usa-icon--size-7' },
                        { label: '8 unit', value: 'usa-icon--size-8' },
                        { label: '9 unit', value: 'usa-icon--size-9' },
                    ] }
                    onChange={nextLayout => {
                      setAttributes({
                        iconType: nextLayout,
                      });
                    }}
                  />
                </PanelBody>
                <PanelColorSettings
                  title={ __('Color Settings') } initialOpen={ true }                  
                  colorSettings={ [
                    {
                      value: svgColor,
                      onChange: value => setAttributes({ svgColor: value }),
                      label: __('Icon Color'),
                      disableCustomColors:true,                      
                    }, {
                      value: backgroundColor,
                      onChange: value => setAttributes({ backgroundColor: value }),
                      label: __('Background Color'),
                      disableCustomColors:true,
                      colors:[
                        { name: 'black', color: '#000000' },
                        { name: 'white', color: '#ffffff' },
                      ],
                    },
                  ] }
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
              <AlignmentToolbar
                value={ align }
                onChange={ nextAlign => setAttributes({ align: nextAlign }) }
              />
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
      </Fragment>
    );
  },

  save({ className, attributes }) {
    const { align, iconType, imageAlt, mediaEntityId, mediaEntity, svgColor, backgroundColor, viewBox } = attributes;
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

registerBlockType(`${category.slug}/icon`, { category: category.slug, ...icon });
