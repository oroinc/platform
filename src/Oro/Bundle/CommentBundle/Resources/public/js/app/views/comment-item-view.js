define(function(require) {
    'use strict';

    var CommentItemView;
    var $ = require('jquery');
    var BaseView = require('oroui/js/app/views/base/view');
    var template = require('text!orocomment/templates/comment/comment-item-view.html');
    var dateTimeFormatter = require('orolocale/js/formatter/datetime');

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
            'mouseenter .more-bar-holder': function(e) {
                $(e.target).trigger('click');
            },
            'mouseleave .more-bar-holder': function(e) {
                this.$('.show > [data-toggle="dropdown"]').trigger('tohide.bs.dropdown');
            }
        },

        /**
         * @inheritDoc
         */
        constructor: function CommentItemView() {
            CommentItemView.__super__.constructor.apply(this, arguments);
        },

        getTemplateData: function() {
            var data = CommentItemView.__super__.getTemplateData.apply(this, arguments);
            if (data.createdAt) {
                data.createdTime = dateTimeFormatter.formatDateTime(data.createdAt);
            }
            if (data.updatedAt) {
                data.updatedTime = dateTimeFormatter.formatDateTime(data.updatedAt);
            }
            return data;
        },

        onEditCommentClick: function(e) {
            e.stopImmediatePropagation();
            this.$el.trigger('comment-edit', [this.model]);
        },

        onRemoveCommentClick: function(e) {
            e.stopImmediatePropagation();
            this.$el.trigger('comment-remove', [this.model]);
        }
    });

    return CommentItemView;
});
