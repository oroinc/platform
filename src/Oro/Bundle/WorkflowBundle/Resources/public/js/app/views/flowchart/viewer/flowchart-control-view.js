define(function (require) {
    'use strict';
    var BaseView = require('oroui/js/app/views/base/view'),
        _ = require('underscore'),
        FlowchartControlView;

    FlowchartControlView = BaseView.extend({
        autoRender: true,
        template: require('tpl!oroworkflow/templates/flowchart/controls.html'),
        events: {
            'change [name="toggle-transition-labels"]': function (e) {
                this.model.set('transitionLabelsVisible', !!_.result(e.currentTarget, 'checked'));
            }
        }
    });

    return FlowchartControlView;
});
