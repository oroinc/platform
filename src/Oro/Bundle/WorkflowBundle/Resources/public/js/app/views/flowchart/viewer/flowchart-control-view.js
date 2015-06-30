define(function (require) {
    'use strict';
    var BaseView = require('oroui/js/app/views/base/view'),
        _ = require('underscore'),
        FlowchartControlView;

    FlowchartControlView = BaseView.extend({
        autoRender: true,
        events: {
            'change [name="toggle-transition-labels"]': function (e) {
                this.model.set('transitionLabelsVisible', !!_.result(e.currentTarget, 'checked'));
            }
        },
        render: function () {
            var transitionLabelsVisible = this.model.get('transitionLabelsVisible');
            this.undelegateEvents();
            this.$el.find('[name="toggle-transition-labels"]').prop('checked', transitionLabelsVisible);
            this.delegateEvents();
        }
    });

    return FlowchartControlView;
});
