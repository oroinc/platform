/*global define*/
define(function (require) {
    'use strict';

    var CommentItemView,
        BaseView = require('oroui/js/app/views/base/view'),
        template = require('text!../../../templates/comment/comment-item-view.html'),
        dateTimeFormatter = require('orolocale/js/formatter/datetime');

    CommentItemView = BaseView.extend({
        template: template,
        tagName: 'li',
        className: 'comment-item',

        listen: {
            'change model': 'render'
        },

        events: {
            'click .item-remove-button': 'onRemoveCommentClick',
            'click .item-edit-button': 'onEditCommentClick',

            // open/close dropdown on hover
            'mouseover .dropdown-toggle': function (e) {
                $(e.target).trigger('click');
            },
            'mouseleave .dropdown-menu': function (e) {
                $(e.target).parent().find('a.dropdown-toggle').trigger('click');
            }
        },

        getTemplateData: function () {
            var data = CommentItemView.__super__.getTemplateData.apply(this, arguments);
            if (data.createdAt) {
                data.createdTime = dateTimeFormatter.formatDateTime(data.createdAt);
            }
            if (data.updatedAt) {
                data.updatedTime = dateTimeFormatter.formatDateTime(data.updatedAt);
            }
            return data;
        },

        onEditCommentClick: function () {
            this.$el.trigger('comment-edit', [this.model]);
        },

        onRemoveCommentClick: function () {
            this.$el.trigger('comment-remove', [this.model]);
        }
    });

    return CommentItemView;
});
