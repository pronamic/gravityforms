// Utility variables
var GF_CONDITIONAL_INSTANCE = false;
var GF_CONDITIONAL_INSTANCES_COLLECTION = [];
var FOCUSABLE_ELEMENTS      = [ 'a[href]', 'area[href]', 'input:not([disabled])', 'select:not([disabled])', 'textarea:not([disabled])', 'button:not([disabled])', 'iframe', 'object', 'embed', '[contenteditable]', '[tabindex]:not([tabindex^="-"])' ];
var TAB_KEY                 = 9;
var ESCAPE_KEY              = 27;
var FOCUSED_BEFORE_DIALOG   = null;
var FOCUSED_BEFORE_RENDER = null;

/**
 * Set the focus to the first focusable child of the given element
 *
 * @param {Element} node The element to focus within.
 * @param {Event} event Passed event from some handlers
 */
function setFocusToFirstItem( node, event ) {
	if ( event && event.target && ! gform.tools.getClosest( event.target, '#' + node.id ) ) {
		return;
	}
	var focusableChildren = getFocusableChildren( node );

	if ( focusableChildren.length ) {
		focusableChildren[ 0 ].focus();
	}
}

/**
 * Get the focusable children for the provided node.
 *
 * @param {Element} node The element to search within.
 *
 * @return {Element[]}
 */
function getFocusableChildren( node ) {
	return $$( FOCUSABLE_ELEMENTS.join( ',' ), node ).filter( function( child ) {
		return !!(child.offsetWidth || child.offsetHeight || child.getClientRects().length);
	} );
}

/**
 * Trap the focus inside the given element
 *
 * @param {Element} node  The node to trap within.
 * @param {Event}   event The JS event.
 */
function trapTabKey( node, event ) {
	var focusableChildren = getFocusableChildren( node );
	var focusedItemIndex  = focusableChildren.indexOf( document.activeElement );

	// If the SHIFT key is being pressed while tabbing (moving backwards) and
	// the currently focused item is the first one, move the focus to the last
	// focusable item from the dialog element
	if ( event.shiftKey && focusedItemIndex === 0 ) {
		focusableChildren[ focusableChildren.length - 1 ].focus();
		event.preventDefault();
		// If the SHIFT key is not being pressed (moving forwards) and the currently
		// focused item is the last one, move the focus to the first focusable item
		// from the dialog element
	} else if ( !event.shiftKey && focusedItemIndex === focusableChildren.length - 1 ) {
		focusableChildren[ 0 ].focus();
		event.preventDefault();
	}
}

/**
 * Query the DOM for nodes matching the given selector, scoped to context (or
 * the whole document)
 *
 * @param {String}  selector             The selector to use.
 * @param {Element} [context = document] The context to search within.
 *
 * @return {Array<Element>}
 */
function $$( selector, context ) {
	return gform.tools.convertElements( (context || document).querySelectorAll( selector ) );
}

/**
 * Render the given view HTML with the provided token replacement configuration.
 *
 * @param {string}  html      The HTML the view should render.
 * @param {Element} container The container in which to render the view.
 * @param {object}  config    An object representing key/value pairs to use for token replacement.
 * @param {bool}    echo      Whether to echo the resulting markup - will return the markup if set to false.
 *
 * @return {boolean|string}
 */
function renderView( html, container, config, echo ) {
	FOCUSED_BEFORE_RENDER = document.activeElement;

	var parsed = html;
	for ( var key in config ) {
		var val       = config[ key ];
		var search    = '{{ ' + key + ' }}';
		var searchRgx = new RegExp( search, 'g' );
		parsed        = parsed.replace( searchRgx, val );
	}

	if ( !echo ) {
		return parsed;
	}

	container.innerHTML = parsed;

	if ( FOCUSED_BEFORE_RENDER.id ) {
		window.setTimeout( function() {
			if ( document.getElementById( FOCUSED_BEFORE_RENDER.id ) == null ) {
				return;
			}

			document.getElementById( FOCUSED_BEFORE_RENDER.id ).focus();
		}, 10 );
	}

	return true;
}

/**
 * Get a field object from the given ID.
 *
 *
 * @param fieldId
 * @return {boolean|*|T}
 */
function getFieldById( fieldId ) {
	var found = this.form.fields.filter( function( field ) {
		return field.id == fieldId;
	} );

	if ( !found.length ) {
		return false;
	}

	return found[ 0 ];
}

/**
 * Get the correct field ID to use as a default value when adding a new rule:
 *
 * - If the field has no child inputs, return the field ID
 * - If the field has child inputs, but all are set to be hidden, return field ID
 * - Otherwise, return the ID of the first non-hidden child input.
 *
 * @param {object} field The field being rendered.
 *
 * @return {string|integer}
 */
