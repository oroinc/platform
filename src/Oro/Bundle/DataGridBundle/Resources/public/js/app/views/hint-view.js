define(function(require) {
    'use strict';

    var HintView;
    var BaseView = require('oroui/js/app/views/base/view');
    var hintTemplate = require('tpl-loader!orodatagrid/templates/hint/hint-view-template.html');
    var _ = require('underscore');
    var $ = require('jquery');

    var popoverConfig = {
        content: '',
        trigger: 'manual',
        placement: 'bottom',
        animation: false,
        container: 'body',
        template: hintTemplate()
    };

    HintView = BaseView.extend({
        /**
         * @inheritDoc
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
         * @inheritDoc
         */
        constructor: function HintView() {
            HintView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            var whiteList = ['offsetOfEl', 'textEl', 'popoverConfig'];

            _.extend(this, _.pick(options, whiteList));

            this.$el.popover(_.extend(
                {
                    offset: this.calcPopoverOffset()
                },
                popoverConfig,
                this.popoverConfig
            ));

            HintView.__super__.initialize.apply(this, arguments);
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
            var textEl = this.$(this.textEl).get(0) || null;

            return textEl ? (textEl.scrollWidth <= textEl.offsetWidth) : true;
        },

        /**
         * Calculation offset of column label for popover
         *
         * @return {String}
         */
        calcPopoverOffset: function() {
            var x = 0;
            var y = 0;
            var $offsetOfEl = $(this.offsetOfEl);

            if ($offsetOfEl.length) {
                y = $offsetOfEl[0].getBoundingClientRect().bottom - this.$el[0].getBoundingClientRect().bottom;
            }

            return [x, y].join(', ');
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$el.popover('hide');
            this.$el.popover('dispose');
            HintView.__super__.dispose.apply(this, arguments);
        }
    });

    return HintView;
});
