import BaseView from 'oroui/js/app/views/base/view';
import template from 'text-loader!orocomment/templates/comment/comments-no-data.html';

const CommentsNoDataView = BaseView.extend({
    template: template,

    listen: {
        'add collection': 'render',
        'remove collection': 'render',
        'reset collection': 'render',
        'syncStateChange collection': 'render',
        'stateChange collection': 'render'
    },

    /**
     * @inheritdoc
     */
    constructor: function CommentsNoDataView(options) {
        CommentsNoDataView.__super__.constructor.call(this, options);
    }
});

export default CommentsNoDataView;
