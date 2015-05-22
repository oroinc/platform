define(function (require) {
    'use strict';
    var FlowchartViewerStepView = require('../viewer/step-view'),
        mediator = require('oroui/js/mediator'),
        $ = require('jquery'),
        _ = require('underscore'),
        __ = require('translator'),
        FlowchartEditorStepView;

    FlowchartEditorStepView = FlowchartViewerStepView.extend({
        template: require('tpl!oroworkflow/templates/flowchart/editor/step.html'),

        events: {
            'dblclick': 'triggerEditStep',
            'click .jsplumb-source': 'triggerAddStep',
            'click .workflow-step-edit': 'triggerEditStep',
            'click .workflow-step-clone': 'triggerCloneStep',
            'click .workflow-step-delete': 'triggerRemoveStep'
        },

        targetDefaults: {
            dropOptions: {hoverClass: 'dragHover'},
            anchor: 'Continuous',
            allowLoopback: true
        },

        sourceDefaults: {
            filter: '.jsplumb-source',
            anchor: 'Continuous',
            connector: ['StateMachine', {curviness: 20}],
            maxConnections: 100
        },

        connect: function () {
            FlowchartEditorStepView.__super__.connect.apply(this, arguments);
            var instance = this.areaView.jsPlumbInstance;

            instance.batch(_.bind(function () {
                // add element as source to jsPlumb
                if (this.model.get('draggable') !== false) {
                    instance.draggable(this.$el, {
                        containment: 'parent',
                        stop: _.bind(function (e) {
                            // update model position when dragging stops
                            this.model.set({position: e.pos});
                        }, this)
                    });
                }
                this.makeTarget();
                this.makeSource();
            }, this));
        },

        makeTarget: function () {
            var instance = this.areaView.jsPlumbInstance;
            instance.makeTarget(this.$el, $.extend(true, {}, _.result(this, 'targetDefaults')));
        },

        makeSource: function () {
            var instance = this.areaView.jsPlumbInstance;
            instance.makeSource(this.$el, $.extend(true,
                {},
                _.result(this, 'sourceDefaults'),
                {
                    onMaxConnections: function (info, e) {
                        mediator.execute(
                            'showErrorMessage',
                            __('Maximum connections') + ' (' + info.maxConnections + ') ' + __('reached'),
                            e
                        );
                    }
                }
            ));
        },

        triggerEditStep: function (e) {
            e.preventDefault();
            this.areaView.model.trigger('requestEditStep', this.model);
        },

        triggerRemoveStep: function (e) {
            e.preventDefault();
            this.areaView.model.trigger('requestRemoveStep', this.model);
        },

        triggerCloneStep: function (e) {
            e.preventDefault();
            this.areaView.model.trigger('requestCloneStep', this.model);
        },

        triggerAddStep: function () {
            this.areaView.model.trigger('requestAddTransition', this.model);
        }
    });

    return FlowchartEditorStepView;
});
