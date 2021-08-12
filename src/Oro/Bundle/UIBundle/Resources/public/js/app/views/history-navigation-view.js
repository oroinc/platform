define(function(require) {
    'use strict';

    const BaseView = require('./base/view');

    const HistoryNavigationView = BaseView.extend({
        autoRender: true,
        template: require('tpl-loader!oroui/templates/history.html'),
        events: {
            'click .undo-btn': 'onUndo',
            'click .redo-btn': 'onRedo'
        },

        listen: {
            'change:index model': 'render'
        },

        /**
         * @inheritdoc
         */
        constructor: function HistoryNavigationView(options) {
            HistoryNavigationView.__super__.constructor.call(this, options);
        },

        onUndo: function() {
            const index = this.model.get('index');
            this.trigger('navigate', index - 1);
        },

        onRedo: function() {
            const index = this.model.get('index');
            this.trigger('navigate', index + 1);
        }
    });

    return HistoryNavigationView;
});
