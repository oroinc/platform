define(function (require) {
    'use strict';

    var WorkflowHistoryView,
        BaseView = require('oroui/js/app/views/base/view');
    WorkflowHistoryView = BaseView.extend({
        autoRender: true,
        template: require('tpl!oroworkflow/templates/workflow-history.html'),
        events: {
            'click .undo-btn': 'onUndo',
            'click .redo-btn': 'onRedo'
        },

        listen: {
            'change:index model': 'render'
        },

        onUndo: function () {
            this.model.back();
        },

        onRedo: function () {
            this.model.forward();
        }
    });

    return WorkflowHistoryView;
});
