define([
    'jquery',
    'underscore',
    'orotranslation/js/translator',
    'oroui/js/tools',
    'oro/filter/multiselect-filter'
], function($, _, __, tools, MultiSelect) {
    'use strict';

    const FILTER_EMPTY_VALUE = '';

    const MultiSelectOriginFolder = MultiSelect.extend({
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
         * @inheritdoc
         */
        constructor: function MultiSelectOriginFolder(options) {
            MultiSelectOriginFolder.__super__.constructor.call(this, options);
        },

        /**
        * Initialize.
        *
        * @param {Object} options
        */
        initialize: function(options) {
            if (_.isUndefined(this.choices)) {
                this.choices = [];
            }
            const choices = this.choices;

            MultiSelect.__super__.initialize.call(this, options);
            this.choices = choices;
        },

        /**
         * Render filter template
         *
         * @return {*}
         */
        render: function() {
            const options = this.choices;
            if (this.populateDefault) {
                options.unshift({value: '', label: this.placeholder});
            }

            this.setElement((
                this.template({
                    label: this.labelPrefix + this.label,
                    showLabel: this.showLabel,
                    options: options,
                    placeholder: this.placeholder,
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
