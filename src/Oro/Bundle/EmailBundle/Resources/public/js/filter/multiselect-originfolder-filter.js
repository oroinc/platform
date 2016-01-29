/*jslint nomen:true*/
/*global define*/
define([
    'jquery',
    'underscore',
    'orotranslation/js/translator',
    'oroui/js/tools',
    'oro/filter/multiselect-filter'
], function($, _, __, tools, MultiSelect) {
    'use strict';

    var MultiSelectOriginFolder;

    // @const
    var FILTER_EMPTY_VALUE = '';

    MultiSelectOriginFolder = MultiSelect.extend({
        /**
         * Template selector for filter criteria
         *
         * @property
         */
        templateSelector: '#multiselect-origin-folder-template',

        /**
         * Selector for widget button
         *
         * @property
         */
        buttonSelector: '.filter-criteria-selector',

        /**
         * Minimal width of dropdown
         *
         * @private
         */
        minimumDropdownWidth: 150,

        /**
         * Select widget options
         *
         * @property
         */
        widgetOptions: {
            multiple: true,
            classes: 'select-filter-widget multiselect-filter-widget multiselect-origin-folder'
        },

        emptyValue: {value: [FILTER_EMPTY_VALUE]},

        /**
        * Initialize.
        *
        * @param {Object} options
        */
        initialize: function(options) {
            if (_.isUndefined(this.choices)) {
                this.choices = [];
            }
            var choices = this.choices;

            MultiSelect.__super__.initialize.apply(this, arguments);
            this.choices = choices;
        },

        /**
         * Render filter template
         *
         * @return {*}
         */
        render: function() {
            var options = this.choices;
            if (this.populateDefault) {
                options.unshift({value: '', label: this.placeholder});
            }

            this.setElement((
                this.template({
                    label: this.labelPrefix + this.label,
                    showLabel: this.showLabel,
                    options: options,
                    placeholder: this.placeholder,
                    canDisable: this.canDisable,
                    selected: _.extend({}, this.emptyValue, this.value),
                    isEmpty: this.isEmpty()
                })
            ));

            this._initializeSelectWidget();

            return this;
        }
    });

    return MultiSelectOriginFolder;
});
