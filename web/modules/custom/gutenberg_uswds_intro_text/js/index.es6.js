const { blocks, data, element, components, editor, blockEditor } = wp;
const { registerBlockType } = blocks;
const { dispatch, select, useSelect } = data;
const { Fragment } = element;
const { Toolbar, ColorPalette, PanelBody } = components;
const { RichText, InnerBlocks } = blockEditor;
const __ = Drupal.t;

const settings = {
  title: __('Intro Text'),
  description: __('Summarize the content on a page or in a section'),
  icon: 'format-aside',

  edit({ attributes, className, setAttributes, isSelected, clientId }) {
    const { title } = attributes;

    return (
      <Fragment>
        <div
          className={ `${className} intro-text` }
          role="region"
        >
          <div className="intro-text__content">
            <InnerBlocks allowedBlocks={ ['core/paragraph', 'core/list'] } />
          </div>
        </div>
      </Fragment>
    );
  },

  save({ className, attributes }) {
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

registerBlockType(`${category.slug}/intro-text`, { category: category.slug, ...settings });
