/*global define*/
define(function (require) {
    'use strict';

    var CommentListView,
        _ = require('underscore'),
        BaseCollectionView = require('oroui/js/app/views/base/collection-view'),
        CommentItemView = require('./comment-item-view');

    CommentListView = BaseCollectionView.extend({
        autoRender: true,
        itemView: CommentItemView,

        listSelector: 'ul.comments',
        itemSelector: 'li',

        initialize: function (options) {
            this.template = _.template($(options.template).html());
            CommentListView.__super__.initialize.apply(this, arguments);
        }
    });

    return CommentListView;
});
