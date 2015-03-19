/*global define*/
define(function (require) {
    'use strict';

    var CommentListView,
        _ = require('underscore'),
        PaginationView = require('orocomment/js/app/views/pagination-view'),
        BaseCollectionView = require('oroui/js/app/views/base/collection-view'),
        CommentItemView = require('./comment-item-view');

    CommentListView = BaseCollectionView.extend({
        autoRender: true,
        itemView: CommentItemView,

        listSelector: 'ul.comments-list',
        itemSelector: 'li.comment-item',
        loadingContainerSelector: '.comments-block-inner',

        listen: {
            // once collection is synced -- recheck items views
            'sync collection': 'renderAllItems'
        },

        events: {
            'click .add-comment-button': 'addModel'
        },

        firstExpandedItems: 5,

        /**
         * Flag if user is permitted to add comments
         * @type {boolean}
         */
        canCreate: false,

        initialize: function (options) {
            _.extend(this, _.pick(options || {}, ['firstExpandedItems', 'canCreate']));
            this.template = _.template($(options.template).html());
            CommentListView.__super__.initialize.apply(this, arguments);
        },

        render: function () {
            CommentListView.__super__.render.call(this);
            this.createPaginationView();
            return this;
        },

        addModel: function (e) {
            e.stopPropagation();
            this.collection.trigger('toAdd');
        },

        createPaginationView: function () {
            var pager;
            pager = this.subview('pager');
            if (!pager) {
                pager = new PaginationView({
                    autoAttach: true,
                    container: this.$('.grid-toolbar'),
                    collection: this.collection
                });
                this.subview('pager', pager);
            } else {
                this.$('.grid-toolbar').append(pager.$el);
            }
        },

        getTemplateData: function () {
            var data = CommentListView.__super__.getTemplateData.call(this);
            data.cid = this.cid;
            data.accordionId = this.getAccordionId();
            data.canCreate = this.canCreate;
            return data;
        },

        initItemView: function(model) {
            var page, index, collapsed;
            if (!this.itemView) {
                return CommentListView.__super__.initItemView.call(this, model);
            }

            page = this.collection.getPage();
            index = this.collection.indexOf(model);
            collapsed = isNaN(this.firstExpandedItems) || page !== 1 || index >= this.firstExpandedItems;

            return new this.itemView({
                autoRender: false,
                model: model,
                accordionId: this.getAccordionId(),
                collapsed: collapsed
            });
        },

        filterer: function (model) {
            // exclude new models from rendering
            return !model.isNew();
        },

        getAccordionId: function () {
            return 'accordion-' + this.cid;
        }
    });

    return CommentListView;
});
