define(function(require) {
    'use strict';

    var CommentsHeaderView;
    var BaseView = require('oroui/js/app/views/base/view');
    var template = require('text!../../../templates/comment/comments-header-view.html');

    CommentsHeaderView = BaseView.extend({
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

        onLoadMoreClick: function(e) {
            e.stopImmediatePropagation();
            this.$el.trigger('comment-load-more');
        }
    });

    return CommentsHeaderView;
});
