define([
    'jquery',
    'underscore',
    'orotranslation/js/translator',
    'oro/filter/multiselect-filter'
], function($, _, __, MultiSelect) {
    'use strict';

    const MultiSelectOriginFolder = MultiSelect.extend({
        /**
         * Template selector for filter criteria
         *
         * @property
         */
        templateSelector: '#multiselect-origin-folder-template',

        widgetOptions: {
            ...MultiSelect.prototype.widgetOptions,
            maxItemsForShowSearchBar: 0
        },

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

        getTemplateData() {
            const options = this.choices;
            if (this.populateDefault) {
                options.unshift({value: '', label: this.placeholder});
            }

            return {
                label: this.labelPrefix + this.label,
                showLabel: this.showLabel,
                options: options,
                placeholder: this.placeholder,
                selected: _.extend({}, this.emptyValue, this.value),
                isEmpty: this.isEmpty()
            };
        }
    });

    return MultiSelectOriginFolder;
});
