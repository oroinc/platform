define(function(require) {
    'use strict';

    const FlowchartJsPlumbBoxView = require('../jsplumb/box-view');
    const $ = require('jquery');
    const _ = require('underscore');
    const mediator = require('oroui/js/mediator');
    const __ = require('orotranslation/js/translator');

    const FlowchartViewerStepView = FlowchartJsPlumbBoxView.extend({
        template: require('tpl-loader!oroworkflow/templates/flowchart/viewer/step.html'),

        jsPlumbSource: null,

        jsPlumbTarget: null,

        className: function() {
            const classNames = [FlowchartViewerStepView.__super__.className.call(this)];
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
            allowLoopback: true
        },

        sourceDefaults: {
            filter: '.jsplumb-source',
            connector: ['Smartline', {cornerRadius: 3, midpoint: 0.5}],
            maxConnections: 100
        },

        /**
         * @inheritdoc
         */
        constructor: function FlowchartViewerStepView(options) {
            FlowchartViewerStepView.__super__.constructor.call(this, options);
        },

        updateStepMinWidth: function() {
            const STEP_MAX_WIDTH = 180; // that's taken from css .workflow-flowchart .workflow-step 'max-width' definition
            const currentId = this.el.id;
            const connections = this.jsPlumbSource.getConnections();
            const count = _.countBy(connections, function(connection) {
                return connection.sourceId === currentId ? 'out' : (connection.targetId === currentId ? 'in' : 'other');
            });
            const newWidth = (Math.max(count.in ? count.in : 0, count.out ? count.out : 0) + 1) *
                this.areaView.connectionWidth;
            this.$el.css({
                minWidth: newWidth
            });
            if (newWidth > STEP_MAX_WIDTH) {
                this.$el.css({
                    maxWidth: newWidth
                });
            } else {
                this.$el.css({
                    maxWidth: ''
                });
            }
        },

        connect: function() {
            FlowchartViewerStepView.__super__.connect.call(this);
            this.makeTarget();
            this.makeSource();

            // must update offsets after first render
            // fixes endpoint location calculation for step without connections
            this.areaView.jsPlumbInstance.updateOffset({elId: this.id(), recalc: true});
        },

        makeTarget: function() {
            const instance = this.areaView.jsPlumbInstance;
            this.jsPlumbTarget = instance.makeTarget(this.$el, $.extend(true, {}, _.result(this, 'targetDefaults')));
        },

        makeSource: function() {
            const instance = this.areaView.jsPlumbInstance;
            this.jsPlumbSource = instance.makeSource(this.$el, $.extend(true,
                {},
                _.result(this, 'sourceDefaults'),
                {
                    onMaxConnections: function(info, e) {
                        mediator.execute(
                            'showErrorMessage',
                            __('oro.workflow.error.maxconnections', info),
                            e
                        );
                    }
                }
            ));
        }
    });

    return FlowchartViewerStepView;
});
