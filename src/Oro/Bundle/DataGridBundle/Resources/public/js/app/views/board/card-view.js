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
    var ActionsView = require('../grid/actions-view');

    CardView = BaseView.extend({
        /**
         * @inheritDoc
         */
        className: 'card-view',

        /**
         * Selector for element where to put card actions dropdown
         *
         * @type {String}
         */
        cardActionsElementSelector: '[data-placeholder-for="actions"]',

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
         * @type {Number}
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
            this.datagrid = options.datagrid;
            this.actions = options.actions;
            this.actionOptions = options.actionOptions;
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
            this.renderCardActionsDropdown(this.cardActionsElementSelector);
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
        },

        /**
         * Renders card actions dropdown
         */
        renderCardActionsDropdown: function(selector) {
            var $placeholder = this.$(selector);
            if (!$placeholder.length) {
                return;
            }
            if (!this.cardActionsView) {
                this.cardActionsView = new ActionsView({
                    el: $placeholder,
                    actions: this.actions,
                    model: this.model,
                    actionConfiguration: this.model.get('action_configuration'),
                    datagrid: this.datagrid,
                    actionOptions: this.actionOptions,
                    showCloseButton: false // have no idea how to reuse value from actions cell
                });
            } else {
                this.cardActionsView.setElement($placeholder);
            }
            this.cardActionsView.render();
        }
    });

    return CardView;
});
