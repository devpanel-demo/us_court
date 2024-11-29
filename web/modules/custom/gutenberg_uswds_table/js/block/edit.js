/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/
"use strict";

function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;
var _state = require("./state");
var _inspectorControls = _interopRequireDefault(require("./inspector-controls"));
var _tableControls = _interopRequireDefault(require("./table-controls"));
function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }
function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }
function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, _toPropertyKey(descriptor.key), descriptor); } }
function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : String(i); }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
function _callSuper(t, o, e) { return o = _getPrototypeOf(o), _possibleConstructorReturn(t, _isNativeReflectConstruct() ? Reflect.construct(o, e || [], _getPrototypeOf(t).constructor) : o.apply(t, e)); }
function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } else if (call !== void 0) { throw new TypeError("Derived constructors may only return object or undefined"); } return _assertThisInitialized(self); }
function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }
function _isNativeReflectConstruct() { try { var t = !Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); } catch (t) {} return (_isNativeReflectConstruct = function _isNativeReflectConstruct() { return !!t; })(); }
function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }
function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); Object.defineProperty(subClass, "prototype", { writable: false }); if (superClass) _setPrototypeOf(subClass, superClass); }
function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }
var _wp = wp,
  element = _wp.element,
  components = _wp.components,
  blockEditor = _wp.blockEditor;
var Fragment = element.Fragment;
var __experimentalNumberControl = components.__experimentalNumberControl,
  Placeholder = components.Placeholder,
  Button = components.Button,
  __experimentalRadio = components.__experimentalRadio,
  __experimentalRadioGroup = components.__experimentalRadioGroup,
  Spinner = components.Spinner;
var RichText = blockEditor.RichText,
  BlockIcon = blockEditor.BlockIcon;
var DrupalBlock = window.DrupalGutenberg.Components.DrupalBlock;
var Component = element.Component;
var __ = Drupal.t;
var _default = exports.default = function (_Component) {
  _inherits(_default, _Component);
  function _default() {
    _classCallCheck(this, _default);
    return _callSuper(this, _default, arguments);
  }
  _createClass(_default, [{
    key: "render",
    value: function render() {
      var _this$props = this.props,
        attributes = _this$props.attributes,
        className = _this$props.className,
        setAttributes = _this$props.setAttributes;
      var dataBody = attributes.dataBody,
        dataHead = attributes.dataHead,
        showColHeadings = attributes.showColHeadings,
        useRowHeadings = attributes.useRowHeadings,
        numCols = attributes.numCols,
        numRows = attributes.numRows,
        showTable = attributes.showTable,
        caption = attributes.caption,
        showCaption = attributes.showCaption,
        sortable = attributes.sortable,
        searchable = attributes.searchable,
        borderless = attributes.borderless,
        striped = attributes.striped,
        fixedLayout = attributes.fixedLayout,
        settings = attributes.settings;
      if (!dataHead.length) {
        for (var i = 0; i < numCols; i++) {
          dataHead[i] = {
            content: ''
          };
        }
      }
      var tableHead;
      var tableHeadData = dataHead.map(function (cell, colIndex) {
        var enabledControls = 'intColBef,intColAft,intRowAft,delCol';
        if (colIndex === 0) {
          enabledControls = 'intColAft,intRowAft';
        }
        return React.createElement(RichText, {
          tagName: "th",
          key: colIndex,
          value: cell.content,
          "data-pos": '-1,' + colIndex,
          "data-buttons": enabledControls,
          className: sortable ? 'sort' : '',
          onChange: function onChange(value) {
            var newHead = JSON.parse(JSON.stringify(dataHead));
            newHead[colIndex] = {
              content: value
            };
            setAttributes({
              dataHead: newHead
            });
          },
          unstableOnFocus: function unstableOnFocus(evt) {
            (0, _state.enterCellState)(evt, attributes, setAttributes);
          }
        });
      });
      if (tableHeadData.length) {
        tableHead = React.createElement("thead", null, React.createElement("tr", null, tableHeadData));
      }
      var tableBody,
        tableBodyData = dataBody.map(function (rows, rowIndex) {
          var rowCells = rows.bodyCells.map(function (cell, colIndex) {
            return React.createElement(RichText, {
              tagName: useRowHeadings && !colIndex ? 'th' : 'td',
              key: colIndex,
              value: cell.content,
              "data-pos": rowIndex + ',' + colIndex,
              "data-buttons": "intColBef,intColAft,intRowBef,intRowAft,delRow,delCol",
              onChange: function onChange(value) {
                var newBody = JSON.parse(JSON.stringify(dataBody));
                newBody[rowIndex].bodyCells[colIndex] = {
                  content: value
                };
                setAttributes({
                  dataBody: newBody
                });
              },
              unstableOnFocus: function unstableOnFocus(evt) {
                (0, _state.enterCellState)(evt, attributes, setAttributes);
              }
            });
          });
          return React.createElement("tr", null, rowCells);
        });
      if (tableBodyData.length) {
        tableBody = React.createElement("tbody", null, tableBodyData);
      }
      return React.createElement(Fragment, null, showTable ? React.createElement("div", {
        className: className
      }, searchable && React.createElement(Fragment, null, React.createElement("label", {
        class: "usa-label",
        for: "input-type-text"
      }, __('Search')), React.createElement("input", {
        class: "usa-input search",
        id: "input-type-text",
        name: "input-type-text",
        type: "text"
      })), React.createElement("table", {
        className: 'usa-table' + (borderless ? ' usa-table--borderless' : '') + (striped ? ' usa-table--striped' : '') + (fixedLayout ? ' fixed-layout' : '')
      }, showCaption && React.createElement(RichText, {
        tagName: "caption",
        "aria-label": __('Table caption text'),
        placeholder: __('Add caption'),
        value: caption,
        onChange: function onChange(value) {
          return setAttributes({
            caption: value
          });
        },
        unstableOnFocus: function unstableOnFocus(evt) {
          (0, _state.disableControls)(attributes, setAttributes);
        }
      }), showColHeadings && tableHead, tableBody)) : React.createElement(Placeholder, {
        icon: React.createElement(BlockIcon, {
          icon: "editor-table"
        }),
        label: __('Table Options'),
        className: "table-options"
      }, React.createElement("div", {
        className: "blocks-table__placeholder-form"
      }, React.createElement("div", {
        className: "options-wrapper"
      }, React.createElement(Fragment, null, React.createElement(__experimentalNumberControl, {
        label: __('Number of Columns'),
        isShiftStepEnabled: true,
        onChange: function onChange(newNumCols) {
          setAttributes({
            numCols: newNumCols
          });
        },
        shiftStep: 1,
        value: numCols
      }), React.createElement(__experimentalNumberControl, {
        label: __('Number of Rows'),
        isShiftStepEnabled: true,
        onChange: function onChange(newNumRows) {
          setAttributes({
            numRows: newNumRows
          });
        },
        shiftStep: 1,
        value: numRows
      }), React.createElement(Button, {
        variant: "primary",
        onClick: function onClick() {
          (0, _state.buildTable)(attributes, setAttributes);
        },
        text: __('Insert Table'),
        className: "is-primary"
      }))))), (0, _inspectorControls.default)(attributes, setAttributes), (0, _tableControls.default)(attributes, setAttributes));
    }
  }]);
  return _default;
}(Component);