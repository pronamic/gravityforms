/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = "./js/src/field-map/index.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./js/src/components/MppingValueField.js":
/*!***********************************************!*\
  !*** ./js/src/components/MppingValueField.js ***!
  \***********************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
  value: true
});
exports["default"] = void 0;

function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

/**
 * WordPress dependencies
 */
var _wp$element = wp.element,
    Component = _wp$element.Component,
    Fragment = _wp$element.Fragment;
var __ = wp.i18n.__;

var MappingValueField = /*#__PURE__*/function (_Component) {
  _inherits(MappingValueField, _Component);

  var _super = _createSuper(MappingValueField);

  function MappingValueField() {
    _classCallCheck(this, MappingValueField);

    return _super.apply(this, arguments);
  }

  _createClass(MappingValueField, [{
    key: "componentDidMount",

    /**
     * Initialize Gravity Forms Merge Tag UI on mount.
     *
     * @since 2.5
     */
    value: function componentDidMount() {
      var _this = this;

      this.$input = jQuery(this.input);

      if (typeof form !== 'undefined') {
        this.mergeTagsObj = new gfMergeTagsObj(form, this.$input);
      }

      this.$input.on('propertychange', function (e) {
        _this.props.updateMapping(_objectSpread(_objectSpread({}, _this.props.mapping), {}, {
          custom_value: e.target.value
        }), _this.props.index);
      });
    }
    /**
     * Destroy merge tag object and remove event listeners.
     *
     * @since 2.5
     */

  }, {
    key: "componentWillUnmount",
    value: function componentWillUnmount() {
      this.$input.off('propertychange');

      if (typeof this.mergeTagsObj !== 'undefined') {
        this.mergeTagsObj.destroy();
      }
    }
  }, {
    key: "render",
    value: function render() {
      var _this2 = this;

      var containerClass = this.props.mergeTagSupport ? 'gform-settings-generic-map__custom gform-settings-input__container--with-merge-tag' : 'gform-settings-generic-map__custom';
      var inputClass = this.props.mergeTagSupport ? 'merge-tag-support mt-position-right' : '';
      return /*#__PURE__*/React.createElement("span", {
        className: containerClass
      }, /*#__PURE__*/React.createElement("input", {
        ref: function ref(input) {
          return _this2.input = input;
        },
        id: this.props.fieldId,
        type: "text",
        className: inputClass,
        value: this.props.mapping.custom_value,
        placeholder: this.props.valueField.placeholder,
        onChange: function onChange(e) {
          return _this2.props.updateMapping(_objectSpread(_objectSpread({}, _this2.props.mapping), {}, {
            custom_value: e.target.value
          }), _this2.props.index);
        }
      }), /*#__PURE__*/React.createElement("button", {
        className: "gform-settings-generic-map__reset",
        onClick: function onClick(e) {
          e.preventDefault();

          _this2.props.updateMapping(_objectSpread(_objectSpread({}, _this2.props.mapping), {}, {
            value: '',
            custom_value: ''
          }), _this2.props.index);
        }
      }, /*#__PURE__*/React.createElement("span", {
        className: "screen-reader-text"
      }, __('Remove Custom Value', 'gravityforms'))));
    }
  }]);

  return MappingValueField;
}(Component);

exports["default"] = MappingValueField;

/***/ }),

/***/ "./js/src/components/Tooltips.js":
/*!***************************************!*\
  !*** ./js/src/components/Tooltips.js ***!
  \***************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
  value: true
});
exports["default"] = void 0;

function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

/**
 * WordPress dependencies
 */
var _wp$element = wp.element,
    Component = _wp$element.Component,
    Fragment = _wp$element.Fragment;
var __ = wp.i18n.__;

var Tooltip = /*#__PURE__*/function (_Component) {
  _inherits(Tooltip, _Component);

  var _super = _createSuper(Tooltip);

  function Tooltip() {
    _classCallCheck(this, Tooltip);

    return _super.apply(this, arguments);
  }

  _createClass(Tooltip, [{
    key: "render",
    value: function render() {
      if (this.props.tooltip) {
        return /*#__PURE__*/React.createElement("button", {
          type: "button",
          className: "gf_tooltip tooltip",
          "aria-label": this.props.tooltip
        }, /*#__PURE__*/React.createElement("i", {
          className: "gform-icon gform-icon--question-mark",
          "aria-hidden": "true"
        }));
      } else {
        return null;
      }
    }
  }]);

  return Tooltip;
}(Component);

