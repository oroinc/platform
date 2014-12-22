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
        },

        getTemplateData: function () {
            var data = CommentListView.__super__.getTemplateData.call(this);
            data.cid = this.cid;
            data.accordionId = this.getAccordionId();
            return data;
        },

        initItemView: function(model) {
            if (this.itemView) {
                return new this.itemView({
                    autoRender: false,
                    model: model,
                    accordionId: this.getAccordionId()
                });
            } else {
                return CommentListView.__super__.initItemView.call(this, model);
            }
        },

        getAccordionId: function () {
            return 'accordion-' + this.cid;
        }
    });

    return CommentListView;
});
