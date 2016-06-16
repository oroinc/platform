/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var DeactivateFormWidgetComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var Error = require('oroui/js/error');
    var mediator = require('oroui/js/mediator');
    var routing = require('routing');
    var widgetManager = require('oroui/js/widget-manager');

    DeactivateFormWidgetComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            _wid: '',
            deactivated: '',
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
                    if (_.isNull(self.options.deactivated)) {
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

        /**
         * @param {jQuery.Event} e
         */
        onClick: function(e) {
            var self = this;

            $.ajax({
                url: routing.generate('oro_api_workflow_activate', {workflowDefinition: this.options.workflow}),
                type: 'GET',
                success: function() {
                    var $form = self.options._sourceElement.find(self.options.selectors.form);

                    $form.trigger('submit');
                },
                error: function(xhr, textStatus, error) {
                    Error.handle({}, xhr, {enforce: true});
                }
            });
        }
    });

    return DeactivateFormWidgetComponent;
});