exports["default"] = Tooltip;

/***/ }),

/***/ "./js/src/field-map/index.js":
/*!***********************************!*\
  !*** ./js/src/field-map/index.js ***!
  \***********************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _mapping = _interopRequireDefault(__webpack_require__(/*! ./mapping */ "./js/src/field-map/mapping.js"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

/**
 * WordPress dependencies
 */
var _wp$element = wp.element,
    Component = _wp$element.Component,
    render = _wp$element.render;
/**
 * Internal dependencies
 */

var FieldMap = /*#__PURE__*/function (_Component) {
  _inherits(FieldMap, _Component);

  var _super = _createSuper(FieldMap);

  function FieldMap() {
    var _this;

    _classCallCheck(this, FieldMap);

    _this = _super.apply(this, arguments);
    /**
     * State is managed via the value attribute on a hidden input that
     * is output via the markup() method in class-generic-map.php. This value
     * is set on initial load via that method in the php.
     */

    _this.state = {
      mapping: JSON.parse(document.querySelector("[name=\"".concat(_this.props.input, "\"]")).value)
    };
    _this.addMapping = _this.addMapping.bind(_assertThisInitialized(_this));
    _this.deleteMapping = _this.deleteMapping.bind(_assertThisInitialized(_this));
    _this.getMapping = _this.getMapping.bind(_assertThisInitialized(_this));
    _this.updateMapping = _this.updateMapping.bind(_assertThisInitialized(_this));
    return _this;
  }

  _createClass(FieldMap, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      this.populateRequiredMappings(); // Ensure there is at least one item.

      if (this.getRequiredChoices().length === 0 && this.getMapping().length < 1) {
        this.addMapping(0);
      }
    } // # MAPPING DATA METHODS ------------------------------------------------------------------------------------------

    /**
     * Add a new mapping item.
     *
     * @param {integer} index Index to add item at.
     */

  }, {
    key: "addMapping",
    value: function addMapping(index) {
      var _this$props$keyField = this.props.keyField,
          allow_custom = _this$props$keyField.allow_custom,
          choices = _this$props$keyField.choices;
      var mapping = this.getMapping(),
          key = choices.length === 0 && allow_custom ? 'gf_custom' : '';
      mapping.splice(index + 1, 0, {
        key: key,
        custom_key: '',
        value: '',
        custom_value: ''
      });
      this.setMapping(mapping);
    }
    /**
     * Remove a mapping item.
     *
     * @param {integer} index Index of item to remove.
     */

  }, {
    key: "deleteMapping",
    value: function deleteMapping(index) {
      var mapping = this.getMapping();
      mapping.splice(index, 1);
      this.setMapping(mapping);
    }
    /**
     * Get current mappings.
     *
     * @returns {array}
     */

  }, {
    key: "getMapping",
    value: function getMapping() {
      return this.state.mapping;
    }
    /**
     * Set current mappings.
     *
     * @param {object} mapping Collection of field mappings.
     */

  }, {
    key: "setMapping",
    value: function setMapping(mapping) {
      var input = this.props.input;
      this.setState({
        mapping: mapping
      });
      document.querySelector("[name=\"".concat(input, "\"]")).value = JSON.stringify(mapping);
    }
    /**
     * Update a mapping item.
     *
     * @param {object} item Mapping item.
     * @param {integer} index Index of item to update.
     */

  }, {
    key: "updateMapping",
    value: function updateMapping(item, index) {
      var mapping = this.getMapping();

      if (!item.key) {
        item.value = '';
      }

      mapping[index] = item;
      this.setMapping(mapping);
    } // # CHOICE METHODS ------------------------------------------------------------------------------------------------

    /**
     * Get choice properties by name.
     *
     * @since 2.5
     *
     * @param {string} name Choice name.
     * @param {array} choices Choices to search in.
     *
     * @returns {boolean|object}
     */

  }, {
    key: "getChoice",
    value: function getChoice(name) {
      var choices = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;

      if (!choices) {
        choices = this.props.keyField.choices;
      }

      for (var i = 0; i < choices.length; i++) {
        var choice = choices[i],
            choiceName = choice.name || choice.value;

        if (choiceName === name) {
          return choices[i];
        }

        if (choice.choices) {
          var foundChoice = this.getChoice(name, choice.choices);

          if (foundChoice) {
            return foundChoice;
          }
        }
      }

      return false;
    }
    /**
     * Get names of mapped choices.
     */

  }, {
    key: "getMappedChoices",
    value: function getMappedChoices() {
      var mapping = this.getMapping();
      return mapping.filter(function (m) {
        return m.key && m.key !== 'gf_custom';
      }).map(function (m) {
        return m.key;
      });
    }
    /**
     * Get names of required choices.
     *
     * @returns {array}
     */

  }, {
    key: "getRequiredChoices",
    value: function getRequiredChoices() {
      var _this$props$keyField2 = this.props.keyField,
          choices = _this$props$keyField2.choices,
          display_all = _this$props$keyField2.display_all;
      var requiredChoices = [];

      for (var i = 0; i < choices.length; i++) {
        var choice = choices[i];

        if (choice.required || display_all) {
          requiredChoices.push(choice.name || choice.value);
        }

        if (choice.choices) {
          for (var ii = 0; ii < choice.choices.length; ii++) {
            var subChoice = choice.choices[ii];

            if (subChoice.required || display_all) {
              requiredChoices.push(subChoice.name || subChoice.value);
            }
          }
        }
      }

      return requiredChoices;
    }
    /**
     * Populate mapping with required choices.
     */

  }, {
    key: "populateRequiredMappings",
    value: function populateRequiredMappings() {
      var mapping = this.getMapping();
      var requiredChoices = this.getRequiredChoices(); // Get mapped fields.

      var mappedFields = mapping.map(function (mapping) {
        return mapping.key;
      }); // Loop through required choices. If not mapped, add to mapping.

      for (var i = 0; i < requiredChoices.length; i++) {
        // If field is mapped, skip.
        if (mappedFields.includes(requiredChoices[i])) {
          continue;
        } // Add to mapping.


        mapping.push({
          key: requiredChoices[i],
          custom_key: '',
          value: '',
          custom_value: ''
        });
      } // Auto populate default values.


      for (var _i = 0; _i < mapping.length; _i++) {
        // If field have a stored value already, skip.
        if (mapping[_i].value !== '') {
          continue;
        }

        var choice = this.getChoice(mapping[_i].key); // If choice have a default value, get it and set it as value.

        if (choice && 'default_value' in choice) {
          mapping[_i].value = choice.default_value;
        }
      }

      this.setMapping(mapping);
    } // Count how many possible choices there are.

  }, {
    key: "countKeyFieldChoices",
    value: function countKeyFieldChoices() {
      var choices = this.props.keyField.choices;
      var count = 0;

      for (var i = 0; i < choices.length; i++) {
        if (choices[i].choices) {
          count += choices[i].choices.length;
        } else {
          count++;
        }
      }

      return count;
    } // # RENDER METHODS ------------------------------------------------------------------------------------------------

  }, {
    key: "render",
    value: function render() {
      var _this2 = this;

      var _this$props = this.props,
          keyField = _this$props.keyField,
          invalidChoices = _this$props.invalidChoices,
          limit = _this$props.limit,
          valueField = _this$props.valueField,
          input = _this$props.input,
          inputType = _this$props.inputType,
          mergeTagSupport = _this$props.mergeTagSupport;
      var mapping = this.getMapping();
      var keyCount = this.countKeyFieldChoices();
      return /*#__PURE__*/React.createElement("table", {
        className: "gform-settings-generic-map__table",
        cellSpacing: "0",
        cellPadding: "0"
      }, /*#__PURE__*/React.createElement("tbody", null, /*#__PURE__*/React.createElement("tr", {
        className: "gform-settings-generic-map__row"
      }, /*#__PURE__*/React.createElement("th", {
        className: "gform-settings-generic-map__column gform-settings-generic-map__column--heading gform-settings-generic-map__column--key"
      }, keyField.title), /*#__PURE__*/React.createElement("th", {
        className: "gform-settings-generic-map__column gform-settings-generic-map__column--heading gform-settings-generic-map__column--value"
      }, valueField.title), /*#__PURE__*/React.createElement("th", {
        className: "gform-settings-generic-map__column gform-settings-generic-map__column--heading gform-settings-generic-map__column--error"
      }), /*#__PURE__*/React.createElement("th", {
        className: "gform-settings-generic-map__column gform-settings-generic-map__column--heading gform-settings-generic-map__column--buttons"
      })), mapping.map(function (m, index) {
        var selectedChoice = _this2.getChoice(m.key);

        return /*#__PURE__*/React.createElement(_mapping["default"], {
          key: index,
          mapping: m,
          choice: selectedChoice,
          mappedChoices: _this2.getMappedChoices(),
          isInvalid: m.key && invalidChoices.includes(m.key),
          keyField: keyField,
          valueField: valueField,
          canAdd: keyField.allow_custom && (limit === 0 || mapping.length <= limit) || !keyField.allow_custom && mapping.length < keyCount,
          canDelete: mapping.length > 1 && !selectedChoice.required && !keyField.display_all,
          addMapping: _this2.addMapping,
          deleteMapping: _this2.deleteMapping,
          updateMapping: _this2.updateMapping,
          index: index,
          inputId: input,
          inputType: inputType,
          mergeTagSupport: mergeTagSupport
        });
      })));
    }
  }]);

  return FieldMap;
}(Component);