function getCorrectDefaultFieldId( field ) {
	if ( ! field ) {
		return null;
	}

	if ( field.type === 'checkbox' || field.type === 'radio' || ! field.inputs || ! field.inputs.length ) {
		return field.id;
	}

	var options = field.inputs.filter( function( input ) {
		return ! input.isHidden;
	} );

	if ( ! options.length ) {
		return field.id;
	}

	return options[0].id;
}

/**
 * Get the available options for a given select field.
 *
 * @param {object} field The field being rendered.
 * @param {mixed}  value The currently-selected value.
 *
 * @return {[]}
 */
function getOptionsFromSelect( field, value ) {
	var options = [];

	var emptyLabel = gf_vars.emptyChoice;

	if ( field.placeholder ) {
		emptyLabel = field.placeholder;
	}

	var emptyChoiceConfig = {
		label: emptyLabel,
		value: '',
		selected: '' === value ? 'selected="selected"' : '',
	};

	options.push( emptyChoiceConfig );

	for ( var i = 0; i < field.choices.length; i++ ) {
		var choice = field.choices[ i ];
		var config = {
			label: choice.text,
			value: choice.value,
			selected: choice.value == value ? 'selected="selected"' : '',
		};

		options.push( config );

	}

	return options;
}

/**
 * Get the available post category options.
 *
 * @param {object} field The field being rendered.
 * @param {mixed}  value The currently-selected value.
 *
 * @return {[]}
 */
function getCategoryOptions( field, value ) {
	var cats    = gf_vars.conditionalLogic.categories;
	var options = [];

	for ( var i = 0; i < cats.length; i++ ) {
		var cat    = cats[ i ];
		var config = {
			label: cat.label,
			value: cat.term_id,
			selected: cat.term_id == value ? 'selected="selected"' : '',
		}

		options.push( config );
	}

	return options;
}

/**
 * Get the available post category options.
 *
 * @param {object} field   The field being rendered.
 * @param {string} inputId The inputId of the current field.
 * @param {mixed}  value   The currently-selected value.
 *
 * @return {[]}
 */
function getAddressOptions( field, inputId, value ) {
	var options        = [];
	var addressOptions = gf_vars.conditionalLogic.addressOptions;

	if ( !field.inputs ) {
		return options;
	}

	if ( !addressOptions[ field.addressType ] ) {
		return [];
	}

	var fieldAddressOptions = addressOptions[ field.addressType ];

	// Address options are grouped by a key; parse them as sub-items.
	if ( ! Array.isArray( fieldAddressOptions ) ) {

		for ( var locale in fieldAddressOptions ) {
			var group = fieldAddressOptions[ locale ];

			for ( var i = 0; i < group.length; i++ ) {
				var option = group[ i ];

				var config = {
					label: option,
					value: option,
					selected: option == value ? 'selected="selected"' : '',
				}

				options.push( config );
			}
		}

		return options;
	}

	// Address options are just a single-level array; loop through them.
	for ( var i = 0; i < fieldAddressOptions.length; i++ ) {
		var option = fieldAddressOptions[ i ];

		var config = {
			label: option,
			value: option,
			selected: option == value ? 'selected="selected"' : '',
		}

		options.push( config );
	}

	return options;
}

/**
 * Generate a GFConditionalLogic instance from the given field ID and object type.
 *
 * @param {int}    fieldId    The ID for the current field.
 * @param {string} objectType The object type of the current field.
 */
function generateGFConditionalLogic( fieldId, objectType ) {
	if ( GF_CONDITIONAL_INSTANCE && GF_CONDITIONAL_INSTANCE.fieldId != fieldId  ) {
		GF_CONDITIONAL_INSTANCES_COLLECTION.forEach( function( instance, instanceIndex ) {
			instance.hideFlyout();
			instance.removeEventListeners();
			instance.deactivated = true;
		});
	}

	GF_CONDITIONAL_INSTANCE = new GFConditionalLogic( fieldId, objectType );

	GF_CONDITIONAL_INSTANCES_COLLECTION = GF_CONDITIONAL_INSTANCES_COLLECTION.filter( function( instance ) {
		return instance.deactivated !== true;
	});

	GF_CONDITIONAL_INSTANCES_COLLECTION.push( GF_CONDITIONAL_INSTANCE );
}

/**
 * Determine whether a click event is from a valid flyout element.
 *
 * @param {Event} e The Event object.
 *
 * @return {boolean}
 */
function isValidFlyoutClick( e ) {
	return (
		'jsConditonalToggle' in e.target.dataset ||
		'jsAddRule' in e.target.dataset ||
		'jsDeleteRule' in e.target.dataset ||
		e.target.classList.contains( 'gform-field__toggle-input' )
	);
}

/**
 * Determine whether a given rule needs to present a text input for the value.
 *
 * @param {object} e The rule object.
 *
 * @return {boolean}
 */
