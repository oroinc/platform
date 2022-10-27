define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const hintTemplate = require('tpl-loader!orodatagrid/templates/hint/hint-view-template.html');
    const _ = require('underscore');
    const $ = require('jquery');

    const popoverConfig = {
        content: '',
        trigger: 'manual',
        placement: 'bottom',
        animation: false,
        container: 'body',
        template: hintTemplate()
    };

    const HintView = BaseView.extend({
        /**
         * @inheritdoc
         */
        noWrap: true,

        /**
         * Some closest element which will be using at calculation for popover offset
         */
        offsetOfEl: null,

        /**
         * {string}
         */
        textEl: '[data-grid-header-cell-text]',

        /**
         * @inheritdoc
         */
        constructor: function HintView(options) {
            HintView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            const whiteList = ['offsetOfEl', 'textEl', 'popoverConfig'];

            _.extend(this, _.pick(options, whiteList));

            this.$el.popover(_.extend(
                {
                    offset: this.calcPopoverOffset()
                },
                popoverConfig,
                this.popoverConfig
            ));

            HintView.__super__.initialize.call(this, options);
        },

        /**
         * Show Bootstrap popover
         */
        show: function() {
            if (this.disposed) {
                return;
            }

            this.$el.popover('show');
        },

        /**
         * Hide Bootstrap popover
         */
        hide: function() {
            if (this.disposed) {
                return;
            }

            this.$el.popover('hide');
        },

        /**
         * Check if content overflow container
         * @returns {boolean}
         */
        fullLabelIsVisible: function() {
            const textEl = this.$(this.textEl).get(0) || null;

            return textEl ? (textEl.scrollWidth <= textEl.offsetWidth) : true;
        },

        /**
         * Calculation offset of column label for popover
         *
         * @return {String}
         */
        calcPopoverOffset: function() {
            const x = 0;
            let y = 0;
            const $offsetOfEl = $(this.offsetOfEl);

            if ($offsetOfEl.length) {
                y = $offsetOfEl[0].getBoundingClientRect().bottom - this.$el[0].getBoundingClientRect().bottom;
            }

            return [x, y].join(', ');
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$el.popover('hide');
            this.$el.popover('dispose');
            HintView.__super__.dispose.call(this);
        }
    });

    return HintView;
});
