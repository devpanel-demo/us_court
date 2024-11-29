/**
 * Insers a new row or column.
 * @param {String} type row or col
 * @param {String} location before or after the selected cell
 */
export function insertToTable(type, location, {currentCell, numRows, numCols, dataBody, dataHead}, setAttributes) {
  let selectedRow = currentCell.row;
  let selectedCol = currentCell.col;
  // If we are inserting after, add 1 to insert in the right place.
  if (location === 'after') {
    selectedRow++;
    selectedCol++;
  }
  let endingRows = numRows;
  // 2 vars to track columns: allCols includes any potential THs; endingCols will be saved as numCols.
  let allCols = numCols, endingCols = numCols;
  let newBody = JSON.parse(JSON.stringify(dataBody));
  let newHead = JSON.parse(JSON.stringify(dataHead));
  if (type === 'row') {
    // First create a row.
    let newRow = {
      bodyCells: []
    };
    for (let c = 0; c < allCols; c++) {
      newRow.bodyCells.push({content: ''});
    }
    // Insert the row.
    newBody.splice(selectedRow, 0, newRow);
    // Increase the total number of rows.
    endingRows++;
  } else if (type === 'col') {
    // Update the body.
    for (let r = 0; r < endingRows; r++) {
      // Create and add a new cell.
      let newCell = {content: ''};
      newBody[r].bodyCells.splice(selectedCol, 0, newCell);
    }
    // Update thead.
    let newTh = {content: ''};
    newHead.splice(selectedCol, 0, newTh);
    // Increase the total number of cols
    endingCols++;
  }
  setAttributes({
    dataBody: newBody,
    dataHead: newHead,
    numRows: endingRows,
    numCols: endingCols
  });
}

/**
 * Delete the selected row or column.
 * @param {String} type The deletion type row or col
 */
export function doDelete(type, {currentCell, numRows, numCols, dataBody, dataHead}, setAttributes) {
  let selectedRow = currentCell.row, selectedCol = currentCell.col,
    endingRows = numRows, endingCols = numCols,
    newBody = JSON.parse(JSON.stringify(dataBody)), newHead = JSON.parse(JSON.stringify(dataHead));
  if (type === 'row') {
    newBody.splice(selectedRow, 1);
    endingRows--;
  } else if (type === 'col') {
    endingCols--;
    // Update the body.
    for (let r = 0; r < endingRows; r++) {
      // Delete the cell.
      newBody[r].bodyCells.splice(selectedCol, 1);
    }
    // If there is a thead, update that too.
    newHead.splice(selectedCol, 1);
  }
  setAttributes({
    dataBody: newBody,
    dataHead: newHead,
    numRows: endingRows,
    numCols: endingCols
  });
}

/**
 * Updates the toolbar controls and current cell.
 * @param {Object} evt the event
 */
export function enterCellState(evt, {buttonStates}, setAttributes) {
  // Set enabled buttons.
  const buttonsToEnable = evt.target.dataset.buttons.split(',');
  let newButtonStates = {};
  for (let prop in buttonStates) {
    newButtonStates[prop] = true;
  }
  buttonsToEnable.forEach(button => {
    newButtonStates[button] = false;
  });
  // Set current cell.
  const cellPos = evt.target.dataset.pos.split(',');
  setAttributes({
    buttonStates: newButtonStates,
    currentCell: {row: cellPos[0], col: cellPos[1]}
  });
}

/**
 * Disables all toolbar controls.
 */
export function disableControls({buttonStates}, setAttributes) {
  let newButtonStates = {};
  for (let prop in buttonStates) {
    newButtonStates[prop] = true;
  }
  setAttributes({
    buttonStates: newButtonStates
  });
}

/**
 * Splits a row in columns.
 * @param {String} line The row to be splited
 * @returns {Array}
 */
function splitQuotes(line) {
  if(line.indexOf('"') < 0) 
    return line.split(',')

  let result = [], cell = '', quote = false;
  for(let i = 0; i < line.length; i++) {
    const char = line[i]
    if(char == '"' && line[i+1] == '"') {
      cell += char
      i++
    } else if(char == '"') {
      quote = !quote;
    } else if(!quote && char == ',') {
      result.push(cell)
      cell = ''
    } else {
      cell += char
    }
    if ( i == line.length-1 && cell) {
      result.push(cell)
    }
  }
  return result
}

/**
 * Builds the initial table.
 */
export function buildTable({numCols, numRows}, setAttributes) {
  // Only build the table and hide the form if there are rows and columns.
  if (numCols > 0 && numRows > 0) {
    // Build the thead attribute array.
    let newHead = [];
    // Add placeholders for the THs.
    for (let i = 0; i < numCols; i++) {
      newHead[i] = {content: ''};
    }
    // Build the tbody attribute array.
    let newBody = [];
    for (let row = 0; row < numRows; row++) {
      let thisRow = {bodyCells: []};
      for (let col = 0; col < numCols; col++) {
        thisRow.bodyCells[col] = {content: ''};
      }
      newBody[row] = thisRow;
    }
    // Save attributes.
    setAttributes({
      dataHead: newHead,
      dataBody: newBody,
      showTable: true
    });
  }
}