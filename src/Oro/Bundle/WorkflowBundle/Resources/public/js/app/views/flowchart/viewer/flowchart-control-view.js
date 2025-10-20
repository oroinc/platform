import BaseView from 'oroui/js/app/views/base/view';
import _ from 'underscore';
import template from 'tpl-loader!oroworkflow/templates/flowchart/controls.html';

const FlowchartControlView = BaseView.extend({
    autoRender: true,

    template,

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

export default FlowchartControlView;
