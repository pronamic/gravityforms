var GFPageConditionalLogic = function (args) {
    var self = this,
        $ = jQuery;

    /**
     * Initialize Feed Ordering
     */
    self.init = function () {

        // Assign options to instance.
        self.options = args;

        self.triggerInputIds = self.getTriggerInputIds(self.options.pages);

        self.formWrapper = '#gform_wrapper_' + self.options.formId;

		self.startAtZero = $(self.formWrapper + ' .gf_progressbar_wrapper').data('startAtZero');

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

        let page, isMatch, isVisible;

        for ( let i = 0; i < self.options.pages.length; i++ ) {

            page = self.options.pages[i];
            isMatch = self.evaluatePage( page, self.options.formId );
            isVisible = self.isPageVisible( page );

            if ( ! isMatch && isVisible !== false ) {
                self.hidePage( page );
            } else if ( isMatch && !isVisible ) {
                self.showPage( page );
            }
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
        gform.utils.trigger( {
            event: 'gform/frontend_pages/evaluated',
            data: {
                formId: self.options.formId,
                pages: self.options.pages
            },
            native: false
        } );

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

    self.showPage = function ( page ) {

        var isVisible = self.isPageVisible(page);

        if (isVisible === true) {
            return;
        }

        page.isVisible = true;
        $('#gform_' + self.options.formId + ' div[data-js="page-field-id-' + page.fieldId + '"]').attr('data-conditional-logic', 'visible');
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

    self.hidePage = function ( page ) {

        var isVisible = self.isPageVisible( page );

        if (isVisible === false) {
            return;
        }

        page.isVisible = false;
        $('#gform_' + self.options.formId + ' div[data-js="page-field-id-' + page.fieldId + '"]').attr('data-conditional-logic', 'hidden');

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

    this.init();
};