function ruleNeedsTextValue( rule ) {
	return ['contains', 'starts_with', 'ends_with', '<', '>' ].indexOf ( rule.operator ) !== -1;
}

/**
 * Class GFConditionalLogic
 *
 * A JS class encapsulating all of the logic and state for a conditional flyout.
 *
 * @param {int}    fieldId    The ID for the current field.
 * @param {string} objectType The object type of the current field.
 *
 * @constructor
 */
function GFConditionalLogic( fieldId, objectType ) {

	// State and Flyout data
	this.fieldId    = fieldId;
	this.form       = form;
	this.objectType = objectType;
	this.els        = this.gatherElements();
	this.state      = this.getStateForField( fieldId );
	this.visible    = false;

	// Prebind event listener callbacks to maintain references
	this._handleToggleClick    = this.handleToggleClick.bind( this );
	this._handleFlyoutChange   = this.handleFlyoutChange.bind( this );
	this._handleBodyClick      = this.handleBodyClick.bind( this );
	this._handleAccordionClick = this.handleAccordionClick.bind( this );
	this._handleSidebarClick   = this.handleSidebarClick.bind( this );
	this._maintainFocus        = this._maintainFocus.bind( this );
	this._bindKeypress         = this._bindKeypress.bind( this );

	this.init();
}

/**
 * Render the sidebar view.
 */
GFConditionalLogic.prototype.renderSidebar = function() {
	var config = {
		title: this.getAccordionTitle(),
		toggleText: gf_vars.configure + ' ' + gf_vars.conditional_logic_text,
		active_class: this.isEnabled() ? 'gform-status--active' : '',
		active_text: this.isEnabled() ? 'Active' : 'Inactive',
		desc_class: GetFirstRuleField() <= 0 ? 'active' : '',
		toggle_class: GetFirstRuleField() <= 0 ? '' : 'active',
		desc: gf_vars.conditionalLogic.conditionalLogicHelperText,
	}

	var html = gf_vars.conditionalLogic.views.sidebar;

	renderView( html, this.els[ this.objectType ], config, true );
};

/**
 * Render the flyout view.
 */
GFConditionalLogic.prototype.renderFlyout = function() {
	var config = {
		objectType: this.objectType,
		fieldId: this.fieldId,
		checked: this.state.enabled ? 'checked' : '',
		activeClass: this.visible ? 'active' : 'inactive',
		enabledText: this.state.enabled ? gf_vars.enabled : gf_vars.disabled,
		configure: gf_vars.configure,
		conditionalLogic: gf_vars.conditional_logic_text,
		enable: gf_vars.enable,
		desc: gf_vars.conditional_logic_desc,
		main: this.renderMainControls( false ),
	};

	var html = gf_vars.conditionalLogic.views.flyout;

	renderView( html, this.els.flyouts[ this.objectType ], config, true );

	gform.tools.trigger( 'gform_render_simplebars' );
};

/**
 * Render the main controls.
 *
 * @param {boolean} echo
 *
 * @return {boolean|string}
 */
GFConditionalLogic.prototype.renderLogicDescription = function() {

	var config = {
		actionType: this.state.actionType,
		logicType: this.state.logicType,
		objectTypeText: this.getObjectTypeText(),
		objectShowText: this.getObjectShowText(),
		objectHideText: this.getObjectHideText(),
		matchText: gf_vars.ofTheFollowingMatch,
		allText: gf_vars.all,
		anyText: gf_vars.any,
		hideSelected: this.state.actionType === 'hide' ? 'selected="selected"' : '',
		showSelected: this.state.actionType === 'show' ? 'selected="selected"' : '',
		allSelected: this.state.logicType === 'all' ? 'selected="selected"' : '',
		anySelected: this.state.logicType === 'any' ? 'selected="selected"' : '',
	};

	var html = gf_vars.conditionalLogic.views.logicDescription;

	var markup = renderView( html, this.els.flyouts[ this.objectType ], config, false );

	/**
	 * @filter gform_conditional_logic_description
	 *
	 * Allows add-ons to modify the markup returned for the Conditional Logic description area.
	 *
	 * @since unknown
	 * @since 2.5 descPieces passed as empty array
	 *
	 * @param {string} markup The current markup HTML for the description
	 * @param {array} descPieces The individual markup pieces which make up the final markup (empty here)
	 * @param {string} objectType The current object type
	 * @param {object} this The current object
	 *
	 * @return {string}
	 */
	return gform.applyFilters( 'gform_conditional_logic_description', markup, [], this.objectType, this );
};

/**
 * Render the main controls.
 *
 * @param {boolean} echo
 *
 * @return {boolean|string}
 */
