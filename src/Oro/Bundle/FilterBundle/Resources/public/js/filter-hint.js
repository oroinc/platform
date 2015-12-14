define([
    'jquery',
    'underscore',
    'orotranslation/js/translator',
    'oroui/js/app/views/base/view',
    'oroui/js/tools'
], function($, _, __, BaseView, tools) {
    'use strict';

    var FilterHint;

    /**
     *
     * @export  oro/filter/abstract-filter
     * @class   oro.filter.AbstractFilter
     * @extends Backbone.View
     */
    FilterHint = BaseView.extend({

        template: '',

        templateSelector: '#filter-criteria-template',

        hint: '',

        label: '',

        /**
         * View events
         *
         * @property {Object}
         */
        events: {
            'click .reset-filter': '_onClickResetFilter'
        },

        /**
         * Initialize.
         *
         * @param {Object} options
         * @param {Boolean} [options.enabled]
         */
        initialize: function(options) {
            var opts = _.pick(options || {}, 'hint', 'label', 'templateSelector', 'templateTheme');
            _.extend(this, opts);

            this._defineTemplate();
            if (this.hint === null) {
                this.hide();
            }

            FilterHint.__super__.initialize.apply(this, arguments);
        },


        /**
         * Show filter criteria
         *
         * @return {*}
         */
        show: function() {
            this.$el.css('display', 'inline-block');
            return this;
        },

        /**
         * Hide filter criteria
         *
         * @return {*}
         */
        hide: function() {
            this.$el.hide();
            return this;
        },

        /**
         * Defines which template to use
         *
         * @private
         */
        _defineTemplate: function() {
            this.template = this._getTemplate(this.templateSelector);
        },

        _getTemplate: function(selector) {
            var src = $(selector).text();

            return _.template(src)({
                criteriaHint: this.hint,
                label: this.label
            });
        },

        _onClickResetFilter: function(e) {
            e.stopImmediatePropagation();
            this.trigger('reset');
        }
    });

    return FilterHint;
});
