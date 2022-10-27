define(function(require) {
    'use strict';

    const $ = require('jquery');
    const BaseView = require('oroui/js/app/views/base/view');
    const template = require('text-loader!orocomment/templates/comment/comment-item-view.html');
    const dateTimeFormatter = require('orolocale/js/formatter/datetime');

    const CommentItemView = BaseView.extend({
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
            'mouseenter .more-bar-holder [data-toggle="dropdown"]': function(e) {
                $(e.currentTarget).trigger('click');
            },
            'mouseleave .more-bar-holder': function(e) {
                $(e.currentTarget).find('[data-toggle="dropdown"]').trigger('tohide.bs.dropdown');
            }
        },

        /**
         * @inheritdoc
         */
        constructor: function CommentItemView(options) {
            CommentItemView.__super__.constructor.call(this, options);
        },

        getTemplateData: function() {
            const data = CommentItemView.__super__.getTemplateData.call(this);
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