GFConditionalLogic.prototype.renderMainControls = function( echo ) {

	var config = {
		enabledClass: this.state.enabled ? 'active' : '',
		logicDescription: this.renderLogicDescription(),
		a11yWarning: this.objectType === 'button' ? gf_vars.conditionalLogic.views.a11yWarning : '',
		a11yWarningText: gf_vars.conditional_logic_a11y,
	};

	var html = gf_vars.conditionalLogic.views.main;

	if ( ! echo ) {
		return renderView( html, this.els.flyouts[ this.objectType ], config, false );
	}

	renderView( html, this.els.flyouts[ this.objectType ].querySelector( '.conditional_logic_flyout__main' ), config, true );
};

/**
 * Render the field options for the given rule.
 *
 * @param {object} rule The rule data to render.
 *
 * @return {string}
 */
GFConditionalLogic.prototype.renderFieldOptions = function( rule ) {
	var html     = '';
	var template = gf_vars.conditionalLogic.views.option;
	var options  = [];

	for ( var i = 0; i < form.fields.length; i++ ) {

		var field = form.fields[ i ];

		if ( !IsConditionalLogicField( field ) ) {
			continue;
		}

		if ( field.inputs && jQuery.inArray( GetInputType( field ), [ 'checkbox', 'email', 'consent' ] ) == -1 ) {
			for ( var j = 0; j < field.inputs.length; j++ ) {
				var input = field.inputs[ j ];

				if ( input.isHidden ) {
					continue;
				}

				var config = {
					label: GetLabel( field, input.id ),
					value: input.id,
					selected: input.id == rule.fieldId ? 'selected="selected"' : '',
				};

				options.push( config );
			}
		} else {
			var config = {
				label: GetLabel( field ),
				value: field.id,
				selected: field.id == rule.fieldId ? 'selected="selected"' : '',
			};

			options.push( config );
		}
	}

	options = gform.applyFilters( 'gform_conditional_logic_fields', options, form, rule.fieldId );

	for ( var i = 0; i < options.length; i++ ) {
		var config = options[ i ];

		if ( ! config.selected ) {
			config.selected = config.value == rule.fieldId ? 'selected="selected"' : '';
		}

		html += renderView( template, null, config, false );
	}

	return html;
};

/**
 * Render operator options for the given rule.
 *
 * @param {object} rule The rule data to render.
 *
 * @return {string}
 */
GFConditionalLogic.prototype.renderOperatorOptions = function( rule ) {
	var html      = '';
	var template  = gf_vars.conditionalLogic.views.option;
	var operators = {
		is: gf_vars.is,
		isnot: gf_vars.isNot,
		'>': gf_vars.greaterThan,
		'<': gf_vars.lessThan,
		contains: gf_vars.contains,
		starts_with: gf_vars.startsWith,
		ends_with: gf_vars.endsWith,
	};

	operators = gform.applyFilters( 'gform_conditional_logic_operators', operators, this.objectType, rule.fieldId );

	for ( key in operators ) {
		var label  = operators[ key ];
		var config = {
			label: label,
			value: key,
			selected: key == rule.operator ? 'selected="selected"' : '',
		};

		html += renderView( template, null, config, false );
	}

	return html;
};

/**
 * Render value options for the given rule.
 *
 * @param {object} rule The rule data to render.
 *
 * @return {string}
 */
GFConditionalLogic.prototype.renderValueOptions = function( rule, idx ) {
	var field    = getFieldById( rule.fieldId );
	var html     = '';
	var template = gf_vars.conditionalLogic.views.option;
	var options  = [];

	// Field is actually a sub-field (such as the First Name or Country field), get the correct field from its ID.
	if ( rule.fieldId.toString().indexOf( '.' ) !== -1 ) {
		var parts   = rule.fieldId.toString().split( '.' );
		var fieldId = parts[ 0 ];
		field       = getFieldById( fieldId );
	}

	// Something went wrong; bail.
	if ( !field && !IsAddressSelect( rule.fieldId, field ) ) {
		return html;
	}

	// We're dealing with an Address field - get the correct values for it.
	if ( IsAddressSelect( rule.fieldId, field ) ) {
		options = getAddressOptions( field, rule.fieldId, rule.value );
	}

	// We're dealing with a category field - get all post categories as options.
	if ( field && field[ 'type' ] == 'post_category' && field[ 'displayAllCategories' ] ) {
		options = getCategoryOptions( field, rule.value );
	}

	// We're dealing with a normal select field - get the options from it.
	if ( field && field.choices && field[ 'type' ] != 'post_category' ) {
		options = getOptionsFromSelect( field, rule.value );
	}

	for ( var i = 0; i < options.length; i++ ) {
		var config = options[ i ];
		html += renderView( template, null, config, false );
	}

	return html;
};

/**
 * Render an input using the given data.
 *
 * @param {object} rule The rule data to render.
 * @param {int}    idx  The index of the rule.
 *
 * @return {string}
 */
