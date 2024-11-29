const {RichText} = wp.blockEditor;

export default function save({attributes}) {
  const {
    dataBody,
    dataHead,
    showColHeadings,
    useRowHeadings,
    caption,
    sortable,
    borderless,
    striped,
    fixedLayout
  } = attributes;
  // Table Head.
  let tableHead;
  const tableHeadData = dataHead.map(function (cell, colIndex) {
    return <RichText.Content tagName="th" scope='col' role="columnheader" className={sortable ? 'sort' : ''}
                             data-sort={'colnum-' + colIndex} value={cell.content}/>;                 
  });
  if (tableHeadData.length) {
    tableHead = <thead>
    <tr>{tableHeadData}</tr>
    </thead>;
  }
  // Table body.
  let tableBodyData = dataBody
    .map(function (rows) {
      let rowCells = rows.bodyCells.map(function (cell, colIndex) {
        if (useRowHeadings === true && colIndex === 0) {
          return <RichText.Content tagName="th" className={'colnum-' + colIndex} scope='row' value={cell.content}/>;
        } else {
          return <RichText.Content tagName="td" className={'colnum-' + colIndex} value={cell.content}/>;
        }
      });
      return (<tr>{rowCells}</tr>);
    });
  if (tableBodyData.length) {
    return (
      <table
        className={'usa-table' + (borderless ? ' usa-table--borderless' : '') + (striped ? ' usa-table--striped' : '') + (fixedLayout ? ' fixed-layout' : '')}>
        <RichText.Content tagName="caption" value={caption}/>
        {showColHeadings && tableHead}
        <tbody className="list">{tableBodyData}</tbody>
      </table>
    );
  }
  return null;
}