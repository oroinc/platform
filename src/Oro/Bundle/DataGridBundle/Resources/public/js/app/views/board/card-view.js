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
    const BaseView = require('oroui/js/app/views/base/view');
    const numberFormatter = require('orolocale/js/formatter/number');
    const datetimeFormatter = require('orolocale/js/formatter/datetime');
    const scrollHelper = require('oroui/js/tools/scroll-helper');
    const ActionsView = require('../grid/actions-view');

    const CardView = BaseView.extend({
        /**
         * @inheritdoc
         */
        className: 'card-view',

        /**
         * Selector for element where to put card actions dropdown
         *
         * @type {String}
         */
        cardActionsElementSelector: '[data-placeholder-for="actions"]',

        /**
         * @inheritdoc
         */
        template: require('tpl-loader!../../../../templates/board/card-view.html'),

        /**
         * @inheritdoc
         */
        keepElement: false,

        /**
         * Timeout for early transition status change detection
         *
         * @type {Number}
         */
        earlyTransitionStatusChangeTimeout: 2000,

        /**
         * @inheritdoc
         */
        events: {
            'click [data-action="navigate"]': 'navigate'
        },

        /**
         * @inheritdoc
         */
        listen: {
            'change model': 'render'
        },

        /**
         * @inheritdoc
         */
        constructor: function CardView(options) {
            CardView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
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
         * @inheritdoc
         */
        getTemplateData: function() {
            const templateData = CardView.__super__.getTemplateData.call(this);
            templateData.numberFormatter = numberFormatter;
            templateData.datetimeFormatter = datetimeFormatter;
            templateData.readonly = this.readonly;
            return templateData;
        },

        /**
         * @inheritdoc
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
            const $el = this.$el;
            $el.removeClass('transition-status-just-changed');
            clearTimeout(this.transitionStatusUpdateTimeout);
            if (!this.model.get('transitionStatusUpdateTime')) {
                return;
            }
            const now = (new Date()).getTime();
            const transitionStatusUpdateTime = this.model.get('transitionStatusUpdateTime').getTime();
            const statusChangeTimeout = this.earlyTransitionStatusChangeTimeout - (now - transitionStatusUpdateTime);
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
            const $placeholder = this.$(selector);
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
