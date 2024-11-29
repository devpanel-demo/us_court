const { blocks, data, element, components, editor, blockEditor } = wp;
const { registerBlockType } = blocks;
const { dispatch, select, useSelect } = data;
const { Fragment } = element;
const { Toolbar, Placeholder, ToggleControl, PanelBody, TextareaControl } = components;
const { RichText, InspectorControls, BlockIcon, TextControl, InnerBlocks } = blockEditor;
const __ = Drupal.t;

const alert = {
  title: __('Alert'),
  description: __('Alert with theme variants'),
  icon: 'list-view',
  attributes: {
    alertType: {
      type: "string",
      default: 'info'
    },
    showIcon: {
      type: 'boolean',
      default: true,
    },
    showSlim: {
      type: 'boolean',
      default: false,
    },
    heading: {
      type: 'string',
      default: '',
    },
  },

  edit({ attributes, className, setAttributes, isSelected, clientId }) {
    const { alertType, showSlim, heading, showIcon } = attributes;
    const isParentOfSelectedBlock = useSelect( ( select ) => select( 'core/block-editor' ).hasSelectedInnerBlock( clientId, true ) );
    const isSelectedOrChild = isSelected || isParentOfSelectedBlock;

    let descriptionClass = ' usa-alert usa-alert--' + alertType;
    let bodyClass = 'usa-alert__body';

    if(showIcon == false){
      descriptionClass = descriptionClass + ' usa-alert--no-icon';
    }

    if(showSlim == true){
      descriptionClass = descriptionClass + ' usa-alert--slim';
    }

    return (
      <Fragment>        
        <div className={ className + descriptionClass }>
          <Fragment>
            <div className={bodyClass}>
              <RichText
                tagName='h4'
                className='usa-alert__heading'
                value={ heading }
                onChange={ ( heading ) => setAttributes( { heading } ) }
                placeholder={ __( 'Write a heading...')}
                keepPlaceholderOnFocus={true}
                withoutInteractiveFormatting={true}
                allowedFormats={[]}
                aria-expanded={ isSelectedOrChild }
              />
              <p class="usa-alert__text">
                <InnerBlocks />
              </p>
            </div>              
          </Fragment>
          <InspectorControls>
            <Fragment>              
              <PanelBody title={ __('Layout') }>
                <SelectControl
                  label={ __('Alert Type') }
                  value={ alertType }
                  options={ [
                      { label: 'Informational', value: 'info' },
                      { label: 'Error', value: 'error' },
                      { label: 'Warning', value: 'warning' },
                      { label: 'Success', value: 'success' },
                      { label: 'Emergency', value: 'emergency' },
                  ] }
                  onChange={nextLayout => {
                    setAttributes({
                      alertType: nextLayout,
                    });
                  }}
                />
              </PanelBody>
            </Fragment>
          </InspectorControls>
          <InspectorControls>
            <Fragment>
              <PanelBody title={ __('Options') }>
                <ToggleControl
                  label={ __('Show Icon') }
                  checked={ showIcon }
                  onChange={() => setAttributes({ showIcon: !showIcon })}
                />
                <ToggleControl
                  label={ __('Slim') }
                  checked={ showSlim }
                  onChange={() => setAttributes({ showSlim: !showSlim })}
                />
              </PanelBody>
            </Fragment>
          </InspectorControls>
        </div>
      </Fragment>
    );
  },

  save({ className, attributes }) {
    const { alertType, showIcon, showSlim, heading } = attributes;
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

registerBlockType(`${category.slug}/alert`, { category: category.slug, ...alert });
