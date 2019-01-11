define(function(require) {
    'use strict';

    var validateTopmostLabelMixin;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var validationMessageHandlers = require('oroform/js/validation-message-handlers');

    /**
     * Mixin designed to make jquery validator error message visible over container element (e.g. dialog header)
     * @this validator
     */
    validateTopmostLabelMixin = {
        init: function() {
            var parentDialog = $(this.currentForm).closest('.ui-dialog');

            if (parentDialog.length === 1) {
                this.parentDialog = parentDialog;
                var dialogEvents = _.map(['dialogresize', 'dialogdrag', 'dialogreposition'], function(item) {
                    return item + '.validate';
                });
                parentDialog.on(dialogEvents.join(' '), validateTopmostLabelMixin.updateMessageHandlerViews.bind(this));
            }

            mediator.on('layout:reposition', validateTopmostLabelMixin.updateMessageHandlerViews, this);

            this.validationMessageHandlerViews = {};
        },

        showLabel: function(element, message, label) {
            if (_.has(this.validationMessageHandlerViews, element.name)) {
                this.validationMessageHandlerViews[element.name].dispose();
                delete this.validationMessageHandlerViews[element.name];
            }

            var Handler = _.find(validationMessageHandlers, function(item) {
                return item.test(element);
            });

            if (Handler) {
                this.validationMessageHandlerViews[element.name] = new Handler({
                    el: element,
                    label: label.length ? label : this.errorsFor(element)
                });
            }
        },

        validationSuccessHandler: function(element) {
            var name = element.getAttribute('name');

            if (this.validationMessageHandlerViews && name in this.validationMessageHandlerViews) {
                this.validationMessageHandlerViews[name].dispose();
                delete this.validationMessageHandlerViews[name];
            }
        },

        updateMessageHandlerViews: function() {
            _.invoke(this.validationMessageHandlerViews, 'update');
        },

        destroy: function() {
            if (this.parentDialog) {
                this.parentDialog.off('validate');
            }

            _.each(this.validationMessageHandlerViews, function(view) {
                view.dispose();
            });

            mediator.off('layout:reposition', validateTopmostLabelMixin.updateMessageHandlerViews, this);

            delete this.validationMessageHandlerViews;
        }
    };

    return validateTopmostLabelMixin;
});
