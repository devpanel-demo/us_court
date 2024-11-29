"use strict";

(function (Drupal) {
  Drupal.behaviors.gutenbergExpandCollapseAll = {
    attach: function attach(context) {
      var groups = context.querySelectorAll('.operations-group');
      groups.forEach(function (group) {
        var expand = group.querySelector('.expand-link');
        var collapse = group.querySelector('.collapse-link');
        if (expand) {
          expand.onclick = function (e) {
            e.preventDefault();
            var accordionId = this.getAttribute('data-controls');
            var accordion = context.getElementById(accordionId);
            Drupal.toogleAccordions(group, accordion);
          };
        }
        if (collapse) {
          collapse.onclick = function (e) {
            e.preventDefault();
            var accordionId = this.getAttribute('data-controls');
            var accordion = context.getElementById(accordionId);
            Drupal.toogleAccordions(group, accordion, false);
          };
        }
      });
    }
  };
  Drupal.toogleAccordions = function (group, accordion) {
    var status = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : true;
    group.classList.toggle('collapsed');
    accordion.querySelectorAll('.usa-accordion__button').forEach(function (element) {
      element.ariaExpanded = status;
    });
    accordion.querySelectorAll('.usa-accordion__content').forEach(function (element) {
      if (status) {
        element.removeAttribute('hidden');
      } else {
        element.setAttribute('hidden', '');
      }
    });
  };
})(Drupal);
