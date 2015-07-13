define(function(require) {
    'use strict';

    var EmailVariableComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var EmailVariableView = require('oroemail/js/app/views/email-variable-view');
    var EmailVariableModel = require('oroemail/js/app/models/email-variable-model');
    var DeleteConfirmation = require('oroui/js/delete-confirmation');
    var BaseComponent = require('oroui/js/app/components/base/component');

    EmailVariableComponent = BaseComponent.extend({
        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function(options) {
            var attributes;

            _.defaults(options, {model: {}, view: {}});

            // create model
            attributes = options.model.attributes;
            attributes = attributes ? JSON.parse(attributes) : {};
            this.model = new EmailVariableModel(attributes);
            this.model.setEntity(options.model.entityName, options.model.entityLabel);

            // create view
            options.view.el = options._sourceElement;
            options.view.model = this.model;
            this.view = new EmailVariableView(options.view);

            // bind entity change handler
            this.entityChoice = $(options.entityChoice);
            this.entityChoice.on('change.' + this.cid, _.bind(this.onEntityChange, this));

            this.view.render();
        },

        onEntityChange: function(e) {
            var view = this.view;
            var $el = $(e.currentTarget);
            var entityName = $el.val();
            var entityLabel = $el.find(':selected').data('label');

            if (!this.view.isEmpty()) {
                if (this.confirm) {
                    this.confirm.remove();
                }
                this.confirm = new DeleteConfirmation({
                    title: __('Change Entity Confirmation'),
                    okText: __('Yes'),
                    content: __('oro.email.emailtemplate.change_entity_confirmation')
                });
                this.confirm.on('ok', function() {
                    view.clear();
                });
                this.confirm.open();
            }
            this.model.setEntity(entityName, entityLabel);
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }
            if (this.confirm) {
                this.confirm.remove();
                delete this.confirm;
            }
            this.entityChoice.off('.' + this.cid);
            delete this.entityChoice;
            EmailVariableComponent.__super__.dispose.call(this);
        }
    });

    return EmailVariableComponent;
});
