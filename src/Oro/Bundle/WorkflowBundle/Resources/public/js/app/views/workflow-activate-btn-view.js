define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const mediator = require('oroui/js/mediator');
    const messenger = require('oroui/js/messenger');
    const $ = require('jquery');
    const _ = require('underscore');
    const activationHandler = require('oroworkflow/js/activation-handler');

    const WorkflowActivateBtnView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
            selectors: {
                button: null
            }
        },

        $el: null,

        /**
         * @inheritdoc
         */
        constructor: function WorkflowActivateBtnView(options) {
            WorkflowActivateBtnView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            WorkflowActivateBtnView.__super__.initialize.call(this, options);

            this.options = $.extend(true, {}, this.options, _.pick(options, _.keys(this.options)));
        },

        delegateEvents: function(events) {
            WorkflowActivateBtnView.__super__.delegateEvents.call(this, events);
            this.$el.on('click' + this.eventNamespace(), this.options.selectors.button, this.onClick.bind(this));
            this.$el.on({
                activation_success: this.onActivationSuccess
            }, this.options.selectors.button);
        },

        /**
         * @param {jQuery.Event} e
         * @param {Object} response
         */
        onActivationSuccess: function(e, response) {
            mediator.once('page:afterChange', function() {
                messenger.notificationFlashMessage('success', response.message);

                if (response.deactivatedMessage) {
                    messenger.notificationFlashMessage('info', response.deactivatedMessage);
                }
            });
            mediator.execute('refreshPage');
        },

        /**
         * @param {jQuery.Event} e
         */
        onClick: function(e) {
            e.preventDefault();
            const el = this.$el.find(this.options.selectors.button);
            activationHandler.call(el, el.attr('href'), el.data('name'), el.data('label'));
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$el.off('click' + this.eventNamespace());
            this.$el.off('activation_success');

            WorkflowActivateBtnView.__super__.dispose.call(this);
        }
    });

    return WorkflowActivateBtnView;
});