window.initializeFieldMap = function (container, props) {
  render( /*#__PURE__*/React.createElement(FieldMap, props), document.getElementById(container));
};

/***/ }),

/***/ "./js/src/field-map/mapping.js":
/*!*************************************!*\
  !*** ./js/src/field-map/mapping.js ***!
  \*************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
  value: true
});
exports["default"] = void 0;

var _Tooltips = _interopRequireDefault(__webpack_require__(/*! ../components/Tooltips */ "./js/src/components/Tooltips.js"));

var _MppingValueField = _interopRequireDefault(__webpack_require__(/*! ../components/MppingValueField */ "./js/src/components/MppingValueField.js"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

/**
 * WordPress dependencies
 */
var _wp$element = wp.element,
    Component = _wp$element.Component,
    Fragment = _wp$element.Fragment;
var __ = wp.i18n.__;

var Mapping = /*#__PURE__*/function (_Component) {
  _inherits(Mapping, _Component);

  var _super = _createSuper(Mapping);

  function Mapping() {
    _classCallCheck(this, Mapping);

    return _super.apply(this, arguments);
  }

  _createClass(Mapping, [{
    key: "renderRequiredSpan",
    value: function renderRequiredSpan() {
      var choice = this.props.choice;
      var fieldId = this.getKeyInputId();

      if (choice.required) {
        return /*#__PURE__*/React.createElement("span", {
          className: "required",
          id: fieldId
        }, "*");
      } else {
        return null;
      }
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props = this.props,
          isInvalid = _this$props.isInvalid,
          index = _this$props.index;
      return /*#__PURE__*/React.createElement("tr", {
        className: "gform-settings-generic-map__row"
      }, /*#__PURE__*/React.createElement("td", {
        className: "gform-settings-generic-map__column gform-settings-generic-map__column--key"
      }, this.getKeyInput(index)), /*#__PURE__*/React.createElement("td", {
        className: "gform-settings-generic-map__column gform-settings-generic-map__column--value"
      }, this.getValueInput()), /*#__PURE__*/React.createElement("td", {
        className: "gform-settings-generic-map__column gform-settings-generic-map__column--error"
      }, isInvalid && /*#__PURE__*/React.createElement("svg", {
        width: "22",
        height: "22",
        fill: "none",
        xmlns: "http://www.w3.org/2000/svg"
      }, /*#__PURE__*/React.createElement("path", {
        d: "M11 22C4.9249 22 0 17.0751 0 11S4.9249 0 11 0s11 4.9249 11 11-4.9249 11-11 11z",
        fill: "#E54C3B"
      }), /*#__PURE__*/React.createElement("path", {
        fillRule: "evenodd",
        clipRule: "evenodd",
        d: "M9.9317 5.0769a.1911.1911 0 00-.1909.2006l.3708 7.4158a.8895.8895 0 001.7768 0l.3708-7.4158a.1911.1911 0 00-.1909-.2006H9.9317zm2.3375 10.5769c0 .701-.5682 1.2693-1.2692 1.2693-.701 0-1.2692-.5683-1.2692-1.2693 0-.7009.5682-1.2692 1.2692-1.2692.701 0 1.2692.5683 1.2692 1.2692z",
        fill: "#fff"
      }))), /*#__PURE__*/React.createElement("td", {
        className: "gform-settings-generic-map__column gform-settings-generic-map__column--buttons"
      }, this.getAddButton(), this.getDeleteButton()));
    }
  }, {
    key: "getValueInputId",
    value: function getValueInputId() {
      var _this$props2 = this.props,
          inputId = _this$props2.inputId,
          inputType = _this$props2.inputType,
          index = _this$props2.index,
          mapping = _this$props2.mapping;

      switch (inputType) {
        case 'generic_map':
        case 'dynamic_field_map':
          return "".concat(inputId, "_custom_value_").concat(index);

        default:
          return "".concat(inputId, "_").concat(mapping.key);
      }
    }
  }, {
    key: "getKeyInputId",
    value: function getKeyInputId() {
      var _this$props3 = this.props,
          inputId = _this$props3.inputId,
          inputType = _this$props3.inputType,
          index = _this$props3.index,
          mapping = _this$props3.mapping;

      switch (inputType) {
        case 'generic_map':
        case 'dynamic_field_map':
          return "".concat(inputId, "_custom_key_").concat(index);

        default:
          return "".concat(inputId, "_").concat(mapping.key, "_key");
      }
    } // # KEY COLUMN ----------------------------------------------------------------------------------------------------

    /**
     * Prepare input for key column.
     * If choice is required, returns only text.
     *
     * @returns {*}
     */

  }, {
    key: "getKeyInput",
    value: function getKeyInput(mapIndex) {
      var _this$props4 = this.props,
          choice = _this$props4.choice,
          keyField = _this$props4.keyField,
          index = _this$props4.index,
          mapping = _this$props4.mapping,
          updateMapping = _this$props4.updateMapping;
      var choices = keyField.choices,
          display_all = keyField.display_all,
          placeholder = keyField.placeholder;
      var fieldId = this.getKeyInputId(); // If currently selected choice is required or we are displaying all keys, display label.

      if (choice.required || display_all) {
        return /*#__PURE__*/React.createElement(Fragment, null, /*#__PURE__*/React.createElement("label", null, choice.label, " ", this.renderRequiredSpan(), " "), /*#__PURE__*/React.createElement(_Tooltips["default"], {
          tooltip: choice.tooltip
        }));
      } // If selected choice is custom key, display input.


      if (mapping.key === 'gf_custom') {
        return /*#__PURE__*/React.createElement("span", {
          className: "gform-settings-generic-map__custom"
        }, /*#__PURE__*/React.createElement("input", {
          id: fieldId,
          type: "text",
          value: mapping.custom_key,
          placeholder: placeholder,
          onChange: function onChange(e) {
            return updateMapping(_objectSpread(_objectSpread({}, mapping), {}, {
              custom_key: e.target.value
            }), index);
          }
        }), choices.length > 0 && /*#__PURE__*/React.createElement("button", {
          className: "gform-settings-generic-map__reset",
          onClick: function onClick(e) {
            e.preventDefault();
            updateMapping(_objectSpread(_objectSpread({}, mapping), {}, {
              key: '',
              custom_key: ''
            }), index);
          }
        }, /*#__PURE__*/React.createElement("span", {
          className: "screen-reader-text"
        }, __('Remove Custom Key', 'gravityforms'))));
      }

      return /*#__PURE__*/React.createElement("select", {
        id: fieldId,
        value: mapping.key,
        onChange: function onChange(e) {
          return updateMapping(_objectSpread(_objectSpread({}, mapping), {}, {
            key: e.target.value
          }), index);
        }
      }, this.getKeyOptions(mapIndex));
    }
    /**
     * Get options for key drop down.
     *
     * @since 2.5
     *
     * @param {integer} mapIndex       Index of current mapping object.
     * @param {array}   choices        Choices to build options from.
     * @param {boolean} addPlaceholder Include the "Select a Field" choice.
     * @param {boolean} addCustomKey   Include the "Add Custom Key" choice.
     *
     * @returns {{label: string, value: string, disabled: boolean}[]}
     */

  }, {
    key: "getKeyOptions",
    value: function getKeyOptions(mapIndex) {
      var choices = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
      var addPlaceholder = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : true;
      var addCustomKey = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : true;
      var _this$props5 = this.props,
          keyField = _this$props5.keyField,
          mappedChoices = _this$props5.mappedChoices,
          mapping = _this$props5.mapping;
      var allow_custom = keyField.allow_custom,
          allow_duplicates = keyField.allow_duplicates;

      if (!choices) {
        choices = keyField.choices;
      }

      var choiceNames = choices.map(function (c) {
        return c.name || c.value;
      });
      var optionKeyBase = "select-".concat(mapIndex, "-optiongroup-"); // Initialize options array.

      var options = [];

      if (!choiceNames.includes('') && addPlaceholder) {
        options.push( /*#__PURE__*/React.createElement("option", {
          key: "".concat(optionKeyBase, "-default"),
          value: "",
          disabled: false
        }, __('Select a Field', 'gravityforms')));
      } // Loop through choices, add as options.


      for (var i = 0; i < choices.length; i++) {
        var choice = choices[i],
            choice_value = choice.name || choice.value; // If this is a required choice, do not add as an option.

        if (choice.required) {
          continue;
        } // If choice is already selected, disable it.


        var disabled = mappedChoices.includes(choice_value) && choice_value !== mapping.key && !allow_duplicates; // Add optgroups for choices with sub-choices.

        if (choice.choices && choice.choices.length > 0) {
          options.push( /*#__PURE__*/React.createElement("optgroup", {
            label: choice.label,
            key: "".concat(optionKeyBase, "-").concat(i)
          }, this.getKeyOptions("".concat(mapIndex, ".").concat(i), choice.choices, false, false)));
        } else {
          options.push( /*#__PURE__*/React.createElement("option", {
            key: "".concat(optionKeyBase, "-").concat(i),
            value: choice.value,
            disabled: disabled
          }, choice.label));
        }
      } // Add custom key if enabled and is not already present.


      if (allow_custom && !choiceNames.includes('gf_custom') && addCustomKey) {
        options.push( /*#__PURE__*/React.createElement("option", {
          key: "".concat(optionKeyBase, "-custom"),
          value: "gf_custom",
          disabled: false
        }, __('Add Custom Key', 'gravityforms')));
      }

      return options;
    } // # VALUE COLUMN --------------------------------------------------------------------------------------------------

    /**
     * Prepare input for value column.
     *
     * @returns {*}
     */

  }, {
    key: "getValueInput",
    value: function getValueInput() {
      var _this$props6 = this.props,
          choice = _this$props6.choice,
          index = _this$props6.index,
          isInvalid = _this$props6.isInvalid,
          mapping = _this$props6.mapping,
          updateMapping = _this$props6.updateMapping,
          valueField = _this$props6.valueField,
          mergeTagSupport = _this$props6.mergeTagSupport;
      var required = choice.required;
      var fieldId = this.getValueInputId(); // If selected value is custom value, display input.

      if (mapping.value === 'gf_custom') {
        return /*#__PURE__*/React.createElement(_MppingValueField["default"], {
          choice: choice,
          index: index,
          isInvalid: isInvalid,
          mapping: mapping,
          updateMapping: updateMapping,
          valueField: valueField,
          mergeTagSupport: mergeTagSupport,
          fieldId: fieldId
        }, " ");
      }

      return /*#__PURE__*/React.createElement("select", {
        id: fieldId,
        disabled: mapping.key === '' || !mapping.key,
        value: mapping.value,
        onChange: function onChange(e) {
          return updateMapping(_objectSpread(_objectSpread({}, mapping), {}, {
            value: e.target.value
          }), index);
        },
        className: isInvalid ? 'gform-settings-generic-map__value--invalid' : '',
        required: required
      }, this.getValueOptions().map(function (opt) {
        if (opt.choices && opt.choices.length > 0) {
          return /*#__PURE__*/React.createElement("optgroup", {
            key: opt.label,
            label: opt.label
          }, opt.choices.map(function (o) {
            return /*#__PURE__*/React.createElement("option", {
              key: o.value,
              value: o.value
            }, o.label);
          }));
        } else {
          return /*#__PURE__*/React.createElement("option", {
            key: opt.value,
            value: opt.value
          }, opt.label);
        }
      }));
    }
    /**
     * Get options for value drop down.
     *
     * @returns {{label: *, value: boolean}[]}
     */

  }, {
    key: "getValueOptions",
    value: function getValueOptions() {
      var _this$props7 = this.props,
          choice = _this$props7.choice,
          valueField = _this$props7.valueField;
      var allow_custom = valueField.allow_custom;
      var choiceKey = choice.name && valueField.choice_keys && valueField.choice_keys[choice.name] ? valueField.choice_keys[choice.name] : 'default'; // if no name is present, use default values.

      var choices = choice.choices || valueField.choices[choiceKey]; // Safety check to ensure choices are an array.

      if (!choices) {
        choices = [];
      }

      var values = choices.map(function (c) {
        return c.value;
      }); // Add custom key if enabled and is not already present.

      if (allow_custom && !values.includes('gf_custom')) {
        choices.push({
          label: __('Add Custom Value', 'gravityforms'),
          value: 'gf_custom',
          disabled: false
        });
      }

      return choices;
    } // # BUTTONS -------------------------------------------------------------------------------------------------------

    /**
     * Get add mapping button.
     *
     * @returns {null|*}
     */

  }, {
    key: "getAddButton",
    value: function getAddButton() {
      var _this$props8 = this.props,
          canAdd = _this$props8.canAdd,
          addMapping = _this$props8.addMapping,
          index = _this$props8.index; // If mapping cannot be added, do not show button.

      if (!canAdd) {
        return null;
      }

      return /*#__PURE__*/React.createElement("button", {
        className: "add_field_choice gform-st-icon gform-st-icon--circle-plus gform-settings-generic-map__button gform-settings-generic-map__button--add",
        onClick: function onClick(e) {
          e.preventDefault();
          addMapping(index);
        }
      }, /*#__PURE__*/React.createElement("span", {
        className: "screen-reader-text"
      }, __('Add', 'gravityforms')));
    }
    /**
     * Get delete mapping button.
     *
     * @returns {null|*}
     */

  }, {
    key: "getDeleteButton",
    value: function getDeleteButton() {
      var _this$props9 = this.props,
          canDelete = _this$props9.canDelete,
          deleteMapping = _this$props9.deleteMapping,
          index = _this$props9.index; // If mapping cannot be deleted, do not show button.

      if (!canDelete) {
        return null;
      }

      return /*#__PURE__*/React.createElement("button", {
        className: "delete_field_choice gform-st-icon gform-st-icon--circle-minus gform-settings-generic-map__button gform-settings-generic-map__button--delete",
        onClick: function onClick(e) {
          e.preventDefault();
          deleteMapping(index);
        }
      }, /*#__PURE__*/React.createElement("span", {
        className: "screen-reader-text"
      }, __('Delete', 'gravityforms')));
    }
  }]);

  return Mapping;
}(Component);

exports["default"] = Mapping;

/***/ })

/******/ });
//# sourceMappingURL=field-map.js.map