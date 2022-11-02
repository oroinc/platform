define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const $ = require('jquery');
    const widgetManager = require('oroui/js/widget-manager');
    const mediator = require('oroui/js/mediator');
    const performTransition = require('oroworkflow/js/transition-executor');
    const TransitionEventHandlers = require('oroworkflow/js/transition-event-handlers');

    /**
     * @export  oroworkflow/js/app/views/workflow-form-widget-view
     * @class   oro.WorkflowFormWidgetView
     * @extends Backbone.View
     */
    const WorkflowFormWidgetView = BaseView.extend({
        optionNames: BaseView.prototype.optionNames.concat(['widgetAlias', 'saveAndTransitButtonSelector']),

        /**
         * @inheritdoc
         */
        constructor: function WorkflowFormWidgetView(options) {
            WorkflowFormWidgetView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this._bindEvents();

            widgetManager.getWidgetInstanceByAlias(this.widgetAlias, this._bindWidgetEvents.bind(this));

            WorkflowFormWidgetView.__super__.initialize.call(this, options);
        },

        onSaveAndTransit: function(e) {
            e.preventDefault();

            widgetManager.getWidgetInstanceByAlias(this.widgetAlias, function(widget) {
                widget.form.submit();
            });
        },

        _bindEvents: function() {
            $(this.saveAndTransitButtonSelector).on('click' + this.eventNamespace(), this.onSaveAndTransit.bind(this));
        },

        _unbindEvents: function() {
            $(this.saveAndTransitButtonSelector).off(this.eventNamespace());
        },

        _bindWidgetEvents: function(widget) {
            widget.on({
                beforeContentLoad: function() {
                    mediator.execute('showLoading');
                },
                formSave: function(data) {
                    performTransition($(this.saveAndTransitButtonSelector), data);
                },
                formSaveError: function() {
                    mediator.execute('hideLoading');
                },
                transitionSuccess: function(response) {
                    TransitionEventHandlers.getOnSuccess($('<div>'))(response);
                },
                transitionFailure: function(responseCode) {
                    TransitionEventHandlers.getOnFailure($('<div>'))({status: responseCode}, '', '');
                }
            });
        },

        dispose: function() {
            this._unbindEvents();

            WorkflowFormWidgetView.__super__.dispose.call(this);
        }
    });

    return WorkflowFormWidgetView;
});
