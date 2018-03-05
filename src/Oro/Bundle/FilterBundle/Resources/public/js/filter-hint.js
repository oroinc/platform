define(function(require) {
    'use strict';

    var template = require('tpl!orofilter/templates/filter/filter-hint.html');
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');
    var module = require('module');
    var FilterTemplate = require('orofilter/js/filter-template');
    var config = module.config();
    config = _.extend({
        inline: true,
        selectors: {
            filters: '.filter-container',
            itemHint: '.filter-item-hint',
            itemsHint: '.filter-items-hint',
            hint: '.filter-criteria-hint',
            reset: '.reset-filter-button'
        }
    }, config);

    var FilterHint;

    FilterHint = BaseView.extend(_.extend({}, FilterTemplate, {
        /**
         * @property
         */
        filter: null,

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
        template: template,

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
        constructor: function FilterHint() {
            FilterHint.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            var opts = _.pick(options || {}, 'filter');
            _.extend(this, opts);

            this.templateTheme = this.filter.templateTheme;
            this.label = this.filter.label;
            this.hint = this.filter._getCriteriaHint();

            this._defineTemplate();

            FilterHint.__super__.initialize.apply(this, arguments);
        },

        render: function() {
            this.setElement(this.template({
                label: this.inline ? null : this.label,
                allowClear: this.filter.allowClear
            }));

            if (this.filter.selectWidget) {
                this.filter.selectWidget.multiselect('getButton').hide();
            }

            if (this.inline) {
                this.filter.$el.find(this.selectors.itemHint).append(this.$el);
            } else {
                this.filter.$el.closest(this.selectors.filters).find(this.selectors.itemsHint)
                    .find(this.selectors.reset).before(this.$el);
            }

            this.visible = true;

            this.update(this.hint);
        },

        /**
         * @param {String|Null} hint
         * @returns {*}
         */
        update: function(hint) {
            this.$el.find(this.selectors.hint).html(_.escape(hint));
            if (!this.inline && hint === null) {
                if (this.visible) {
                    this.$el.hide();
                    this.visible = false;
                }
            } else {
                if (!this.visible) {
                    this.$el.show();
                    this.visible = true;
                }
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
