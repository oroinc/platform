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

        getTemplateData: function () {
            var data = CommentItemView.__super__.getTemplateData.apply(this, arguments);
            if (data.createdAt) {
                data.createdTime = dateTimeFormatter.formatDateTime(data.createdAt);
            }
            if (data.updatedAt) {
                data.updatedTime = dateTimeFormatter.formatDateTime(data.updatedAt);
            }
            return data;
        }
    });

    return CommentItemView;
});
