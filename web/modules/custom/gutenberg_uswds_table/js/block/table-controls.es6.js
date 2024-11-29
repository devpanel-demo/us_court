const {components, blockEditor} = wp;
const {
  Toolbar,
  ToolbarDropdownMenu
} = components;
const {BlockControls} = blockEditor;
const __ = Drupal.t;

import {
	insertToTable,
  doDelete
} from './state';

export default function getTableControls(attributes, setAttributes) {
  // Toolbar Controls.
  const tableControls = [
    {
      icon: 'table-col-before',
      title: __('Insert column before'),
      isDisabled: attributes.buttonStates.intColBef,
      onClick: () => insertToTable('col', 'before', attributes, setAttributes),
    },
    {
      icon: 'table-col-after',
      title: __('Insert column after'),
      isDisabled: attributes.buttonStates.intColAft,
      onClick: () => insertToTable('col', 'after', attributes, setAttributes),
    },
    {
      icon: 'table-row-before',
      title: __('Insert row before'),
      isDisabled: attributes.buttonStates.intRowBef,
      onClick: () => insertToTable('row', 'before', attributes, setAttributes),
    },
    {
      icon: 'table-row-after',
      title: __('Insert row after'),
      isDisabled: attributes.buttonStates.intRowAft,
      onClick: () => insertToTable('row', 'after', attributes, setAttributes),
    },
    {
      icon: 'table-col-delete',
      title: __('Delete column'),
      isDisabled: attributes.buttonStates.delCol,
      onClick: () => doDelete('col', attributes, setAttributes),
    },
    {
      icon: 'table-row-delete',
      title: __('Delete row'),
      isDisabled: attributes.buttonStates.delRow,
      onClick: () => doDelete('row', attributes, setAttributes),
    }
  ];
  return <BlockControls key='form-controls'>
    <Toolbar>
      <ToolbarDropdownMenu
        icon='screenoptions'
        label={__('Edit table')}
        controls={tableControls}
      />
    </Toolbar>
  </BlockControls>
}