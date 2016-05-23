define([
    'jquery',
    'underscore',
    'jquery.multiselect',
    'jquery.multiselect.filter'
], function($, _) {
    'use strict';

    /**
     * Multiselect decorator class.
     * Wraps multiselect widget and provides design modifications
     *
     * @export orofilter/js/multiselect-decorator
     * @class  orofilter.MultiselectDecorator
     */
    var MultiselectDecorator = function(options) {
        this.initialize(options);
    };

    MultiselectDecorator.prototype = {
        /**
         * Multiselect widget element container
         *
         * @property {Object}
         */
        element: null,

        /**
         * Default multiselect widget parameters
         *
         * @property {Object}
         */
        parameters: {
            height: 'auto'
        },

        /**
         * @property {Boolean}
         */
        contextSearch: true,

        /**
         * Initialize all required properties
         */
        initialize: function(options) {
            if (!options.element) {
                throw new Error('Select element must be defined');
            }
            this.element = options.element;

            if (_.has(options, 'contextSearch')) {
                this.contextSearch = options.contextSearch;
            }

            options.parameters = options.parameters || {};
            _.defaults(options.parameters, this.parameters);

            // initialize multiselect widget
            this.multiselect(options.parameters);

            // initialize multiselect filter
            if (this.contextSearch) {
                this.multiselectfilter({
                    label: '',
                    placeholder: '',
                    autoReset: true
                });
            }
        },

        /**
         * Destroys decorator
         *  - removes created widgets
         *  - removes reference on element
         */
        dispose: function() {
            if (this.contextSearch && this.element.data('ech-multiselectfilter')) {
                this.multiselectfilter('destroy');
            }
            if (this.element.data('ech-multiselect')) {
                this.multiselect('destroy');
            }
            this.element.remove();
            delete this.element;
        },

        /**
         * Set design for view
         *
         * @param {Backbone.View} view
         */
        setViewDesign: function(view) {
            view.$('.ui-multiselect').removeClass('ui-widget').removeClass('ui-state-default');
            view.$('.ui-multiselect span.ui-icon').remove();
        },

        /**
         * Fix dropdown design
         *
         * @protected
         */
        _setDropdownDesign: function() {
            var widget = this.getWidget();
            widget.addClass('dropdown-menu');
            widget.removeClass('ui-widget-content');
            widget.removeClass('ui-widget');
            widget.find('.ui-widget-header').removeClass('ui-widget-header');
            widget.find('.ui-multiselect-filter').removeClass('ui-multiselect-filter');
            widget.find('ul li label').removeClass('ui-corner-all');
        },

        /**
         * Action performed on dropdown open
         */
        onOpenDropdown: function() {
            this._setDropdownDesign();
            this.getWidget().find('input[type="search"]').focus();
            $('body').trigger('click');
        },

        /**
         * Get minimum width of dropdown menu
         *
         * @return {Number}
         */
        getMinimumDropdownWidth: function() {
            var minimumWidth = 0;
            this.getWidget().find('.ui-multiselect-checkboxes').removeClass('fixed-li');
            var elements = this.getWidget().find('.ui-multiselect-checkboxes li');
            _.each(elements, function(element) {
                var width = this._getTextWidth($(element).find('label'));
                if (width > minimumWidth) {
                    minimumWidth = width;
                }
            }, this);
            this.getWidget().find('.ui-multiselect-checkboxes').addClass('fixed-li');
            return minimumWidth;
        },

        /**
         * Get element width
         *
         * @param {Object} element
         * @return {Integer}
         * @protected
         */
        _getTextWidth: function(element) {
            var htmlOrg = element.html();
            var htmlCalc = '<span>' + htmlOrg + '</span>';
            element.html(htmlCalc);
            var width = element.outerWidth();
            element.html(htmlOrg);
            return width;
        },

        /**
         * Get multiselect widget
         *
         * @return {Object}
         */
        getWidget: function() {
            return this.multiselect('widget');
        },

        /**
         * Proxy for multiselect method
         *
         * @param functionName
         * @return {Object}
         */
        multiselect: function(functionName) {
            return this.element.multiselect(functionName);
        },

        /**
         * Proxy for multiselectfilter method
         *
         * @param functionName
         * @return {Object}
         */
        multiselectfilter: function(functionName) {
            return this.element.multiselectfilter(functionName);
        },

        /**
         *  Set dropdown position according to button element
         */
        updateDropdownPosition: function() {
            this.multiselect('updatePos');
        }
    };

    return MultiselectDecorator;
});
