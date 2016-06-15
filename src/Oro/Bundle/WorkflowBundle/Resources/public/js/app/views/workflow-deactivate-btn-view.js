/*jslint nomen:true*/
/*global define*/
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

        $el: null,

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
        },

        /**
         * @param {jQuery.Event} e
         */
        onClick: function(e) {
            e.preventDefault();

            var el = this.$el.find(this.options.selectors.button);

            el.on('deactivation_success', function(e, response) {
                mediator.once('page:afterChange', function() {
                    messenger.notificationFlashMessage('success', response.message);
                });
                mediator.execute('refreshPage');
            });

            deactivationHandler.call(el, el.prop('href'));
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$el.off('click' + this.eventNamespace());

            WorkflowDeactivateBtnView.__super__.dispose.apply(this, arguments);
        }
    });

    return WorkflowDeactivateBtnView;
});
