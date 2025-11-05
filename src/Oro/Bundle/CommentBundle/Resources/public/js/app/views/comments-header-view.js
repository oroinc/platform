import BaseView from 'oroui/js/app/views/base/view';
import template from 'text-loader!orocomment/templates/comment/comments-header-view.html';

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

export default CommentsHeaderView;