GFConditionalLogic.prototype.renderInput = function( rule, idx ) {
	var config = {
		ruleIdx: idx,
		value: rule.value,
	};

	var html = gf_vars.conditionalLogic.views.input;

	return renderView( html, null, config, false );
};

/**
 * Render a select using the given data.
 *
 * @param {object} rule The rule data to render.
 * @param {int}    idx  The index of the rule.
 *
 * @return {string}
 */
GFConditionalLogic.prototype.renderSelect = function( rule, idx ) {
	var config = {
		ruleIdx: idx,
		fieldValueOptions: this.renderValueOptions( rule, idx ),
	};

	var html = gf_vars.conditionalLogic.views.select;

	return renderView( html, null, config, false );
};

/**
 * Render a rule value using the given data.
 *
 * @param {object} rule The rule data to render.
 * @param {int}    idx  The index of the rule.
 *
 * @return {string}
 */
GFConditionalLogic.prototype.renderRuleValue = function( rule, idx ) {
	var fieldValueOptions = this.renderValueOptions( rule, idx );
	var isSelect          = fieldValueOptions.length;
	var html              = '';
	var needsTextInput    = ruleNeedsTextValue( rule );

	if ( ! isSelect || needsTextInput ) {
		html = this.renderInput( rule, idx );
	} else {
		html = this.renderSelect( rule, idx );
	}

	html = gform.applyFilters( 'gform_conditional_logic_values_input', html, this.objectType, idx, rule.fieldId, rule.value );

	var el = gform.tools.htmlToElement( html );

	if ( ! el.classList.contains( 'active' ) ) {
		el.classList.add( 'active' );
	}

	if ( ! el.hasAttribute( 'data-js-rule-input' ) ) {
		el.setAttribute( 'data-js-rule-input', 'value' );
	}

	return gform.tools.elementToHTML( el );
};

/**
 * Render a rule using the given data.
 *
 * @param {object} rule The rule data to render.
 * @param {int}    idx  The index of the rule.
 *
 * @return {string}
 */
GFConditionalLogic.prototype.renderRule = function( rule, idx ) {
	var field = getFieldById( rule.fieldId );

	if ( ! field ) {
		field = {
			choices: '',
		};
	}

	var config = {
		rule_idx: idx,
		fieldOptions: this.renderFieldOptions( rule ),
		operatorOptions: this.renderOperatorOptions( rule ),
		deleteClass: this.state.rules.length > 1 ? 'active' : '',
		value: rule.value,
		valueMarkup: this.renderRuleValue( rule, idx ),
		addRuleText: gf_vars.conditionalLogic.addRuleText,
		removeRuleText: gf_vars.conditionalLogic.removeRuleText,
	};

	var html = gf_vars.conditionalLogic.views.rule;

	return renderView( html, null, config, false );
}

/**
 * Render a list of rules.
 *
 * @return {string}
 */
GFConditionalLogic.prototype.renderRules = function() {
	var container = this.els.flyouts[ this.objectType ].querySelector( '.conditional_logic_flyout__logic' );

	var html = '';
	for ( var i = 0; i < this.state.rules.length; i++ ) {
		html += this.renderRule( this.state.rules[ i ], i );
	}

	renderView( html, container, {}, true );
}

/**
 * Gather an object populated with the DOM elements we'll be interacting with.
 *
 * @return {object}
 */
GFConditionalLogic.prototype.gatherElements = function() {
	return {
		field: document.querySelector( '.conditional_logic_field_setting' ),
		page: document.querySelector( '.conditional_logic_page_setting' ),
		next_button: document.querySelector( '.conditional_logic_nextbutton_setting' ),
		button: document.querySelector( '.conditional_logic_submit_setting' ),
		flyouts: {
			page: document.getElementById( 'conditional_logic_flyout_container' ),
			field: document.getElementById( 'conditional_logic_flyout_container' ),
			next_button: document.getElementById( 'conditional_logic_next_button_flyout_container' ),
			button: document.getElementById( 'conditional_logic_submit_flyout_container' ),
		},
	};
};

/**
 * Get the default rule to show if none exist.
 *
 * @return {{value: string, operator: string, fieldId: number}}
 */
GFConditionalLogic.prototype.getDefaultRule = function() {
	var fieldId = GetFirstRuleField();
	var field   = GetFieldById( fieldId );
	var fieldId = getCorrectDefaultFieldId( field );

	return {
		fieldId: fieldId,
		operator: 'is',
		value: '',
	};
};

/**
 * Get the default state for a new field.
 *
 * @return {{actionType: string, logicType: string, rules: [*], enabled: boolean}}
 */
GFConditionalLogic.prototype.getDefaultState = function() {
	return {
		enabled: false,
		actionType: 'show',
		logicType: 'all',
		rules: [
			this.getDefaultRule(),
		]
	};
};

