const { blocks, data, element, components, editor, blockEditor } = wp;
const { registerBlockType } = blocks;
const { dispatch, select, useSelect } = data;
const { Fragment, useCallback, useState, useRef, useEffect } = element;
const { Toolbar, Placeholder, ToggleControl, PanelBody, TextareaControl } = components;
const { 
  RichText,
  AlignmentControl,
  InspectorControls,
  BlockControls,
  BlockIcon,
  TextControl,
  InnerBlocks,
} = blockEditor;
const __ = Drupal.t;
import edit from './block/edit';
import save from './block/save';
const table = {
  title: __('Table'),
  description: __('Table with theme variants'),
  icon: 'editor-table',
  attributes: {
    settings: {
      type: 'object',
    },
    buttonStates: {
      type: 'object',
      default: {
        intColBef: true,
        intColAft: true,
        intRowBef: true,
        intRowAft: true,
        delCol: true,
        delRow: true
      }
    },
    currentCell: {
      type: 'object',
      default: {
        row: '',
        col: ''
      }
    },
    dataBody: {
      type: 'array',
      source: 'query',
      default: [],
      selector: 'tbody tr',
      query: {
        bodyCells: {
          type: 'array',
          source: 'query',
          default: [],
          selector: 'td,th',
          query: {
            content: {
              type: 'string',
              source: 'html'
            }
          }
        }
      }
    },
    dataHead: {
      type: 'array',
      source: 'query',
      default: [],
      selector: 'th[scope="col"]',
      query: {
        content: {
          type: 'string',
          source: 'html'
        }
      }
    },
    showColHeadings: {
      type: 'boolean',
      default: false
    },
    useRowHeadings: {
      type: 'boolean',
      default: false
    },
    caption: {
      type: 'string',
      source: 'html',
      selector: 'caption'
    },
    showCaption: {
      type: 'boolean',
      default: false
    },
    numCols: {
      type: 'number',
      default: 3
    },
    numRows: {
      type: 'number',
      default: 2
    },
    showTable: {
      type: 'boolean',
      default: false
    },
    sortable: {
      type: 'boolean',
      default: false
    },
    searchable: {
      type: 'boolean',
      default: false
    },
    itemsPerPage: {
      type: 'number',
      default: 20
    },
    borderless: {
      type: 'boolean',
      default: false
    },
    striped: {
      type: 'boolean',
      default: false
    },
    fixedLayout: {
      type: 'boolean',
      default: false
    },
    tableId: {
      type: 'string',
    },
  },
  edit,
  save
};

const category = {
  slug: 'uswds',
  title: __('USWDS')
};

const currentCategories = select('core/blocks').getCategories().filter(item => item.slug !== category.slug);
dispatch('core/blocks').setCategories([ category, ...currentCategories ]);

registerBlockType(`${category.slug}/table`, { category: category.slug, ...table });
