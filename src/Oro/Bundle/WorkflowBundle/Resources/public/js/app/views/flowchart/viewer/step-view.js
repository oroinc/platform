define(function (require) {
    'use strict';

    var FlowchartJsPlumbBoxView = require('../jsplumb/box-view'),
        $ = require('jquery'),
        _ = require('underscore'),
        mediator = require('oroui/js/mediator'),
        __ = require('orotranslation/js/translator'),
        FlowchartViewerStepView;

    FlowchartViewerStepView = FlowchartJsPlumbBoxView.extend({
        template: require('tpl!oroworkflow/templates/flowchart/viewer/step.html'),

        className: function () {
            var classNames = [FlowchartViewerStepView.__super__.className.call(this)];
            classNames.push('workflow-step');
            if (this.model.get('_is_start')) {
                classNames.push('start-step');
            }
            if (this.model.get('is_final')) {
                classNames.push('final-step');
            }
            return classNames.join(' ');
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
            FlowchartViewerStepView.__super__.connect.apply(this, arguments);
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
                            __('Maximum connections ({{ maxConnections }}) reached', info),
                            e
                        );
                    }
                }
            ));
        }
    });

    return FlowchartViewerStepView;
});
