/*global define*/
define(function (require) {
    'use strict';

    var CommentListView,
        _ = require('underscore'),
        BaseCollectionView = require('oroui/js/app/views/base/collection-view'),
        CommentItemView = require('./comment-item-view'),
        template = require('text!../../../templates/comment/comment-list-view.html');

    CommentListView = BaseCollectionView.extend({
        autoRender: true,
        itemView: CommentItemView,

        template: template,

        listSelector: 'ul.comments',
        itemSelector: 'li',
        fallbackSelector: '.no-data',

        events: {
            'click .add-comment-button': 'addComment'
        },

        addComment: function () {
            var attrs = {},
                fields = this.$('form').serializeArray();
            _.each(fields, function (field) {
                if (attrs[field.name] !== undefined) {
                    if (!attrs[field.name].push) {
                        attrs[field.name] = [attrs[field.name]];
                    }
                    attrs[field.name].push(field.value || '');
                } else {
                    attrs[field.name] = field.value || '';
                }
            });
            var model = this.collection.add(attrs);
            model.save();
        }
    });

    return CommentListView;
});
