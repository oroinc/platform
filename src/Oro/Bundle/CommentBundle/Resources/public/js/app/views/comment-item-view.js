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
            'click .remove': 'removeModel',
            'click .form-container': 'edit'
        },

        removeModel: function () {
            this.model.destroy();
        },

        getTemplateData: function () {
            var data = CommentItemView.__super__.getTemplateData.call(this);
            data.cid = this.cid;
            return data;
        },

        edit: function () {
            if (!this.$('form').length) {
                // if it's not edit mode yet
                this.model.trigger('toEdit', this.model);
            }
        }
    });

    return CommentItemView;
});
