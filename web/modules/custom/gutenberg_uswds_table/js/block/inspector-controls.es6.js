const {components, blockEditor} = wp;
const {
  PanelBody,
  ToggleControl,
  __experimentalNumberControl,
  __experimentalText
} = components;
const {InspectorControls} = blockEditor;
const __ = Drupal.t;

export default function getInspectorControls(attributes, setAttributes) {
  const {
    showColHeadings,
    useRowHeadings,
    showCaption,
    sortable,
    searchable,
    itemsPerPage,
    borderless,
    striped,
    fixedLayout,
    tableId
  } = attributes;
  return <InspectorControls>
    <PanelBody title={__('General Settings')}>
      <ToggleControl
        label={__('Show table caption')}
        checked={showCaption}
        onChange={() => {
          setAttributes({showCaption: !showCaption})
        }}
      />
      <ToggleControl
        label={__('Enable Columns Header')}
        checked={showColHeadings}
        onChange={() => {
          if (showColHeadings) {
            setAttributes({sortable: false})
          }
          setAttributes({showColHeadings: !showColHeadings})
        }}
      />
      <ToggleControl
        label={__('Enable Rows Header')}
        checked={useRowHeadings}
        onChange={() => {
          setAttributes({useRowHeadings: !useRowHeadings})
        }}
      />
      {showColHeadings &&
        <ToggleControl
          label={__('Sortable')}
          checked={sortable}
          onChange={() => {
            setAttributes({sortable: !sortable})
          }}
        />
      }
      <ToggleControl
        label={__('Searchable')}
        checked={searchable}
        onChange={() => {
          setAttributes({searchable: !searchable})
        }}
      />
    </PanelBody>
    <PanelBody title={__('Styling')}>
      <ToggleControl
        label={__('Borderless')}
        checked={borderless}
        onChange={() => {
          setAttributes({borderless: !borderless})
        }}
      />
      <ToggleControl
        label={__('Striped')}
        checked={striped}
        onChange={() => {
          setAttributes({striped: !striped})
        }}
      />
      <ToggleControl
        label={__('Fixed Layout')}
        checked={fixedLayout}
        onChange={() => {
          setAttributes({fixedLayout: !fixedLayout})
        }}
      />
    </PanelBody>
  </InspectorControls>
}