/**
 * Get the correct state for the given field ID.
 *
 * @param {int} fieldId The ID of the field for which the state should be gathered.
 *
 * @return {obj}
 */
GFConditionalLogic.prototype.getStateForField = function( fieldId ) {
	// The submit field in the editor has a non-numeric ID.
	if( 'submit' === fieldId ) {
		var logic = form.button.conditionalLogic;
		if( logic ) {
			logic.enabled = true;
		} else {
			return this.getDefaultState();
		}
		return logic;
	}

	var field = getFieldById( fieldId );

	if ( field === false ) {
		return this.getDefaultState();
	}

	var logic = this.objectType === 'next_button' ? field.nextButton.conditionalLogic : field.conditionalLogic;

	if ( !logic || !logic.actionType ) {
		return this.getDefaultState();
	}

	// pre 2.5 forms dont have the enabled key in this object. If we have logic but no key, lets enable the ui
	if ( !( 'enabled' in logic ) ) {
		logic.enabled = true;
	}

	return logic;
};

/**
 * Determine whether the current conditional logic is enabled for this field.
 *
 * @return {boolean}
 */
GFConditionalLogic.prototype.isEnabled = function() {
	return this.state.enabled && GetFirstRuleField() > 0;
}

GFConditionalLogic.prototype.getAccordionTitle = function() {
	var prefix = '';
	switch ( this.objectType ) {
		case 'page':
			prefix = gf_vars.page + ' ';
			break;
		case 'next_button':
			prefix = gf_vars.next_button + ' ';
			break;
		case 'button':
			prefix = gf_vars.button + ' ';
		case 'field':
		default:
			break;
	}

	return prefix + gf_vars.conditional_logic_text;
};

/**
 * Get the correctly-translated text for the object type.
 *
 * @return {string}
 */
GFConditionalLogic.prototype.getObjectTypeText = function() {
	switch ( this.objectType ) {
		case 'section':
			return gf_vars.thisSectionIf;
		case 'field':
			return gf_vars.thisFieldIf;
		case 'page':
			return gf_vars.thisPage;
		case 'confirmation':
			return gf_vars.thisConfirmation;
		case 'notification':
			return gf_vars.thisNotification;
		default:
			return gf_vars.thisFormButton;
	}
}

/**
 * Get the correctly-translated text for the show text.
 *
 * @return {string}
 */
GFConditionalLogic.prototype.getObjectShowText = function() {
	if ( this.objectType === "next_button" ) {
		return gf_vars.enable;
	} else {
		return gf_vars.show;
	}
}

/**
 * Get the correctly-translated text for the hide text.
 *
 * @return {string}
 */
GFConditionalLogic.prototype.getObjectHideText = function() {
	if ( this.objectType === "next_button" ) {
		return gf_vars.disable;
	} else {
		return gf_vars.hide;
	}
}

/**
 * Hide the flyout.
 */
GFConditionalLogic.prototype.hideFlyout = function() {
	var thisFlyout = this.els.flyouts[ this.objectType ];
	if ( ! thisFlyout.classList.contains( 'anim-in-active' ) ) {
		return;
	}

	thisFlyout.classList.remove( 'anim-in-ready' );
	thisFlyout.classList.remove( 'anim-in-active' );
	thisFlyout.classList.add( 'anim-out-ready' );

	window.setTimeout( function() {
		thisFlyout.classList.add( 'anim-out-active' );
	}, 25 );

	window.setTimeout( function() {
		thisFlyout.classList.remove( 'anim-out-ready' );
		thisFlyout.classList.remove( 'anim-out-active' );
	}, 215 );
};

/**
 * Show the flyout.
 */
GFConditionalLogic.prototype.showFlyout = function() {
	for ( type in this.els.flyouts ) {
		var flyout = this.els.flyouts[ type ];
		flyout.classList.remove( 'anim-in-ready' );
		flyout.classList.remove( 'anim-in-active' );
		flyout.classList.remove( 'anim-out-ready' );
		flyout.classList.remove( 'anim-out-active' );
	}

	var thisFlyout = this.els.flyouts[ this.objectType ];

	thisFlyout.classList.add( 'anim-in-ready' );
	window.setTimeout( function() {
		thisFlyout.classList.add( 'anim-in-active' );
	}, 25 );
}

/**
 * Toggle the flyout when button is clicked.
 */
GFConditionalLogic.prototype.toggleFlyout = function( restoreFocus ) {
	this.renderFlyout();
	this.renderRules();

	if ( this.visible ) {
		this.hideFlyout();
	} else {
		this.showFlyout();
	}

	this.visible = !this.visible;

	var self = this;

	if ( ! restoreFocus ) {
		return;
	}

	window.setTimeout( function() {
		self.handleFocus();
	}, 325 );
};

