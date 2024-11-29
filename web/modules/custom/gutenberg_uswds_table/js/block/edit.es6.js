const {element, components, blockEditor} = wp;
const {Fragment} = element;
const {
  __experimentalNumberControl,
  Placeholder,
  Button,
  __experimentalRadio,
  __experimentalRadioGroup,
  Spinner,
} = components;
const {RichText, BlockIcon} = blockEditor;
const {DrupalBlock} = window.DrupalGutenberg.Components;
const {Component} = element;
const __ = Drupal.t;

import {
  enterCellState,
  disableControls,
  setBlockIdFromFile,
  buildTable
} from './state';

import getInspectorControls from './inspector-controls';
import getTableControls from './table-controls';

export default class extends Component {
  constructor() {
    super(...arguments);
  }

  render() {
    let {attributes, className, setAttributes} = this.props;
    const {
      dataBody,
      dataHead,
      showColHeadings,
      useRowHeadings,
      numCols,
      numRows,
      showTable,
      caption,
      showCaption,
      sortable,
      searchable,
      borderless,
      striped,
      fixedLayout,
      settings
    } = attributes;
    // Creating Header.
    if (!dataHead.length) {
      // Add placeholders for the THs when dataHead is empty.
      for (let i = 0; i < numCols; i++) {
        dataHead[i] = {content: ''};
      }
    }
    let tableHead;
    const tableHeadData = dataHead.map(function (cell, colIndex) {
      let enabledControls = 'intColBef,intColAft,intRowAft,delCol';
      // If row headings are enabled, and this is the very first col TH, don't allow insert col before or delete col.
      if (colIndex === 0) {
        enabledControls = 'intColAft,intRowAft';
      }
      return (
        <RichText
          tagName='th'
          key={colIndex}
          value={cell.content}
          data-pos={'-1,' + colIndex}
          data-buttons={enabledControls}
          className={sortable ? 'sort': ''}
          onChange={(value) => {
            let newHead = JSON.parse(JSON.stringify(dataHead));
            // Replace the old cell
            newHead[colIndex] = {content: value};
            // Set the attribute
            setAttributes({
              dataHead: newHead
            });
          }}
          unstableOnFocus={evt => {
            enterCellState(evt, attributes, setAttributes);
          }}
        />
      );
    });
    if (tableHeadData.length) {
      tableHead = <thead>
      <tr>{tableHeadData}</tr>
      </thead>;
    }
    // Creating Body.
    let tableBody, tableBodyData = dataBody.map(function (rows, rowIndex) {
      let rowCells = rows.bodyCells.map((cell, colIndex) =>
        (
          <RichText
            tagName={(useRowHeadings && !colIndex) ? 'th' : 'td'}
            key={colIndex}
            value={cell.content}
            data-pos={rowIndex + ',' + colIndex}
            data-buttons='intColBef,intColAft,intRowBef,intRowAft,delRow,delCol'
            onChange={(value) => {
              let newBody = JSON.parse(JSON.stringify(dataBody));
              // Replace the old cell.
              newBody[rowIndex].bodyCells[colIndex] = {content: value};
              // Set the attribute.
              setAttributes({
                dataBody: newBody
              });
            }}
            unstableOnFocus={evt => {
              enterCellState(evt, attributes, setAttributes);
            }}
          />
        )
      );
      return (<tr>{rowCells}</tr>);
    });
    if (tableBodyData.length) {
      tableBody = <tbody>{tableBodyData}</tbody>;
    }
    
    return (
      <Fragment>
        {showTable ?
          <div className={className}>
            {searchable &&
              <Fragment>
                <label class="usa-label" for="input-type-text">{__('Search')}</label>
                <input
                  class="usa-input search"
                  id="input-type-text"
                  name="input-type-text"
                  type="text"
                />
              </Fragment>
            }
            <table
              className={'usa-table' + (borderless ? ' usa-table--borderless' : '') + (striped ? ' usa-table--striped' : '') + (fixedLayout ? ' fixed-layout' : '')}>
              {showCaption &&
                <RichText
                  tagName="caption"
                  aria-label={__('Table caption text')}
                  placeholder={__('Add caption')}
                  value={caption}
                  onChange={(value) =>
                    setAttributes({caption: value})
                  }
                  unstableOnFocus={evt => {
                    disableControls(attributes, setAttributes);
                  }}
                />
              }
              {showColHeadings &&
                tableHead
              }
              {tableBody}
            </table>
          </div>            
        :
          <Placeholder
            icon={<BlockIcon icon="editor-table"/>}
            label={__('Table Options')}
            className="table-options"
          >
            <div className="blocks-table__placeholder-form">
              <div className="options-wrapper">
                <Fragment>
                  <__experimentalNumberControl
                    label={__('Number of Columns')}
                    isShiftStepEnabled={true}
                    onChange={(newNumCols) => {
                      setAttributes({numCols: newNumCols})
                    }}
                    shiftStep={1}
                    value={numCols}
                  />
                  <__experimentalNumberControl
                    label={__('Number of Rows')}
                    isShiftStepEnabled={true}
                    onChange={(newNumRows) => {
                      setAttributes({numRows: newNumRows})
                    }}
                    shiftStep={1}
                    value={numRows}
                  />
                  <Button
                    variant='primary'
                    onClick={() => {
                      buildTable(attributes, setAttributes)
                    }}
                    text={__('Insert Table')}
                    className="is-primary"
                  />
                </Fragment>
              </div>
            </div>
          </Placeholder>
        }
        { getInspectorControls(attributes, setAttributes) }
        { getTableControls(attributes, setAttributes) }
      </Fragment>
    );
  }
}