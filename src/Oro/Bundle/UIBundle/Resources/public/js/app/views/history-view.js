define(function (require) {
    'use strict';

    var HistoryView,
        BaseView = require('./base/view');
    HistoryView = BaseView.extend({
        autoRender: true,
        template: require('tpl!oroui/templates/history.html'),
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

    return HistoryView;
});
