define([
    'jquery',
    'underscore',
    'oroui/js/app/views/base/view',
    'module',
    'orofilter/js/filter-template'
], function($, _, BaseView, module, FilterTemplate) {
    'use strict';

    var config = module.config();
    config = _.extend({
        inline: true,
        templateSelector: '#filter-hint-template',
        selectors: {
            filters: '.filter-container',
            itemHint: '.filter-item-hint',
            itemsHint: '.filter-items-hint',
            hint: '.filter-criteria-hint'
        }
    }, config);

    var FilterHint;

    FilterHint = BaseView.extend(_.extend({}, FilterTemplate, {
        /**
         * @property {String}
         */
        label: '',

        /**
         * @property {String}
         */
        hint: '',

        /**
         * @property {String}
         */
        templateSelector: config.templateSelector,

        /**
         * @property {Boolean}
         */
        inline: config.inline,

        /**
         * @property {Object}
         */
        selectors: config.selectors,

        /**
         * @property {Object}
         */
        events: {
            'click .reset-filter': '_onClickResetFilter'
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            var opts = _.pick(options || {}, 'label', 'hint', 'templateSelector', 'templateTheme', 'selectors');
            _.extend(this, opts);

            this._defineTemplate();

            FilterHint.__super__.initialize.apply(this, arguments);
        },

        /**
         * @param {jQuery} $filter
         */
        render: function($filter) {
            this.setElement(this.template({
                label: this.inline ? null : this.label
            }));

            if (this.inline) {
                $filter.find(this.selectors.itemHint).append(this.$el);
            } else {
                $filter.closest(this.selectors.filters).find(this.selectors.itemsHint).append(this.$el);
            }

            this.update(this.hint);
        },

        /**
         * @param {String|Null} hint
         * @returns {*}
         */
        update: function(hint) {
            this.$el.find(this.selectors.hint).html(_.escape(hint));
            if (!this.inline && hint === null) {
                this.$el.hide();
            } else {
                this.$el.show();
            }
            return this;
        },

        /**
         * Handles click on filter reset button
         *
         * @param {jQuery.Event} e
         * @private
         */
        _onClickResetFilter: function(e) {
            e.stopPropagation();
            this.trigger('reset');
        }
    }));

    return FilterHint;
});
