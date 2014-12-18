/*global define*/
define(function (require) {
    'use strict';

    var CommentListView,
        _ = require('underscore'),
        tools = require('oroui/js/tools'),
        BaseCollectionView = require('oroui/js/app/views/base/collection-view'),
        CommentItemView = require('./comment-item-view');

    CommentListView = BaseCollectionView.extend({
        autoRender: true,
        itemView: CommentItemView,

        listSelector: 'ul.comments',
        itemSelector: 'li',
        fallbackSelector: '.no-data',

        events: {
            'submit': 'addComment'
        },

        initialize: function (options) {
            this.template = _.template($(options.template).html());
            CommentListView.__super__.initialize.apply(this, arguments);
        },

        addComment: function (e) {
            var attrs, model;
            e.stopPropagation();
            e.preventDefault();
            attrs = tools.unpackFromQueryString(this.$('form').serialize());
            model = this.collection.add(attrs);
            model.save();
        }
    });

    return CommentListView;
});
