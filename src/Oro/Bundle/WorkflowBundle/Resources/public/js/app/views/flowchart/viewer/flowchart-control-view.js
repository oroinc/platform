define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const _ = require('underscore');

    const FlowchartControlView = BaseView.extend({
        autoRender: true,

        template: require('tpl-loader!oroworkflow/templates/flowchart/controls.html'),

        events: {
            'change [name="toggle-transition-labels"]': function(e) {
                this.model.set('transitionLabelsVisible', Boolean(_.result(e.currentTarget, 'checked')));
            }
        },

        /**
         * @inheritdoc
         */
        constructor: function FlowchartControlView(options) {
            FlowchartControlView.__super__.constructor.call(this, options);
        }
    });

    return FlowchartControlView;
});
