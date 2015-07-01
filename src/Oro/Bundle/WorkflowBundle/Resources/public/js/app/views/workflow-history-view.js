define(function (require) {
    'use strict';

    var FlowchartHistoryView,
        BaseView = require('oroui/js/app/views/base/view');
    FlowchartHistoryView = BaseView.extend({
        autoRender: true,
        container: '.workflow-history-container',
        initialize: function () {
            this.listenTo(this.model, 'change:index', this.render)
        },
        template: require('tpl!oroworkflow/templates/workflow-history.html')
    });

    return FlowchartHistoryView;
});
