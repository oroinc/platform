define(function(require) {
    'use strict';

    var FlowchartControlView;
    var BaseView = require('oroui/js/app/views/base/view');
    var _ = require('underscore');

    FlowchartControlView = BaseView.extend({
        autoRender: true,
        template: require('tpl!oroworkflow/templates/flowchart/controls.html'),
        events: {
            'change [name="toggle-transition-labels"]': function(e) {
                this.model.set('transitionLabelsVisible', Boolean(_.result(e.currentTarget, 'checked')));
            }
        }
    });

    return FlowchartControlView;
});
