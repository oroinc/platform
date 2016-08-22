/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var DeactivateFormWidgetComponent;
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var mediator = require('oroui/js/mediator');
    var widgetManager = require('oroui/js/widget-manager');

    DeactivateFormWidgetComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            _wid: '',
            success: false,
            deactivated: null,
            workflow: '',
            selectors: {
                form: null
            },
            buttonName: ''
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            var self = this;

            widgetManager.getWidgetInstance(
                this.options._wid,
                function(widget) {
                    if (!self.options.success) {
                        widget.getAction(self.options.buttonName, 'adopted', function(action) {
                            action.on('click', _.bind(self.onClick, self));
                        });
                    } else {
                        mediator.trigger('widget_success:' + widget.getAlias());
                        mediator.trigger('widget_success:' + widget.getWid());

                        var response = {message: __('oro.workflow.activated')};

                        if (!_.isEmpty(self.options.deactivated)) {
                            response = _.extend(response, {
                                deactivatedMessage: __('oro.workflow.deactivated_list') + self.options.deactivated
                            });
                        }

                        widget.trigger('formSave', response);
                        widget.remove();
                    }
                }
            );
        },

        onClick: function() {
            this.options._sourceElement.find(this.options.selectors.form).trigger('submit');
        }
    });

    return DeactivateFormWidgetComponent;
});
