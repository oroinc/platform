import BaseView from 'oroui/js/app/views/base/view';
import $ from 'jquery';
import widgetManager from 'oroui/js/widget-manager';
import mediator from 'oroui/js/mediator';
import performTransition from 'oroworkflow/js/transition-executor';
import TransitionEventHandlers from 'oroworkflow/js/transition-event-handlers';

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
            widget.form.trigger('submit');
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

export default WorkflowFormWidgetView;
