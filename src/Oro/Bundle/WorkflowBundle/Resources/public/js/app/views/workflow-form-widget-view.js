define(function(require) {
    'use strict';

    var WorkflowFormWidgetView;
    var BaseView = require('oroui/js/app/views/base/view');
    var $ = require('jquery');
    var widgetManager = require('oroui/js/widget-manager');
    var mediator = require('oroui/js/mediator');
    var performTransition = require('oroworkflow/js/transition-executor');
    var TransitionEventHandlers = require('oroworkflow/js/transition-event-handlers');

    /**
     * @export  oroworkflow/js/app/views/workflow-form-widget-view
     * @class   oro.WorkflowFormWidgetView
     * @extends Backbone.View
     */
    WorkflowFormWidgetView = BaseView.extend({
        optionNames: BaseView.prototype.optionNames.concat(['widgetAlias', 'saveAndTransitButtonSelector']),

        /**
         * @inheritDoc
         */
        constructor: function WorkflowFormWidgetView() {
            WorkflowFormWidgetView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function() {
            this._bindEvents();

            widgetManager.getWidgetInstanceByAlias(this.widgetAlias, this._bindWidgetEvents.bind(this));

            WorkflowFormWidgetView.__super__.initialize.apply(this, arguments);
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

            WorkflowFormWidgetView.__super__.dispose.apply(this, arguments);
        }
    });

    return WorkflowFormWidgetView;
});
