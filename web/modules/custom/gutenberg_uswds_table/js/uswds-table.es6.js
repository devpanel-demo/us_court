((Drupal) => {

  /**
   * Expand or Collapse accordions created in Gutenberg.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attach expand or collapse accordions functionality.
   */
  Drupal.behaviors.gutenbergAdvancedTable = {
    attach(context) {
      // Find all tables with features enabled.
      const tables = context.querySelectorAll('div.wp-block-uswds-table');

      once('bind-table-list', 'div.wp-block-uswds-table', context).forEach(table => {
        const numCols = table.querySelector('table').rows[0].cells.length;
        const tableId = table.getAttribute('id');
        let colNames = [];
        for (let index = 0; index < numCols; index++) {
          colNames.push('colnum-' + index);
        }
        let options = {
          valueNames: colNames
        };

        var tableList = new List(tableId, options);

        tableList.on('searchComplete', function() {
          table.removeAttribute('sorted');
          table.querySelectorAll('th').forEach(header => {
            header.removeAttribute('title');
          });
        });
        // Sort classes.
        const sortHeaders = table.querySelectorAll('th.sort');
        sortHeaders.forEach(header => {
          header.addEventListener("click", function (e) {
            // Remove title of all headers.
            sortHeaders.forEach(header => {
              header.removeAttribute('title');
            });
            // Add sort attribute.
            table.setAttribute('sorted', header.getAttribute('data-sort'));
            // Add header title.
            var message = Drupal.t("Column sorted in ascending order.");
            if (header.classList.contains("desc")) {
              message = Drupal.t("Column sorted in descending order.");
            }
            header.setAttribute('title', message);
          });
        });
      });
    }
  };
})(Drupal);
