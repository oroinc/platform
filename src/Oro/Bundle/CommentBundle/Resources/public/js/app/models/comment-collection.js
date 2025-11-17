import LoadMoreCollection from 'oroui/js/app/models/load-more-collection';
import CommentModel from 'orocomment/js/app/models/comment-model';

const CommentCollection = LoadMoreCollection.extend({
    model: CommentModel,

    /**
     * @inheritdoc
     */
    routeDefaults: {
        routeName: 'oro_api_comment_get_items',
        routeQueryParameterNames: ['page', 'limit', 'createdAt', 'updatedAt']
    },

    comparator: 'createdAt',

    /**
     * @inheritdoc
     */
    constructor: function CommentCollection(...args) {
        CommentCollection.__super__.constructor.apply(this, args);
    },

    create: function() {
        return new CommentModel({
            relationId: this._route.get('relationId'),
            relationClass: this._route.get('relationClass')
        });
    }
});

export default CommentCollection;
