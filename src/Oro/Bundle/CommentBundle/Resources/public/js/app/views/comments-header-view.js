define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const template = require('text-loader!orocomment/templates/comment/comments-header-view.html');

    const CommentsHeaderView = BaseView.extend({
        template: template,

        listen: {
            'add collection': 'render',
            'remove collection': 'render',
            'reset collection': 'render',
            'syncStateChange collection': 'render',
            'stateChange collection': 'render'
        },

        events: {
            'click a.load-more': 'onLoadMoreClick'
        },

        /**
         * @inheritdoc
         */
        constructor: function CommentsHeaderView(options) {
            CommentsHeaderView.__super__.constructor.call(this, options);
        },

        onLoadMoreClick: function(e) {
            e.stopImmediatePropagation();
            this.$el.trigger('comment-load-more');
        }
    });

    return CommentsHeaderView;
});
