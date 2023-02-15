var GFPageConditionalLogic = function (args) {
    var self = this,
        $ = jQuery;

    /**
     * Initialize Feed Ordering
     */
    self.init = function () {

        // Assign options to instance.
        self.options = args;

        self.paginationType = self.options.pagination.type;

        self.triggerInputIds = self.getTriggerInputIds(self.options.pages);

        self.formWrapper = '#gform_wrapper_' + self.options.formId;

        if (self.paginationType === 'steps') {
            self.originalCurrentPage = parseInt($(self.formWrapper + ' .gf_step_active .gf_step_number').text(), 10);
        } else if (self.paginationType === 'percentage') {
            self.originalCurrentPage = parseInt($(self.formWrapper + ' .gf_step_current_page').text(), 10);
            self.originalProgress = parseInt($(self.formWrapper + ' .gf_progressbar_percentage span').text(), 10);
        }

        self.evaluatePages();

        self.bindEvents();

    };

    self.bindEvents = function () {

        gform.addAction('gform_input_change', function (elem, formId, inputId) {

            var fieldId = parseInt(inputId, 10) + '';
            var isTriggeredInput = $.inArray(inputId, self.triggerInputIds) !== -1 || $.inArray(fieldId, self.triggerInputIds) !== -1;

            if (self.options.formId == formId && isTriggeredInput) {
                self.evaluatePages();
            }
        });

    };

    self.evaluatePages = function () {

        var page, stepNumber, isMatch, isVisible, progress, visibleStepNumber = 1, currentPage = self.originalCurrentPage;

        for (var i = 0; i < self.options.pages.length; i++) {

            page = self.options.pages[i];
            stepNumber = i + 2; // plus 2 because the first page field is actually Step 2.
            isMatch = self.evaluatePage(page, self.options.formId);
            isVisible = self.isPageVisible(page);

            if (!isMatch && isVisible !== false) {
                self.hidePage(page, stepNumber);
            } else if (isMatch && !isVisible) {
                self.showPage(page, stepNumber);
            }

            // check if the page is visible and
	        // available as a step after evaluation.
            isVisible = self.isPageVisible(page);
            if (isVisible) {
                visibleStepNumber++;

                if (self.paginationType === 'steps') {
                    $('#gf_step_' + self.options.formId + '_' + stepNumber).find('.gf_step_number').html(visibleStepNumber);
                } else if (self.paginationType === 'percentage' && self.originalCurrentPage == stepNumber) {
                    currentPage = visibleStepNumber;
                    $(self.formWrapper + ' .gf_step_current_page').html(currentPage);
                }
            }

        }

        if (self.paginationType === 'percentage') {
            currentPage = self.options.pagination.display_progressbar_on_confirmation === true ? ( currentPage - 1 ) : currentPage;
        }

        progress = Math.floor( currentPage / visibleStepNumber * 100 );

        if (self.paginationType === 'percentage') {
            var progressPercent = progress + '%';

            $(self.formWrapper + ' .gf_step_page_count').html(visibleStepNumber);
            $(self.formWrapper + ' .gf_progressbar_percentage span').html(progressPercent);
            $(self.formWrapper + ' .gf_progressbar_percentage').removeClass('percentbar_' + self.originalProgress).addClass('percentbar_' + progress).css('width', progressPercent);
        }

        // Update the form button based on progress
        if ( progress === 100 ) {
            // Treat the current page as the last one.
            self.updateButtonToSubmitText( self.originalCurrentPage - 1 );
        } else {
	        // Update the button on the current page.
	        $( '[id^=gform_next_button_' + self.options.formId + '_]' ).each( function ( e, element ) {
		        if ( $( element ).is(':visible') ) {
			        self.updateButtonToNextText( self.options.pages[ e ] );
		        }
	        });

            // Update the button on the last page.
            self.updateButtonToSubmitText();
        }

        /**
         * Fires after the conditional logic on the form has been evaluated.
         *
         * @since 2.5
         *
         * @param array $pages     A collection of page field objects.
         * @param int   $formId    The form id.
         */
        gform.doAction('gform_frontend_pages_evaluated', self.options.pages, self.options.formId, self);
        gform.doAction('gform_frontend_pages_evaluated_{0}'.gformFormat(self.options.formId), self.options.pages, self.options.formId, self);

    };

    self.evaluatePage = function (page, formId) {

        // Pages with no configured conditional logic always a match.
        if (!page.conditionalLogic) {
            return true;
        }

        return gf_get_field_action(formId, page.conditionalLogic) === 'show';
    };

    self.getTriggerInputIds = function () {
        var inputIds = [];
        for (var i = 0; i < self.options.pages.length; i++) {

            var page = self.options.pages[i];

            if (!page.conditionalLogic) {
                continue;
            }

            for (var j = 0; j < page.conditionalLogic.rules.length; j++) {
                var rule = self.options.pages[i].conditionalLogic.rules[j];
                if ($.inArray(rule.fieldId, inputIds) === -1) {
                    inputIds.push(rule.fieldId);
                }
            }

        }
        return inputIds;
    };

    self.isPageVisible = function (page) {

        if (typeof page != 'object') {
            page = self.getPage(page);
            if (!page) {
                return false;
            }
        }

        return typeof page.isVisible != 'undefined' ? page.isVisible : null;
    };

    self.getPage = function (fieldId) {
        for (var i = 0; i < self.options.pages.length; i++) {
            var page = self.options.pages[i];
            if (page.fieldId == fieldId) {
                return page;
            }
        }
        return false;
    };

    self.showPage = function (page, stepNumber) {

        var isVisible = self.isPageVisible(page);

        if (isVisible === true) {
            return;
        }

        page.isVisible = true;
        $('#gf_step_' + self.options.formId + '_' + stepNumber).removeClass('gf_step_hidden');

        /**
         * Fires after the conditional logic on the form has been evaluated and the page has been found to be visible.
         *
         * @since 2.5
         *
         * @param array $pages  A collection of page field objects.
         * @param int   $formId The form id.
         */
        gform.doAction('gform_frontend_page_visible', page, self.options.formId);
        gform.doAction('gform_frontend_page_visible_{0}'.gformFormat(self.options.formId), page, self.options.formId);

    };

    self.hidePage = function (page, stepNumber) {

        var isVisible = self.isPageVisible(page);

        if (isVisible === false) {
            return;
        }

        page.isVisible = false;
        $('#gf_step_' + self.options.formId + '_' + stepNumber).addClass('gf_step_hidden');

        /**
         * Fires after the conditional logic on the form has been evaluated and the page has become hidden.
         *
         * @since 2.5
         *
         * @param array $pages  A collection of page field objects.
         * @param int   $formId The form id.
         */
        gform.doAction('gform_frontend_page_hidden', page, self.options.formId);
        gform.doAction('gform_frontend_page_hidden_{0}'.gformFormat(self.options.formId), page, self.options.formId);

    };

	/**
	 * The lastPageIndex might get miscalculated at some point during the flow. If it's outside the bounds of
	 * the page numbers, this resets it to the last natural page.
	 *
	 * @since 2.5.3
	 *
	 * @param {number|undefined} lastPageIndex The calculated last page of the form.
	 * @return {number} The calculated last page number.
	 */
	self.getValidatedLastPageIndex = function( lastPageIndex ) {
		if ( lastPageIndex === undefined ||  lastPageIndex < 0 || lastPageIndex >= self.options.pages.length ) {
			return self.options.pages.length - 1;
		}

		return lastPageIndex;
	};

	/**
	 * Checks whether the page the user is on is also considered to be the last page.
	 *
	 * Without conditional logic, forms have cardinal page numbers: 1, 2, 3, 4, 5, 6.
	 * With conditional logic, a "last page" of a form might not be the last page. e.g., 4 is the "submit" page.
	 *
	 * @since 2.5.3
	 *
	 * @param {number|string} targetPageNumber Next page to be shown.
	 * @param {number|string} lastPageNumber Actual last page of the form without conditional logic.
	 * @param {number|undefined} lastPageIndex In the scenario above, lastPageIndex is 4.
	 * @return {boolean} True or false whether the current page is the last calculated page.
	 */
	self.currentPageIsLastPage = function( targetPageNumber, lastPageNumber, lastPageIndex ) {
		return targetPageNumber === lastPageNumber || lastPageIndex !== undefined;
	};

	/**
	 * Updates the text of the next button to be submit text on a paginated form.
	 *
	 * This method changes the text of the next button to the text of
	 * the submit button on the form if the user is on the page
	 * determined to be the last page of the form.
	 *
	 * @since Unknown
	 *
	 * @param {number|undefined} lastPageIndex The calculated last page of the form.
	 * @return {void}
	 */
	self.updateButtonToSubmitText = function ( lastPageIndex ) {
		var targetPageNumber = parseInt($('#gform_target_page_number_' + self.options.formId).val(), 10),
			lastPageNumber = self.options.pages.length + 1;

		// No need to update the button, we're not on the last page.
		if ( ! self.currentPageIsLastPage( targetPageNumber, lastPageNumber, lastPageIndex ) ) {
			return;
		}

		var calculatedLastPageIndex = self.getValidatedLastPageIndex( lastPageIndex );

		var lastPageField = self.options.pages[ calculatedLastPageIndex ],
			lastNextButton = $('#gform_next_button_' + self.options.formId + '_' + lastPageField.fieldId),
			isLastPageVisible = self.isPageVisible(lastPageField),
			formButton = $('#gform_submit_button_' + self.options.formId);

		if (! isLastPageVisible ) {
			if (formButton.attr('type') === 'image') {
				// Cache last next button image alt.
				if (lastNextButton.attr('type') === 'image') {
					lastNextButton.data('alt', lastNextButton.attr('alt'));
				}
				lastNextButton.attr('type', 'image').attr('src', formButton.attr('src')).attr('alt', formButton.attr('alt')).addClass('gform_image_button').removeClass('button');
			} else {
				lastNextButton.attr('type', 'button').val(formButton.val()).addClass('button').removeClass('gform_image_button');
			}

			// Set a mark on the page, so later on we can reset the button when evaluating pages.
			self.options.pages[ calculatedLastPageIndex ].isUpdated = true;
		} else {
			self.updateButtonToNextText( lastPageField );
		}
    };

	/**
	 * Updates the text of the submit button to be next text on a paginated form.
	 *
	 * This method changes the text of the submit button to the text of
	 * the next button on the form if the user is on the page
	 * determined to not be the last page of the form.
	 *
	 * @since Unknown
	 *
	 * @param {number|undefined} page The current page of the form.
	 * @return {void}
	 */
	self.updateButtonToNextText = function ( page ) {
		// No need to reset if the button hasn't been updated.
		if ( ! page.hasOwnProperty( 'isUpdated' ) ) {
			return;
		}

		delete page.isUpdated;

		var nextButton = $('#gform_next_button_' + self.options.formId + '_' + page.fieldId);
		if (page.nextButton.type === 'image') {
			nextButton.attr('type', 'image').attr('src', page.nextButton.imageUrl).attr('alt', nextButton.data('alt')).addClass('gform_image_button').removeClass('button');
		} else {
			nextButton.attr('type', 'button').val(page.nextButton.text).addClass('button').removeClass('gform_image_button');
		}
	}

    this.init();
};
