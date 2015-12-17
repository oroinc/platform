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
        inline: true
    }, config);

    var FilterHint;

    FilterHint = BaseView.extend(_.extend({}, FilterTemplate, {
        inline: config.inline,

        $filter: null,

        label: '',

        hint: '',

        templateSelector: '#filter-criteria-hint-template',

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
            var opts = _.pick(options || {}, '$filter', 'label', 'hint', 'templateSelector', 'templateTheme');
            _.extend(this, opts);

            this._defineTemplate();

            FilterHint.__super__.initialize.apply(this, arguments);
        },

        render: function($filter) {
            this.setElement(this.template({
                label: this.inline ? null : this.label
            }));

            if (this.inline) {
                $filter.find('.filter-criteria-hint-inline').append(this.$el);
            } else {
                $filter.closest('.filter-container').find('.filter-items-hint').append(this.$el);
            }

            this.update(this.hint);
        },

        /**
         * Show filter criteria
         *
         * @return {*}
         */
        update: function(hint) {
            this.$el.find('.filter-criteria-hint').html(_.escape(hint));
            if (!this.inline && hint === null) {
                this.$el.hide();
            } else {
                this.$el.show();
            }
            return this;
        },

        /**
         * Handles click on filter-item reset button
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
