function initLayoutEditor( $ ) {

	/**
	 * Get the group ID of the targeted element.
	 *
	 * @param {string} groupId The ID of the group to be set on the targeted element.
	 *
	 * @returns {jQuery}
	 */
	$.fn.setGroupId = function ( groupId ) {

		this.attr( 'data-groupId', groupId );

		this.each( function () {
			var field = getFieldByElement( $( this ) );
			if ( field ) {
				field.layoutGroupId = groupId;
			}
		} );

		return this;
	};

	/**
	 * Set the grid column span CSS property of the targeted element.
	 *
	 * @param {number} span The number of columns the targeted element should span.
	 *
	 * @returns {jQuery}
	 */
	$.fn.setGridColumnSpan = function ( span ) {

		if ( span === null ) {
			this.css( 'grid-column', 'auto / auto' );
			return this;
		}

		var field;

		this.css( 'grid-column', 'span {0}'.gformFormat( span ) );

		this.each( function () {
			// Spacer fields are pseudo-fields; they are generated when the last field in the group is resized and are
			// rendered based on that field's layoutSpacerGridColumnSpan property.
			if ( $( this ).hasClass( 'spacer' ) ) {
				var $prev = $( this ).prev( '.gfield' );
				field = getFieldByElement( $prev );
				field.layoutSpacerGridColumnSpan = span;
			} else {
				field = getFieldByElement( $( this ) );
				if ( field ) {
					field.layoutGridColumnSpan = span;
				}
			}
		} );

		return this;
	};

	/**
	 * Get the grid column span CSS property of the targeted element.
	 *
	 * @returns {number}
	 */
	$.fn.getGridColumnSpan = function () {
		if( undefined === this.css('gridColumnStart') ) {
			return;
		}

		// Use 'gridColumnStart' instead of 'grid-column' as Firefox returns null for the latter.
		var span = parseInt( this.css( 'gridColumnStart' ).split( ' ' )[ 1 ] );
		if ( isNaN( span ) && typeof columnCount !== 'undefined' ) {
			span = columnCount;
		}
		return span;
	};

	$.fn.resizeGroup = function ( groupID ) {
		resizeGroup( groupID );
	};

	/**
	 * Replace placeholders in the targeted string with passed values.
	 *
	 * @returns {string}
	 */
	if ( ! String.prototype.gformFormat ) {
		String.prototype.gformFormat = function() {
			var args = arguments;
			return this.replace( /{(\d+)}/g, function( match, number ) {
				return typeof args[ number ] != 'undefined' ? args[ number ] : match;
			} );
		};
	}

	var $editorContainer = $( '#form_editor_fields_container' ),
		$editor = $( '.gform_editor' ),
		$container = $( '#gform_fields' ),
		$noFields = $( '#no-fields' ),
		$noFieldsDropzone = $( '#no-fields-drop' ),
		$sidebar = $( '.editor-sidebar' ),
		$button = $( '.gfield-field-action' ),
		$fields = $elements(),
		$elem = null,
		fieldButtonsSelector = '.add-buttons button';


	/**
	 * The max column count determined by the fields container's grid CSS.
	 * @type {number}
	 */
	var columnCount = getComputedStyle( $container[ 0 ] )[ 'grid-template-columns' ].split( ' ' ).length,
		/**
		 * The minimum number of columns a field can span.
		 * @type {number}
		 */
		min = columnCount / 4,
		/**
		 * The maximum number of columns a field can span.
		 * @type {number}
		 */
		max = null,
		/**
		 * A flag to determine if the field was dropped in droparea that appears when the form has no fields.
		 * @type {boolean}
		 */
		isNoFieldsDrop = false,
		/**
		 * The group ID of the last deleted field. This is used to resize the remaining fields in that group once the field has been removed from the DOM.
		 * @type {boolean}
		 */
		deletedFieldGroupId;

	// Initialize fields for layout editor.
	initElement( $fields );

	// Parse and maybe patch group ids
	validateGroupIds();

	// Set the correct group for the submit button.
	setSubmitButtonGroup();

	// Initialize field buttons.
	initFieldButtons( $( fieldButtonsSelector ) );

	// Initialize the No Fields droparea.
	$noFields.droppable( {
		accept: fieldButtonsSelector,
		activate: function ( event, ui ) {
			$noFieldsDropzone.show();
			$( this ).addClass( 'ready' );
		},
		over: function () {
			$( this ).addClass( 'hovering' );
			$noFieldsDropzone.addClass( 'hovering' );
		},
		out: function () {
			$( this ).removeClass( 'hovering' );
			$noFieldsDropzone.removeClass( 'hovering' );
		},
		drop: function () {
			isNoFieldsDrop = true;
			$( this ).removeClass( 'hovering' );
			$noFieldsDropzone.removeClass( 'hovering' );
		},
		deactivate: function () {
			$( this ).removeClass( 'ready' );
		}
	} );

	// Clear field selection when clicking off of any field.
	$editorContainer.on( 'click', function () {
		clearFieldSelection();
	} );

	// Handle adding a new field.
	$( document ).on( 'gform_field_added', function ( event, form, field ) {

		var $field = $( '#field_' + field.id );

		// This field was added by clicking.
		if ( $elem === null ) {

			$field.setGroupId( getGroupId() );

			// If the submit button is inline, move it back to its own row
			if( jQuery('#field_submit').data( 'field-position' ) == 'inline' ) {
				moveButtonToBottom();
			}

		}
		// This field was added by dragging into the editor.
		else {

			moveByTarget( $field, $indicator().data( 'target' ), $indicator().data( 'where' ) );

			$elem.remove();
			$elem = null;

		}

		// editor is receiving first field, cleanup placeholders and no fields class, maybe init simplebar
		if ( $editorContainer.hasClass( 'form_editor_fields_no_fields' ) ) {
			gform.simplebar.initializeInstance( $editorContainer[ 0 ] );
			setTimeout( function() {
				$noFieldsDropzone.hide();
				$editorContainer.removeClass( 'form_editor_fields_no_fields' );
			}, 200 );
		}

		$indicator().remove();

		initElement( $field );

		if ( field['type'] === 'page' ) {
			moveButtonToBottom();
			jQuery('input[name="submit_location"][value="inline"]').prop( 'disabled', true );
			SetFieldAccessibilityWarning( 'submit_location_setting', 'below' );
		}

		if ( ! jQuery( '#field_submit' ).length > 0 ) {
			StartAddField( 'submit', Math.max( 0, $container.children().index( $elem ) + 1 ) );
		}

		var nativeEvent = new Event('gform/layout_editor/field_modified');
		document.dispatchEvent(nativeEvent);

	} );

	// Save the group ID of the deleted field.
	$( document ).on( 'gform_field_deleted', function ( event, form, fieldId ) {
		deletedFieldGroupId = getGroupId( $( '#field_' + fieldId ) );
		if ( ! HasPageField() ) {
			jQuery('input[name="submit_location"][value="inline"]').prop( 'disabled', false );
			jQuery( '.submit_location_setting' ).prev( '.gform-alert--notice' ).remove();
		}

		var nativeEvent = new Event('gform/layout_editor/gform_field_deleted');
		document.dispatchEvent(nativeEvent);
	} );

	// Handle resizing the group after the deleted field has been fully removed from the DOM.
	gform.addAction( 'gform_after_field_removed', function ( form, fieldId ) {
		resizeGroup( deletedFieldGroupId );
	} );

	// Handle duplicating a field.
	gform.addAction( 'gform_field_duplicated', function ( form, field, $field, sourceFieldId ) {

		var $source      = $( '#field_' + sourceFieldId );
		var $sourceGroup = getGroup( getGroupId( $source ) );

		// Add duplicated fields *after* the last field in its group so that it will always appear on a new row.
		$sourceGroup.last().after( $field );

		$field
			.setGridColumnSpan( columnCount )
			.setGroupId( getGroupId() );

		initElement( $field );

	} );

	// Re-initialize the field after it's markup is refreshed (e.g. after the description is updated).
	gform.addAction( 'gform_after_refresh_field_preview', function( fieldId ) {
		initElement( $( '#field_' + fieldId ) );
	} );

		gform.addAction( 'gform_form_saving_action_element_after_reload', function( form, event, newElement, elementReloadId, existingElement ) {
			if ( $( newElement ).hasClass( 'gfield' ) ) {
				initElement( $( '[data-js-reload="' + elementReloadId + '"]' ) );
			}

			if ( $( newElement ).hasClass( 'editor-sidebar' ) ) {
				initFieldButtons( $( fieldButtonsSelector ) );
			}

		} );

		gform.addAction( 'gform_form_saving_action_editor_has_new_components', function( form, event, newElement, currentSidebar, newSidebar ) {
			initFieldButtons( $( fieldButtonsSelector ) );
		} );

	gform.addAction( 'gform_before_get_field_markup', function( form, field, index ) {
		addFieldPlaceholder( field, index );
	} );

	gform.addAction( 'gform_after_get_field_markup', function( form, field, index ) {
		removeFieldPlaceholder();
	} );

	gform.addAction( 'gform_after_get_field_markup', function( form, field, index ) {
		initSubmit();
	} );

	gform.addAction( 'gform_before_field_duplicated', function( sourcefieldId ) {
		var $source = $( '#field_' + sourcefieldId );
		var $index  = $container.children().index( $source );

		addFieldPlaceholder( null, $index + 1 );
	} );

	gform.addAction( 'gform_field_duplicated', function() {
		removeFieldPlaceholder();
	} );

	gform.addAction( 'gform_before_refresh_field_preview', function( field_id ) {
		addFieldUpdateIndicator( field_id );
	} );

	gform.addAction( 'gform_after_refresh_field_preview', function( field_id ) {
		removeFieldUpdateIndicator( field_id );
	} );

	/**
	 * Make the submit button resizable when it is first added to the form.
	 *
	 * @since 2.6
	 */
	function initSubmit() {
		var submitField = jQuery( '#field_submit' );
		initElement( submitField );
	}

	function addFieldPlaceholder( field, index ) {

		var fieldString = '<li data-js-field-loading-placeholder><div class="dropzone__loader">' +
			'<div class="dropzone__loader-item dropzone__loader-label"></div>' +
			'<div class="dropzone__loader-item dropzone__loader-content"></div>' +
			'</div></li>';

		//sets up DOM for new field
		if ( typeof index != 'undefined' ) {
			if ( index === 0 ) {
				$( '#gform_fields' ).prepend( fieldString );
			} else {
				$( '#gform_fields' ).children().eq( index - 1 ).after( fieldString );
			}
		} else {
			if ( jQuery( '#field_submit' ) ) {
				jQuery( fieldString ).insertBefore ( jQuery( '#field_submit' ) );
			} else {
				$( '#gform_fields' ).append( fieldString );
			}
		}

		$( '[data-js-field-loading-placeholder]' ).setGridColumnSpan( columnCount );

		$( '#form_editor_fields_container' ).addClass( 'dropzone-loader-visible' );

		moveByTarget( $( '[data-js-field-loading-placeholder]' ), $indicator( false ).data( 'target' ), $indicator( false ).data( 'where' ) );
	}

	function removeFieldPlaceholder() {
		$( '#form_editor_fields_container' ).removeClass( 'dropzone-loader-visible' );
		$( '[data-js-field-loading-placeholder]' ).remove();
	}

	function addFieldUpdateIndicator( field_id ) {
		jQuery( "#field_" + field_id ).addClass( 'loading' );
	}

	function removeFieldUpdateIndicator( field_id ) {
		jQuery( "#field_" + field_id ).removeClass( 'loading' );
	}

	/**
	 * Initialize a form field so that it can be dragged and resized.
	 *
	 * @param {jQuery} $element The element(s) to be initialized.
	 */
	function initElement( $element ) {

		if ( $element.hasClass( 'ui-draggable' ) ) {
			$element
				.draggable( 'destroy' )
				.resizable( 'destroy' );
		}

		$element
			.draggable( {
				helper: 'clone',
				zIndex: 999,
				handle: '.gfield-drag',
				create: function( event, ui ) {
					if ( isSpacer( $( this ) ) ) {
						return;
					}

					var groupId,
						fieldId = $( this ).attr( 'id' ).replace( 'field_', '' ),
						field = fieldId ? GetFieldById( fieldId ) : false;

					if ( field && field.layoutGroupId && ! $editor.hasClass( 'gform_legacy_markup' ) ) {
						groupId = field.layoutGroupId;
					}
					// This applies when initializing a newly added field.
					else if ( ! getGroupId( $( this ), false ) ) {
						groupId = getGroupId();
					}

					$( this ).setGroupId( groupId );
				},
				start: function( event, ui ) {
					$container.addClass( 'dragging' );
					$editorContainer.addClass( 'droppable' );
					$elem = $( this );
					$elem.addClass( 'placeholder' );
				},
				drag: function( event, ui ) {
					// Match the helper to the current elements size.
					ui.helper
						.width( $elem.width() )
						.height( $elem.height() )
						// Firefox has trouble positioning the dragged element when it still has it's grid-column property set.
						.setGridColumnSpan( null );

					if ( ! gform.tools.isRtl() ) {
						helperLeft = ui.position.left;
					} else {
						helperLeft = ui.position.left + ( ui.helper.outerWidth() );
					}

					handleDrag( event, ui, ui.position.top, helperLeft );
				},
				stop: function( event, ui ) {
					$container.removeClass( 'dragging' );
					$editorContainer.removeClass( 'droppable' );
					$elem.removeClass( 'placeholder' );
					$elements().removeClass( 'hovering' );

					if ( $indicator().data( 'target' ) ) {
						moveByTarget( $elem, $indicator().data( 'target' ), $indicator().data( 'where' ) );
					}

					$indicator().remove();

					ui.helper.remove();
				},
			} )
			.resizable( {
				handles: 'e, w',
				start: function( event, ui ) {
					if ( gf_legacy.is_legacy === '1' ) {
						$element.resizable( 'option', 'minWidth', ui.size.width );
						$element.resizable( 'option', 'maxWidth', ui.size.width );
						alert( gf_vars.alertLegacyMode );
						return;
					}
					max = null;
					$container.addClass( 'resizing' );
				},
				resize: function( event, ui ) {
					if ( gf_legacy.is_legacy === '1' ) {
						return;
					}
					var columnWidth = $container.outerWidth() / columnCount,
						$item = ui.element,
						width = $item.outerWidth(),
						span = Math.max( min, Math.round( width / columnWidth ) ),
						prevSpan = $item.getGridColumnSpan(),
						$group = getGroup( getGroupId( $item ) ),
						lastInGroup = isLastInGroup( $item, $group ),
						$spacer = $group.filter( '.spacer' ),
						$sibling = lastInGroup && ! $spacer.length ? null : $item.next(),
						siblingSpan;

					/**
					 * Calculate the max on the first move of a resize and then rely on the set max until a new resize is initialized.
					 * Attempting to recalculate the max on each move results in some odd calculations...
					 */
					if ( max === null ) {
						if ( $group.length > 1 ) {
							siblingSpan = $sibling ? getGroupGridColumnSpan( $sibling ) : 0;
							max = prevSpan + siblingSpan;
						} else {
							max = columnCount;
						}
					}

					if ( ui.element.data( 'fieldClass' ) === 'gform_editor_submit_container' ) {
						min = 1;
					} else {
						min = columnCount / 4;
					}

					/**
					 * We've calculated the desired span based on the physical size of the field. Now let's adjust it to
					 * make sure it's not too big or too small.
					 *
					 * If the field is in a group, we will deduct the minimum span from the max to always save room for
					 * the field to it's right. If it the last field, we do not have to save this room.
					 */
					var calculatedMax = max;
					if ( $item.next().data( 'fieldClass' ) === 'gform_editor_submit_container' ) {
						calculatedMax = max - 1;
					} else if ( $group.length > 1 && ! lastInGroup ) {
						calculatedMax = max - min;
					}
					span = getAdjustedGridColumnSpan( span, min, calculatedMax );

					$().add( ui.helper ).add( ui.element )
						// Resizable will set a width with each increment, we have to deliberately override this.
						.css( 'width', 'auto' ).css( 'left', 'auto' )
						.setGridColumnSpan( span );

					if ( $sibling ) {
						siblingSpan = max - span;
						$sibling
							.css( 'width', 'auto' )
							.setGridColumnSpan( siblingSpan );
					}

					// If resizing a field to it's max allowable span, remove the spacer.
					if ( span == columnCount || span == max ) {
						removeSpacer( $spacer );
					}
					// Insert spacer when resizing a field with no field to its right.
					else if ( lastInGroup && ! $spacer.length && getGroupGridColumnSpan( $group ) < columnCount ) {
						addSpacer( $item, getGroupId( $item ), 1 );
					}
				},
				stop: function() {
					if ( gf_legacy.is_legacy === '1' ) {
						return;
					}
					$container.removeClass( 'resizing' );
				},
			} );
	}

	/**
	 * @function getFieldsAsRows
	 * @description Return an array of elements plus group ids grouped into rows as sub arrays.
	 *
	 * @since 2.5.1
	 *
	 * @returns {*[]}
	 */

	function getFieldsAsRows() {
		var rows = [];
		var row = [];
		var previousOffset = $fields[ 0 ].offsetTop;

		$fields.each( function() {
			// this element is on the same row as previous
			if ( previousOffset === this.offsetTop ) {
				row.push( {
					el     : this,
					groupId: this.dataset.groupid,
				} );
			} else {
				// we are on a new row, push previously stored row and start a new store
				if ( row.length ) {
					rows.push( row );
					row = [];
				}
				// push the current item into the new store
				row.push( {
					el     : this,
					groupId: this.dataset.groupid,
				} );
			}
			previousOffset = this.offsetTop;
		} );

		return rows;
	}

	/**
	 * @function setUniqueGroupIdForRow
	 * @description Get a new unique groupId and apply it to a row of fields.
	 *
	 * @since 2.5.1
	 *
	 * @param {Array} row An array of objects that each contain a field element and its groupId.
	 */

	function setUniqueGroupIdForRow( row ) {
		var groupId = getGroupId();
		row.forEach( function( entry ) {
			$( entry.el ).setGroupId( groupId );
		} );
	}

	/**
	 * @function validateGroupIds
	 * @description Iterate over all fields and patch any duplicate group id's, or rows that have mismatched group id's.
	 *
	 * @since 2.5.1
	 */

	function validateGroupIds() {
		// no need to run in legacy mode or if no fields
		if ( window.gf_legacy.is_legacy === '1' || ! $fields.length ) {
			return;
		}
		var rows = getFieldsAsRows();
		var ids = [];

		rows.forEach( function( currentRow ) {
			var rowIds = [];
			var duplicateFound = false;

			currentRow.forEach( function( entry ) {
				if ( ids.indexOf( entry.groupId ) !== - 1 ) {
					// this id has already been used in a previous field row
					duplicateFound = true;
				}
				rowIds.push( entry.groupId );
			} );

			// test if all ids for the row match
			var groupIdsMatchForRow = rowIds.every( function( val, i, arr ) {
				return val === arr[ 0 ];
			} );
			// if the row has mismatched id's, or contains an id used before, scrub and set fresh group id for the row
			if ( ! groupIdsMatchForRow || duplicateFound ) {
				setUniqueGroupIdForRow( currentRow );
			}
			// store the id for duplicate check in subsequent iterations
			ids.push( currentRow[ 0 ].groupId );
		} );
	}

	/**
	 * @function setSubmitButtonGroup
	 * @description Sets the submit button's group ID to the group ID of the last row if it is inline.
	 *
	 * @since 2.6
	 */
	function setSubmitButtonGroup() {
		if ( $( '#field_submit' ).data( 'field-position') === 'inline' ) {
			// Find the last group id.
			var lastGroup = jQuery( '#field_submit' ).prev().attr( 'data-groupid' );
			// Move the submit button to the group.
			jQuery( '#field_submit' ).setGroupId( lastGroup );
		}
	}

	/**
	 * Initialize the field buttons so they can be dragged over the layout editor.
	 *
	 * @param {jQuery} $buttons All field buttons.
	 */
	function initFieldButtons( $buttons ) {
		$buttons
			.on( 'mousedown touchstart', function() {
				// closes any open flyouts
				gform.tools.trigger( 'gform/flyout/close-all' );
				// hides the tooltip during drag, stop method sets it back using the data-description
				// start was too late to execute this with, the tooltip would persist in some browsers
				$( this ).attr( 'title', '' );
			} )
			.draggable( {
				helper: 'clone',
				revert: function () {
					// @todo Return true when field will not be added. This is low priority polish.
					return false;
				},
				cancel: false,
				appendTo: $container,
				containment: 'document',
				start: function( event, ui ) {
					clearFieldSelection();

					$editorContainer.addClass( 'droppable' );

					if ( gf_vars[ 'currentlyAddingField' ] == true ) {
						return false;
					}

					// Match the helper to the current elements size.
					ui.helper
						.width( $( this ).width() )
						.height( $( this ).height() );

					$container.addClass( 'dragging' );
					$elem = $( this ).clone();
					$elem.addClass( 'placeholder' );

					$( this ).addClass( 'fieldPlaceholder' );
				},
				drag: function( event, ui ) {
					// When form has no fields, there is only one place the field can be dragged...
					if ( ! form.fields.length ) {
						return;
					}

					/**
					 * New field buttons are dragged relative to #wpbody so their position needs to be adjusted to work the
					 * the same way as dragging an existing field (which is relative to #gform_fields).
					 */
					var helperTop = ui.position.top - 0 + ( ui.helper.outerHeight() / 2 ),
						helperLeft = ui.position.left - 0 + ( ui.helper.outerWidth() / 2 );

					handleDrag( event, ui, helperTop, helperLeft );

				},
				stop: function( event, ui ) {
					$( this ).removeClass( 'fieldPlaceholder' );
					$editorContainer.removeClass( 'droppable' );
					$container.removeClass( 'dragging' );

					var isAddingField = false;

					// Make sure the *entire* button has been dragged into the fields area before we add a field.
					if ( ! form.fields.length && isNoFieldsDrop ) {
						isNoFieldsDrop = false;
						isAddingField = addField( ui.helper.data( 'type' ) );
					} else if ( form.fields.length && $indicator( false ).data( 'target' ) ) {
						isAddingField = addField( ui.helper.data( 'type' ) );
					}

					// If we're not adding a new field, remove our placeholder element.
					if ( ! isAddingField ) {
						$indicator( false ).remove();
						$elem.remove();
						$elem = null;
					}

					$( this ).attr( 'title', $( this ).attr( 'data-description' ) );
				}
			} )
			.on( 'click keypress', function () {
				$elem = null;
			} );
	}

	/**
	 * Handle placing the indicator when a field is dragged over the layout editor.
	 *
	 * @param {Event}  event
	 * @param {object} ui         jQuery UI helper object which manages the current state.
	 * @param {number} helperTop  The top position of the element being dragged.
	 * @param {number} helperLeft The left position of the element being dragged.
	 */
	function handleDrag( event, ui, helperTop, helperLeft ) {

		$elements().removeClass( 'hovering' );

		if ( ! isInEditorArea( helperLeft, helperTop ) ) {
			$indicator( false ).remove();
			return;
		}

		// Check if field is dragged *above* all other fields.
		if ( helperTop < 0 ) {
			$indicator()
				.css( {
					top: -30,
					left: 0,
					height: '4px',
					width: $container.outerWidth()
				} )
				.data( {
					where: 'top',
					target: $elements().first()
				} );
			return;
		}
		// Check if field is dragged *below* all other fields.
		else if ( helperTop > $container.outerHeight() ) {
			if ( $elements().last().data( 'field-class' ) !== 'gform_editor_submit_container' && $elements().last().prev().data( 'field-class' ) !== 'gform_editor_submit_container' ) {
				$indicator()
					.css( {
						top: $container.outerHeight() - 14,
						left: 0,
						height: '4px',
						width: $container.outerWidth()
					} )
					.data( {
						where: 'bottom',
						target: $elements().last()
					} );
			}
			return;
		}

		$elements()
			.not( ui.helper )
			.not( this )
			.each( function() {

				var $target = $( this ),
					sibPos = $target.position(),
					sibArea = {
						top: sibPos.top,
						right: sibPos.left + $target.outerWidth(),
						bottom: sibPos.top + $target.outerHeight(),
						left: sibPos.left
					};

				if ( ! isInArea( helperLeft, helperTop, sibArea ) ) {
					return;
				}

				$target.addClass( 'hovering' );

				if ( isSpacer( $target ) ) {
					$target = $target.prev();
					sibPos = $target.position();
					where = 'right';
				}

				var where = whichArea( helperLeft, helperTop, sibArea, $target.outerWidth(), $target.outerHeight() ),
					targetGroupId = getGroupId( $target ),
					$targetGroup = getGroup( targetGroupId, false );

				var isGroupMaxed = $targetGroup.length >= ( columnCount / min );

				if ( getGroupId( $target ) === getGroupId( ui.helper ) ) {
					isGroupMaxed = false;
				}

				var available = isSpaceAvailable( ui, $target );

				if ( $target.data( 'field-class' ) === 'gform_editor_submit_container' ) {
					if ( gform.tools.isRtl() ) {
						if ( where === 'left' || where === 'bottom' ) {
							return;
						}
					}
					if ( where === 'right' || where === 'bottom' ) {
						return;
					}
				}

				if ( where === 'left' || where === 'right' ) {
					if ( $target.data( 'field-position' ) === 'bottom' ) {
						return;
					}
					// Columns are not supported in Legacy markup or with Page or Section fields.
					if ( ! areColumnsEnabled( $target, $elem ) ) {
						return;
					} else if ( isGroupMaxed || ( available === false ) ) {
						return;
					}
				}

				if ( where === 'bottom' && isButtonInGroup( $targetGroup ) ) {
					return;
				}

				$indicator().data( {
					where: where,
					target: $target
				} );

				// drop indicator is a different distance from field in compact view.
				if ( $target.parents( '.gform-compact-view' ).length > 0 ) {
					var topDistance = 10;
					var bottomDistance = 6;
				} else {
					var topDistance = 30;
					var bottomDistance = 26;
				}

				// Where on the child field has the helper been dragged?
				switch ( where ) {
					case 'left':

						$indicator()
							.css( {
								top: sibPos.top,
								left: sibPos.left - 10,
								height: $target.outerHeight(),
								width: '4px'
							} );

						return false;
					case 'right':

						$indicator().css( {
							top: sibPos.top,
							left: sibPos.left + $target.outerWidth() + 6,
							right: 'auto',
							height: $target.outerHeight(),
							width: '4px'
						} );

						return false;
					case 'bottom':
						$indicator().css( {
							top: sibPos.top + $target.outerHeight() + bottomDistance,
							left: 0,
							height: '4px',
							width: '100%',
						} );

						return false;
					case 'top':

						$indicator().css( {
							top: sibPos.top - topDistance,
							left: 0,
							height: '4px',
							width: '100%'
						} );

						return false;
				}

			} );

	}

	/**
	 * Determine whether columns are enabled based on the current element and the target over which it is being dragged.
	 *
	 * @param {jQuery} $target The element over which the dragged element is currently positioned.
	 * @param {jQuery} $elem   The element that is being dragged.
	 *
	 * @returns {boolean}
	 */
	function areColumnsEnabled( $target, $elem ) {

		if ( $editor.hasClass( 'gform_legacy_markup' ) ) {
			return false;
		}

		if ( $target.hasClass( 'gpage' ) || $target.hasClass( 'gsection' ) || $target.hasClass( 'gform_hidden' ) ) {
			return false;
		}

		if ( $elem.hasClass( 'gpage' ) || $elem.hasClass( 'gsection' ) || $elem.hasClass( 'gform_hidden' ) || $elem.data( 'type' ) === 'hidden' ) {
			return false;
		}

		if ( $elem.is( 'button' ) && ( $.inArray( $elem.val().toLowerCase(), [ 'page', 'section' ] ) !== -1 ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Determine whether the given coordinates are in the specified area.
	 *
	 * @param {number} x    The left position of the coordinate.
	 * @param {number} y    The top position of the coordinate.
	 * @param {object} area An object of top, right, bottom and left positions.
	 *
	 * @returns {boolean}
	 */
	function isInArea( x, y, area ) {
		return y < area.bottom && y > area.top && x < area.right && x > area.left;
	}

	/**
	 * Determine which portion of a specified area the given coordinates are in.
	 *
	 * @param {number} x      The left position of the coordinate.
	 * @param {number} y      The top position of the coordinate.
	 * @param {object} area   An object of top, right, bottom and left positions.
	 * @param {number} width  The width of the given area.
	 * @param {number} height The height of the given area.
	 *
	 * @returns {string}
	 */
	function whichArea( x, y, area, width, height ) {

		var thresholdLeft = area.left + ( width / 2 ),
			thresholdRight = area.right - ( width / 2 ),
			thresholdTop = area.top + ( height / 5 ),
			thresholdBottom = area.bottom - ( height / 5 );

		if ( y > area.top && y < thresholdTop ) {
			return 'top';
		} else if ( y < area.bottom && y > thresholdBottom ) {
			return 'bottom';
		} else if ( x > area.left && x < thresholdLeft ) {
			return 'left';
		} else if ( x < area.right && x > thresholdRight ) {
			return 'right';
		}

		return 'center';
	}

	/**
	 * Determine whether the given coordinates are in the area of the layout editor.
	 *
	 * @param {number} x The left position of the coordinate.
	 * @param {number} y The top position of the coordinate.
	 *
	 * @returns {boolean}
	 */
	function isInEditorArea( x, y ) {

		if ( ! gform.tools.isRtl() ) {
			var editorOffsetLeft = $editorContainer.offset().left;
		} else {
			var editorOffsetLeft = $container.offset().left;
		}
		var containerOffset = $container.offset(),
			offsetTop = containerOffset.top - $editorContainer.offset().top,
			offsetLeft = containerOffset.left - editorOffsetLeft,
			buttonWidth = $button.outerWidth() || null,
			editorArea = {
				top: -offsetTop + buttonWidth,
				right: -offsetLeft + $editorContainer.outerWidth() - $sidebar.outerWidth() - buttonWidth,
				bottom: -offsetTop + $editorContainer.outerHeight(),
				left: -offsetLeft,
			};

		return y > editorArea.top && y < editorArea.bottom && x > editorArea.left && x < editorArea.right;
	}

	/**
	 * Check if a group has room to accommodate an additional field.
	 *
	 * @param {object} ui      jQuery UI helper object which manages the current state.
	 * @param {jQuery} $target The element over which the dragged element was last positioned.
	 */
	function isSpaceAvailable( ui, $target ) {
		var targetSpan, splitSpan, $targetGroup, groupId, $spacer, helperGroupId;

		groupId = getGroupId( $target );
		helperGroupId = getGroupId( ui.helper );
		$targetGroup = getGroup( groupId );

		if ( groupId === helperGroupId ) {
			return true;
		}

		// Figure out if we're dropping a field onto a spacer or next to a spacer.
		if ( isSpacer( $target ) ) {
			$spacer = $target;
			$target = $target.prev();
		} else if ( isSpacer( $target.next() ) && $targetGroup.index( $target.next() ) !== false ) {
			$spacer = $target.next();
		}

		// If we're dropping onto or next to a spacer, set the target span to the spacer span.
		targetSpan = $spacer ? $spacer.getGridColumnSpan() : null;

		// Determine the span of the field we're dropping in.
		if ( targetSpan ) {
			splitSpan = targetSpan;
		} else if ( isEvenSplit( $targetGroup ) ) {
			splitSpan = columnCount / ( $targetGroup.length + 1 ); // +1 for the element about to be added to this group.
		} else {
			targetSpan = $target.getGridColumnSpan();
			splitSpan = targetSpan / 2;
		}

		// If the span of the field we're dropping in calculates to less than 3, no space available.
		if ( parseInt( splitSpan ) < 3 ) {
			return false;
		}
	}

	/**
	 * Move the given element based on the specified target and location.
	 *
	 * @param {jQuery} $elem   The element to be moved.
	 * @param {jQuery} $target The element over which the dragged element was last positioned.
	 * @param {string} where   The area of the target element over which the element was last positioned.
	 */
	function moveByTarget( $elem, $target, where ) {

		if ( ! $target ) {
			return;
		}

		if ( $target.hasClass( 'gform_button' ) ) {
			return;
		}

		var targetSpan,
			splitSpan,
			$targetGroup,
			$resizeGroup,
			groupId,
			sourceGroupId,
			movingIntoTargetGroup,
			$spacer;

		sourceGroupId = getGroupId( $elem );
		groupId = getGroupId( $target );
		$targetGroup = getGroup( groupId );

		if ( isSpacer( $target ) ) {
			$spacer = $target;
			$target = $target.prev();
		} else if ( ( isSpacer( $target.next() ) || isPlaceholder( $target.next() ) ) && $targetGroup.index( $target.next() ) !== false ) {
			$spacer = $target.next();
		}

		movingIntoTargetGroup = where === 'left' || where === 'right';

		if ( $spacer && movingIntoTargetGroup ) {
			targetSpan = $spacer.getGridColumnSpan();
			removeSpacer( $spacer );
			$targetGroup = getGroup( groupId );
		}

		if ( where == 'top' ) {
			$target = $targetGroup.first();
		} else if ( where == 'bottom' ) {
			$target = $targetGroup.last();
		}

		var direction = gform.tools.isRtl() ? 'right' : 'left';

		if ( where == 'top' || where == direction ) {
			$elem.insertBefore( $target );
		} else {
			$elem.insertAfter( $target );
		}

		if ( ! movingIntoTargetGroup ) {

			groupId = getGroupId();
			$elem.setGridColumnSpan( columnCount );

		} else {

			if ( targetSpan ) {
				$resizeGroup = $elem;
				splitSpan = targetSpan;
			} else if ( isEvenSplit( $targetGroup ) ) {
				splitSpan = columnCount / ( $targetGroup.length + 1 ); // +1 for the element about to be added to this group.
				$resizeGroup = $targetGroup.add( $elem );
			} else {
				targetSpan = $target.getGridColumnSpan();
				splitSpan = targetSpan / 2;
				$resizeGroup = $target.add( $elem );
			}

			if ( parseInt( splitSpan ) == splitSpan ) {
				$resizeGroup.setGridColumnSpan( splitSpan );
			}
			// Handle non-even spans by making one smaller than the other. Should only happen in non-even splits.
			else {
				var floor = Math.floor( splitSpan ),
					ceil = Math.ceil( splitSpan );
				$elem.setGridColumnSpan( floor );
				$target.setGridColumnSpan( ceil );
			}

		}

		$elem.setGroupId( groupId );

		// Reset sizes on the group the element has been removed from.
		resizeGroup( sourceGroupId );

	}

	/**
	 * Get the group ID of the given element or generate a new group ID if none exists.
	 *
	 * @param {jQuery}  $elem        The element for which we are getting the group ID.
	 * @param {boolean} autoGenerate Whether or not a group ID should be auto-generated if no group ID exists.
	 *
	 * @returns {string}
	 */
	function getGroupId( $elem, autoGenerate ) {
		var groupId;
		if ( typeof $elem !== 'undefined' ) {
			groupId = $elem.attr( 'data-groupId' );
		}
		if ( ! groupId && ( autoGenerate || typeof autoGenerate === 'undefined' ) ) {
			groupId = 'xxxxxxxx'.replace( /[xy]/g, function ( c ) {
				var r = Math.random() * 16 | 0, v = c == 'x' ? r : r & 0x3 | 0x8;
				return v.toString( 16 );
			} );
		}
		return groupId;
	}

	/**
	 * Get a group of field elements by the given group ID.
	 *
	 * @param {string} groupId The ID of the group to be set on the targeted element.
	 *
	 * @returns {jQuery}
	 */
	function getGroup( groupId, spacers ) {
		if ( spacers || 'undefined' === typeof( spacers ) ) {
			return $elements()
				.filter( '[data-groupId="{0}"]'.gformFormat( groupId ) )
				.not( '.ui-draggable-dragging' );
		} else {
			return $elements()
				.filter( '[data-groupId="{0}"]'.gformFormat( groupId ) )
				.not( '.ui-draggable-dragging' )
				.not( '.spacer' );
		}
	}

	/**
	 * Get the grid column span value adjusted by a specified min and max value.
	 *
	 * @param {number} span The desired number columns to be spanned.
	 * @param {number} min  The minimum number of columns that must be spanned.
	 * @param {number} max  The maximum number of columns that can be spanned.
	 *
	 * @returns {number}
	 */
	function getAdjustedGridColumnSpan( span, min, max ) {
		return Math.max( min, Math.min( max, span ) );
	}

	/**
	 * Get the combined grid column span value of the given group.
	 *
	 * @param {jQuery} $group A group of field elements making up a row.
	 *
	 * @returns {number}
	 */
	function getGroupGridColumnSpan( $group ) {
		var span = 0;
		$group.each( function () {
			span += $( this ).getGridColumnSpan();
		} );
		return span;
	}

	/**
	 * Determine whether the grid column span for the given group is the same for all elements in the group.
	 *
	 * @param {jQuery} $group A group of field elements making up a row.
	 *
	 * @returns {boolean}
	 */
	function isEvenSplit( $group ) {

		if ( $group.length === 0 ) {
			return isEvenSplit = true;
		}
		var baseSpan = $group.first().getGridColumnSpan(),
			isEvenSplit = true;

		$group.each( function () {
			var span = $( this ).getGridColumnSpan();
			if ( span !== baseSpan ) {
				isEvenSplit = false;
				return false;
			}
		} );

		return isEvenSplit;
	}

	/**
	 * Resize the elements in a group based on the provided group ID.
	 *
	 * @param {string} groupId The ID of the group to be set on the targeted element.
	 */
	function resizeGroup( groupId ) {

		var $group = getGroup( groupId ),
			splitSpan = columnCount / ( $group.length ),
			$spacer = $group.filter( '.spacer' );

		// If the only field in a group is a spacer, remove the spacer.
		if ( $group[0] === $spacer[0] && $group.length > 0 ) {
			removeSpacer( $spacer );
		}

		$group.setGridColumnSpan( splitSpan );

	}

	/**
	 * Determine whether the given element is the last element in the specified group.
	 *
	 * @param {jQuery} $elem  The element to check if it is the last in the specified group.
	 * @param {jQuery} $group The group of field elements to which the given element belongs.
	 *
	 * @returns {boolean}
	 */
	function isLastInGroup( $elem, $group ) {
		$group = $group.not( '.spacer' );
		return $group.length === 1 || $group.last()[ 0 ] === $elem[ 0 ];
	}

	/**
	 * Determine if a submit button is in the group.
	 *
	 * @since 2.6
	 *
	 * @param {jQuery} $group The group of field elements to check for a submit button.
	 *
	 * @returns {boolean}
	 */
	function isButtonInGroup( $group ) {
		return $group.filter( '[data-field-class="gform_editor_submit_container"]' ).length > 0;
	}

	/**
	 * Move the button to the bottom of the form and adjust the location setting.
	 *
	 * @since 2.6
	 *
	 */
	function moveButtonToBottom() {
		SetSubmitLocation( 'bottom' );
		jQuery('#field_submit').attr( 'data-field-position', 'bottom' );
		jQuery('input[name="submit_location"][value="bottom"]').prop( 'checked', true );
	}

	/**
	 * Insert a Spacer field after the given field element.
	 *
	 * @param {jQuery} $field  The field element after which the Spacer should be inserted.
	 * @param {string} groupId The ID of the group to be set on the targeted element.
	 * @param {number} span    The number of columns the Spacer should span.
	 *
	 * @returns {jQuery}
	 */
	function addSpacer( $field, groupId, span ) {

		var $spacer = $( '<div class="spacer gfield"></div>' )
			.setGroupId( groupId )
			.setGridColumnSpan( span );

		$field.after( $spacer );

		return $spacer;
	}

	/**
	 * Remove the given Spacer field from the DOM.
	 *
	 * @param {jQuery} $spacer A field element representing a Spacer field.
	 */
	function removeSpacer( $spacer ) {
		$spacer
			.setGridColumnSpan( 0 )
			.remove();
	}

	/**
	 * Determine whether the given element is a Spacer field.
	 *
	 * @param {jQuery} $elem The element for which to determine if it is a Spacer field.
	 *
	 * @returns {boolean}
	 */
	function isSpacer( $elem ) {
		return $elem.filter( '.spacer' ).length > 0;
	}

	/**
	 * Determine whether the given element is a Placeholder.
	 *
	 * @since 2.5
	 *
	 * @param {jQuery} $elem The element for which to determine if it is a placeholder.
	 *
	 * @returns {boolean}
	 */
	function isPlaceholder( $elem ) {
		return $elem.filter( '[data-js-field-loading-placeholder]' ).length > 0;
	}

	/**
	 * Get the Gravity Forms field object based on the given element.
	 *
	 * @param {jQuery} $elem The element to be used to fetch the field object.
	 *
	 * @returns {object|boolean}
	 */
	function getFieldByElement( $elem ) {
		var id = $elem.attr( 'id' );
		var fieldId = id && id.indexOf( 'field_' ) !== -1 ? String( id ).replace( 'field_', '' ) : false;
		return fieldId ? GetFieldById( fieldId ) : false;
	}

	/**
	 * Add a new field of the specified type to the form.
	 *
	 * @param {string} type The field type to add to the form.
	 *
	 * @returns {boolean}
	 */
	function addField( type ) {
		return StartAddField( type, Math.max( 0, $container.children().index( $elem ) ) );
	}

	/**
	 * Deselect the currently selected field.
	 */
	function clearFieldSelection() {
		$elements().removeClass( 'field_selected' );
		$( '.sidebar' ).tabs( 'option', 'active', 0 );
		HideSettings();
	}

	/**
	 * Get all field elements in current form.
	 *
	 * @returns {jQuery|[]}
	 */
	function $elements() {
		return $container.find( '.gfield' );
	}

	/**
	 * Create or return the current Indicator. The Indicator indicates where the currently dragged field will be placed when dropped.
	 *
	 * @param {boolean} create Whether or not an indicator should be created if it does not exist.
	 *
	 * @returns {jQuery}
	 */
	function $indicator( create ) {

		create = typeof create === 'undefined';

		var $indicator = $( '#indicator' );

		if ( ! $indicator.length && create ) {
			$indicator = $( '<div id="indicator"></div>' );
			$container.append( $indicator );
		}

		return $indicator;
	}

}

initLayoutEditor( jQuery );
