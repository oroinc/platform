define(function(require) {
    'use strict';

    var WorkflowActivateBtnView;
    var BaseView = require('oroui/js/app/views/base/view');
    var mediator = require('oroui/js/mediator');
    var messenger = require('oroui/js/messenger');
    var $ = require('jquery');
    var _ = require('underscore');
    var activationHandler = require('oroworkflow/js/activation-handler');

    WorkflowActivateBtnView = BaseView.extend({
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
         * @inheritDoc
         */
        constructor: function WorkflowActivateBtnView() {
            WorkflowActivateBtnView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            WorkflowActivateBtnView.__super__.initialize.apply(this, arguments);

            this.options = $.extend(true, {}, this.options, _.pick(options, _.keys(this.options)));
        },

        delegateEvents: function() {
            WorkflowActivateBtnView.__super__.delegateEvents.apply(this, arguments);
            this.$el.on('click' + this.eventNamespace(), this.options.selectors.button, $.proxy(this.onClick, this));
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
            var el = this.$el.find(this.options.selectors.button);
            activationHandler.call(el, el.prop('href'), el.data('name'), el.data('label'));
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$el.off('click' + this.eventNamespace());
            this.$el.off('activation_success');

            WorkflowActivateBtnView.__super__.dispose.apply(this, arguments);
        }
    });

    return WorkflowActivateBtnView;
});