/**
 * Update the current state with the corresponding key/value pair.
 *
 * @param {string} stateKey   The key in the state to update.
 * @param {*}      stateValue The value to update with.
 *
 * @return void
 */
GFConditionalLogic.prototype.updateState = function( stateKey, stateValue ) {
	this.state[ stateKey ] = stateValue;
	this.updateForm();

	if ( stateKey === 'enabled' ) {
		this.renderSidebar();
		this.renderMainControls( true );
		this.renderRules();
	}
};

/**
 * Update the given rule by its index with the provided values.
 *
 * @param {string} key   The key in the state to update.
 * @param {*}      value The value to update with.
 * @param {int}    idx   The index of the rule to update.
 *
 * @return void
 */
GFConditionalLogic.prototype.updateRule = function( key, value, idx ) {
	this.state.rules[ idx ][ key ] = value;
	this.renderRules();
	this.updateForm();
}

/**
 * Add a rule.
 *
 * @return void
 */
GFConditionalLogic.prototype.addRule = function() {
	this.state.rules.push( this.getDefaultRule() );
	this.renderRules();
	this.updateForm();
}

/**
 * Delete a rule at the provided index.
 *
 * @param {int} idx The index of the rule to delete.
 *
 * @return void
 */
GFConditionalLogic.prototype.deleteRule = function( idx ) {
	this.state.rules.splice( idx, 1 );
	this.renderRules();
	this.updateForm();
};

/**
 * Update the form conditional data at the provided index with the given data.
 *
 * @param {int} index The index of the data to update.
 * @param {obj} data  The conditional data to update.
 *
 * @return void
 */
GFConditionalLogic.prototype.updateFormConditionalData = function( index, data ) {
	if ( this.objectType === 'next_button' ) {
		form.fields[ index ].nextButton.conditionalLogic = data;
		return;
	}

	if ( this.objectType === 'button' ) {
		form.button.conditionalLogic = data;
		return;
	}

	form.fields[ index ].conditionalLogic = data;
}

/**
 * Update the global form object so that data saves correctly.
 */
GFConditionalLogic.prototype.updateForm = function() {

	if ( 'submit' === this.fieldId ) {
		this.updateFormButtonConditionalData( this.state );
	}

	for ( var i = 0; i < form.fields.length; i++ ) {
		var field = form.fields[ i ];

		if ( field.id != this.fieldId ) {
			continue;
		}

		if ( !this.isEnabled() ) {
			this.updateFormConditionalData( i, '' )
			return;
		}

		this.updateFormConditionalData( i, this.state );
		return;
	}
}

/**
 * Update the submit button in the global form object so that data saves correctly.
 *
 * @since 2.6
 *
 * @params {array} data
 */
GFConditionalLogic.prototype.updateFormButtonConditionalData = function( data ) {
	if ( !this.isEnabled() ) {
		form.button.conditionalLogic = '';
		return;
	}
	form.button.conditionalLogic = data;
}

/**
 * Handle clicks of the toggle button.
 *
 * @param {Event} e
 */
GFConditionalLogic.prototype.handleToggleClick = function( e ) {
	if ( e.target.classList.contains( 'conditional_logic_accordion__toggle_button' ) || e.target.classList.contains( 'conditional_logic_accordion__toggle_button_icon' ) ) {
		this.toggleFlyout( true );
	}
};

/**
 * Handle clicks within the sidebar.
 *
 * @param {Event} e
 */
GFConditionalLogic.prototype.handleSidebarClick = function( e ) {
	if ( ('jsConditonalToggle' in e.target.dataset) ) {
		this.updateState( 'enabled', e.target.checked );
	}

	if ( ('jsAddRule' in e.target.dataset) ) {
		this.addRule();
	}

	if ( ('jsDeleteRule' in e.target.dataset) ) {
		var parent = gform.tools.getClosest( e.target, '[data-js-rule-idx]' );
		this.deleteRule( parent.dataset.jsRuleIdx );
	}

	if ( ('jsCloseFlyout' in e.target.dataset) ) {
		this.toggleFlyout( true );
	}
};

/**
 * Handle changes within the flyout container.
 *
 * @param {Event} e
 */
GFConditionalLogic.prototype.handleFlyoutChange = function( e ) {
	if ( ('jsStateUpdate' in e.target.dataset) ) {
		var key = e.target.dataset.jsStateUpdate;
		var val = e.target.value;

		this.updateState( key, val );
	}

	if ( ('jsRuleInput' in e.target.dataset) ) {
		var parent = e.target.parentNode;
		var key    = e.target.dataset.jsRuleInput;
		var val    = e.target.value;

		this.updateRule( key, val, parent.dataset.jsRuleIdx );
	}
};

/**
 * Handle clicks outside the flyout container.
 *
 * @param {Event} e
 */
