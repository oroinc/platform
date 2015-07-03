/*jslint nomen:true*/
/*global define*/
define([
    'jquery',
    'underscore',
    'orotranslation/js/translator',
    'oroui/js/tools',
    'oro/filter/multiselect-filter'
], function ($, _, __, tools, MultiSelect) {
    'use strict';

    var MultiSelectOriginFolder;

    /**
     * Choice filter: filter type as option + filter value as string
     *
     * @export  oro/filter/choice-filter
     * @class   oro.filter.ChoiceFilter
     * @extends oro.filter.TextFilter
     */
    MultiSelectOriginFolder = MultiSelect.extend({
        /**
         * Template selector for filter criteria
         *
         * @property
         */
        templateSelector: '#multiselect-origin-folder-template',

        /**
        * Initialize.
        *
        * @param {Object} options
        */
        initialize: function (options) {
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
        render: function () {
            var options = this.choices;
            if (this.populateDefault) {
                options.unshift({value: '', label: this.placeholder});
            }

            this.setElement((
                this.template({
                    label: this.label,
                    showLabel: this.showLabel,
                    options: options,
                    placeholder: this.placeholder,
                    nullLink: this.nullLink,
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
