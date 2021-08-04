define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const mediator = require('oroui/js/mediator');
    const messenger = require('oroui/js/messenger');
    const $ = require('jquery');
    const _ = require('underscore');
    const deactivationHandler = require('oroworkflow/js/deactivation-handler');

    const WorkflowDeactivateBtnView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
            selectors: {
                button: null
            }
        },

        /**
         * @property {jQuery.Element}
         */
        $el: null,
        /**
         * @inheritdoc
         */
        constructor: function WorkflowDeactivateBtnView(options) {
            WorkflowDeactivateBtnView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            WorkflowDeactivateBtnView.__super__.initialize.call(this, options);

            this.options = $.extend(true, {}, this.options, _.pick(options, _.keys(this.options)));
        },

        delegateEvents: function(events) {
            WorkflowDeactivateBtnView.__super__.delegateEvents.call(this, events);
            this.$el.on('click' + this.eventNamespace(), this.options.selectors.button, this.onClick.bind(this));
            this.$el.on({
                deactivation_success: this.onDeactivationSuccess
            }, this.options.selectors.button);
        },

        /**
         * @param {jQuery.Event} e
         * @param {Object} response
         */
        onDeactivationSuccess: function(e, response) {
            mediator.once('page:afterChange', function() {
                messenger.notificationFlashMessage('success', response.message);
            });
            mediator.execute('refreshPage');
        },

        /**
         * @param {jQuery.Event} e
         */
        onClick: function(e) {
            e.preventDefault();
            const el = this.$el.find(this.options.selectors.button);
            deactivationHandler.call(el, el.attr('href'), el.data('label'));
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$el.off('click' + this.eventNamespace());
            this.$el.off('deactivation_success');

            WorkflowDeactivateBtnView.__super__.dispose.call(this);
        }
    });

    return WorkflowDeactivateBtnView;
});
