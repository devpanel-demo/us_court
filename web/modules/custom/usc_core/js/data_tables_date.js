/**
 * @file
 * Data tables date.
 */
(($, Drupal, once) => {
  Drupal.behaviors.dataTablesDateSelect = {
    attach(context) {

      once('preselect-day', '#edit-field-date-updated-0-value', context).forEach(function (table) {
        let monthSelect = $('#edit-field-date-updated-0-value-month', context);
        let daySelect = $('#edit-field-date-updated-0-value-day', context);

        // Attach event listener for month select change.
        monthSelect.on("change", handleMonthChange);

        // Trigger the event initially to set the initial day value.
        handleMonthChange();

        // Function to handle month change event
        function handleMonthChange() {
          let selectedMonth = parseInt(monthSelect.val());
          let daysInMonth;
          let selectedDay;

          // Determine the number of days in the selected month.
          switch (selectedMonth) {
            case 3: // March
            case 12: // December
              daysInMonth = 31;
              selectedDay = "31";
              break;
            case 6: // June
            case 9: // September
              daysInMonth = 30;
              selectedDay = "30";
              break;
            default:
              daysInMonth = 0;
              selectedDay = "";
              break;
          }

          // Clear existing options and add only the valid options for the selected month.
          daySelect.empty();

          if (daysInMonth === 31) {
            daySelect.append(new Option(31, 31));
          } else if (daysInMonth === 30) {
            daySelect.append(new Option(30, 30));
          }
          else {
            daySelect.append(new Option('Day', ''));
          }
          daySelect.val(selectedDay);
        }
      });
    },
  };
})(jQuery, Drupal, once);