GFConditionalLogic.prototype.handleBodyClick = function( e ) {
	if ( isValidFlyoutClick( e ) ) {
		return;
	}

	if ( this.visible && !this.els.flyouts[ this.objectType ].contains( e.target ) ) {
		this.toggleFlyout( true );
	}

};

/**
 * Handle clicks on sidebar accordion items.
 *
 * @param {Event} e
 */
GFConditionalLogic.prototype.handleAccordionClick = function( e ) {
	if (
		this.visible &&
		! e.target.classList.contains( 'conditional_logic_accordion__toggle_button') &&
		! e.target.classList.contains( 'conditional_logic_accordion__toggle_button_icon' )
	) {
		this.toggleFlyout( false );
	}
};

/**
 * Add all event listeners to flyout.
 */
GFConditionalLogic.prototype.addEventListeners = function() {
	this.els[ this.objectType ].addEventListener( 'click', this._handleToggleClick );
	this.els.flyouts[ this.objectType ].addEventListener( 'click', this._handleSidebarClick );
	this.els.flyouts[ this.objectType ].addEventListener( 'change', this._handleFlyoutChange );
	document.body.addEventListener( 'click', this._handleBodyClick );
	gform.addAction( 'formEditorNullClick', this._handleAccordionClick );
}

/**
 * Remove all event listeners from flyout.
 */
GFConditionalLogic.prototype.removeEventListeners = function() {
	this.els[ this.objectType ].removeEventListener( 'click', this._handleToggleClick );
	this.els.flyouts[ this.objectType ].removeEventListener( 'click', this._handleSidebarClick );
	this.els.flyouts[ this.objectType ].removeEventListener( 'change', this._handleFlyoutChange );
	document.body.removeEventListener( 'click', this._handleBodyClick );
}

/**
 * Private event handler used when listening to some specific key presses
 * (namely ESCAPE and TAB)
 *
 * @param {Event} event
 */
GFConditionalLogic.prototype._bindKeypress = function( event ) {
	// If the dialog is shown and the ESCAPE key is being pressed, prevent any
	// further effects from the ESCAPE key and hide the dialog
	if ( this.visible && event.which === ESCAPE_KEY ) {
		event.preventDefault();
		this.toggleFlyout( true );
	}

	// If the dialog is shown and the TAB key is being pressed, make sure the
	// focus stays trapped within the dialog element
	if ( this.visible && event.which === TAB_KEY ) {
		trapTabKey( this.els.flyouts[ this.objectType ], event );
	}
};

/**
 * Add focus to the flyout.
 */
GFConditionalLogic.prototype.addFocusToFlyout = function() {
	// Keep a reference to the currently focused element to be able to restore
	// it later, then set the focus to the first focusable child of the dialog
	// element
	FOCUSED_BEFORE_DIALOG = document.activeElement;

	setFocusToFirstItem( this.els.flyouts[ this.objectType ] );

	// Bind a focus event listener to the body element to make sure the focus
	// stays trapped inside the dialog while open, and start listening for some
	// specific key presses (TAB and ESC)
	document.body.addEventListener( 'focus', this._maintainFocus, true );
	document.addEventListener( 'keydown', this._bindKeypress );
};

/**
 * Remove focus from the flyout.
 */
GFConditionalLogic.prototype.removeFocusFromFlyout = function() {
	// If their was a focused element before the dialog was opened, restore the
	// focus back to it
	if ( FOCUSED_BEFORE_DIALOG ) {
		FOCUSED_BEFORE_DIALOG.focus();
	}

	// Remove the focus event listener to the body element and stop listening
	// for specific key presses
	document.body.removeEventListener( 'focus', this._maintainFocus, true );
	document.removeEventListener( 'keydown', this._bindKeypress );
};

/**
 * Handle the focus event callback.
 */
GFConditionalLogic.prototype.handleFocus = function() {
	if ( this.visible ) {
		this.addFocusToFlyout();
	} else {
		this.removeFocusFromFlyout();
	}
};

/**
 * Private event handler used when making sure the focus stays within the
 * currently open dialog
 *
 * @param {Event} event
 *
 * @return void
 */
GFConditionalLogic.prototype._maintainFocus = function( event ) {
	// If the dialog is shown and the focus is not within the dialog element,
	// move it back to its first focusable child
	if ( this.visible && !this.els.flyouts[ this.objectType ].contains( event.target ) ) {
		setFocusToFirstItem( this.els.flyouts[ this.objectType ], event );
	}
};

/**
 * Render the markup for this Conditional Flyout.
 */
GFConditionalLogic.prototype.render = function() {
	this.renderSidebar();
	this.renderFlyout();
	this.renderRules();

	this.updateForm();
};

/**
 * Initialize the Conditional Flyout.
 */
GFConditionalLogic.prototype.init = function() {
	this.addEventListeners();

	this.renderSidebar();
};

