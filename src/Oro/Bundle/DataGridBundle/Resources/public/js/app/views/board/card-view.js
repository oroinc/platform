define(function(require) {
    'use strict';

    /**
     * Renders generic card with support of following features:
     * - transition status tracking
     * - scrolls itself into view when transition status becomes `error`
     *
     * @param {Boolean} readonly - true if card should be draggable
     * @param {Number} earlyTransitionStatusChangeTimeout - timeout for early transition status change detection
     * @augments {BaseView}
     */
    var CardView;
    var BaseView = require('oroui/js/app/views/base/view');
    var numberFormatter = require('orolocale/js/formatter/number');
    var datetimeFormatter = require('orolocale/js/formatter/datetime');
    var scrollHelper = require('oroui/js/tools/scroll-helper');

    CardView = BaseView.extend({
        /**
         * @inheritDoc
         */
        className: 'card-view',

        /**
         * @inheritDoc
         */
        template: require('tpl!../../../../templates/board/card-view.html'),

        /**
         * @inheritDoc
         */
        keepElement: false,

        /**
         * Timeout for early transition status change detection
         *
         * @param {Number}
         */
        earlyTransitionStatusChangeTimeout: 2000,

        /**
         * @inheritDoc
         */
        events: {
            'click [data-action="navigate"]': 'navigate'
        },

        /**
         * @inheritDoc
         */
        listen: {
            'change model': 'render'
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            CardView.__super__.initialize.call(this, options);
            this.readonly = options.readonly;
            if (options.earlyTransitionStatusChangeTimeout) {
                this.earlyTransitionStatusChangeTimeout = options.earlyTransitionStatusChangeTimeout;
            }
        },

        /**
         * @inheritDoc
         */
        getTemplateData: function() {
            var templateData = CardView.__super__.getTemplateData.call(this, arguments);
            templateData.numberFormatter = numberFormatter;
            templateData.datetimeFormatter = datetimeFormatter;
            templateData.readonly = this.readonly;
            return templateData;
        },

        /**
         * @inheritDoc
         */
        render: function() {
            CardView.__super__.render.call(this);
            this.$el.attr({
                'data-transition-status': this.model.get('transitionStatus'),
                'data-readonly': this.readonly
            });
            this.trackErrorTransitionStatus();
            this.trackEarlyTransitionStatusChange();
            if (!this.model) {
                this.$el.attr('data-non-valid', '');
            } else {
                this.$el.removeAttr('data-non-valid');
                this.$el.data({model: this.model});
            }
        },

        /**
         * Scrolls this card into view when transition status becomes `error`
         */
        trackErrorTransitionStatus: function() {
            if (this.model.get('transitionStatus') === 'error') {
                if (!this.scrolledToErrorCard) {
                    scrollHelper.scrollIntoView(this.el, void 0, 20, 20);
                    this.scrolledToErrorCard = true;
                }
            } else {
                delete this.scrolledToErrorCard;
            }
        },

        /**
         * Tracks `transition-status-just-changed` class
         */
        trackEarlyTransitionStatusChange: function() {
            var $el = this.$el;
            $el.removeClass('transition-status-just-changed');
            clearTimeout(this.transitionStatusUpdateTimeout);
            if (!this.model.get('transitionStatusUpdateTime')) {
                return;
            }
            var now = (new Date()).getTime();
            var transitionStatusUpdateTime = this.model.get('transitionStatusUpdateTime').getTime();
            var statusChangeTimeout = this.earlyTransitionStatusChangeTimeout - (now - transitionStatusUpdateTime);
            if (statusChangeTimeout > 0) {
                $el.addClass('transition-status-just-changed');
                this.transitionStatusUpdateTimeout = setTimeout(function() {
                    $el.removeClass('transition-status-just-changed');
                }, statusChangeTimeout);
            }
        },

        /**
         * Generic navigation action handler
         *
         * @param {jQuery.Event} e
         */
        navigate: function(e) {
            e.preventDefault();
            this.trigger('navigate', this.model, {doExecute: true});
        }
    });

    return CardView;
});
