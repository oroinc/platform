/*global define*/
define(function (require) {
    'use strict';

    var CommentItemView,
        BaseView = require('oroui/js/app/views/base/view'),
        template = require('text!../../../templates/comment/comment-item-view.html');

    CommentItemView = BaseView.extend({
        template: template,
        tagName: 'li',
        className: 'comment',

        events: {
            'click .remove': 'removeModel'
        },

        removeModel: function () {
            this.model.destroy();
        }
    });

    return CommentItemView;
});
