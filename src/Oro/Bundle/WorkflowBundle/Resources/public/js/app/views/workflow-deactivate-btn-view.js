define(function(require) {
    'use strict';

    var WorkflowDeactivateBtnView;
    var BaseView = require('oroui/js/app/views/base/view');
    var mediator = require('oroui/js/mediator');
    var messenger = require('oroui/js/messenger');
    var $ = require('jquery');
    var _ = require('underscore');
    var deactivationHandler = require('oroworkflow/js/deactivation-handler');

    WorkflowDeactivateBtnView = BaseView.extend({
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
         * @inheritDoc
         */
        constructor: function WorkflowDeactivateBtnView() {
            WorkflowDeactivateBtnView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            WorkflowDeactivateBtnView.__super__.initialize.apply(this, arguments);

            this.options = $.extend(true, {}, this.options, _.pick(options, _.keys(this.options)));
        },

        delegateEvents: function() {
            WorkflowDeactivateBtnView.__super__.delegateEvents.apply(this, arguments);
            this.$el.on('click' + this.eventNamespace(), this.options.selectors.button, $.proxy(this.onClick, this));
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
            var el = this.$el.find(this.options.selectors.button);
            deactivationHandler.call(el, el.prop('href'), el.data('label'));
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$el.off('click' + this.eventNamespace());
            this.$el.off('deactivation_success');

            WorkflowDeactivateBtnView.__super__.dispose.apply(this, arguments);
        }
    });

    return WorkflowDeactivateBtnView;
});
