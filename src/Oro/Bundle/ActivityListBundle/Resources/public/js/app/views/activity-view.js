/*jslint browser:true, nomen:true*/
/*global define, alert*/
define([
    'jquery',
    'underscore',
    'backbone',
    'oroui/js/mediator',
    'oroui/js/app/views/base/view',
    'routing',
    'orolocale/js/formatter/datetime'
], function ($, _, Backbone, mediator, BaseView, routing, dateTimeFormatter) {
    'use strict';

    var ActivityView;
    ActivityView = BaseView.extend({
        options: {
            configuration: {
                has_comments: false
            },
            template: null,
            urls: {
                viewItem: null,
                updateItem: null,
                deleteItem: null
            },
            infoBlock: '.accordion-body .message .info',
            commentsBlock: '.accordion-body .message .comment',
            commentsCountBlock: '.comment-count .count'
        },
        attributes: {
            'class': 'list-item'
        },
        events: {
            'click .item-edit-button': 'onEdit',
            'click .item-remove-button': 'onDelete',
            'click .accordion-toggle': 'onToggle'
        },
        listen: {
            'change:contentHTML model': '_onContentChange',
            'change:commentCount model': '_onCommentCountChange'
        },

        initialize: function (options) {
            this.options = _.defaults(options || {}, this.options);
            this.collapsed = true;
            if (this.options.template) {
                this.template = _.template($(this.options.template).html());
            }
            if (this.model.get('relatedActivityClass')) {
                var templateName = '#template-activity-item-' + this.model.get('relatedActivityClass');
                templateName = templateName.replace(/\\/g, '_');
                this.template = _.template($(templateName).html());
            }
            ActivityView.__super__.initialize.apply(this, arguments);
        },

        getTemplateData: function () {
            var data = ActivityView.__super__.getTemplateData.call(this);
            data.has_comments = this.options.configuration.has_comments;
            data.collapsed = this.collapsed;
            data.createdAt = dateTimeFormatter.formatDateTime(data.createdAt);
            data.updatedAt = dateTimeFormatter.formatDateTime(data.updatedAt);
            data.relatedActivityClass = _.escape(data.relatedActivityClass);
            if (data.owner_id) {
                data.owner_url = routing.generate('oro_user_view', {'id': data.owner_id});
            } else {
                data.owner_url = '';
            }
            if (data.editor_id) {
                data.editor_url = routing.generate('oro_user_view', {'id': data.editor_id});
            }else {
                data.editor_url = '';
            }
            data.routing = routing;

            return data;
        },

        render: function () {
            ActivityView.__super__.render.apply(this, arguments);
            this.$('.dropdown-toggle.activity-item').on('mouseover', function () {
                $(this).trigger('click');
            });
            this.$('.dropdown-menu.activity-item').on('mouseleave', function () {
                $(this).parent().find('a.dropdown-toggle').trigger('click');
            });
            mediator.execute('layout:init', this.$el, this);
            return this;
        },

        onEdit: function () {
            this.model.collection.trigger('toEdit', this.model);
        },

        onDelete: function () {
            this.model.collection.trigger('toDelete', this.model);
        },

        onToggle: function (e) {
            e.preventDefault();
            this.model.collection.trigger('toView', this.model);
        },

        isCollapsed: function () {
            return this.$('.accordion-toggle').hasClass('collapsed');
        },

        _onContentChange: function () {
            this.$(this.options.infoBlock).html(this.model.get('contentHTML'));
        },

        _onCommentCountChange: function () {
            var quantity = this.model.get('commentCount'),
                $elem = this.$(this.options.commentsCountBlock);
            $elem.html(quantity);
            $elem.parent()[quantity > 0 ? 'show' : 'hide']();
        },

        getCommentsBlock: function () {
            return this.$(this.options.commentsBlock);
        },

        setCommentComponent: function (comments) {
            this.subview('comments', comments);
            this.listenTo(comments.collection, 'sync', this.updateCommentsQuantity, this);
        },

        hasCommentComponent: function () {
            return Boolean(this.subview('comments'));
        },

        updateCommentsQuantity: function (collection) {
            if (collection instanceof Backbone.Collection) {
                this.model.set('commentCount', collection.getRecordsQuantity());
            }
        }
    });

    return ActivityView;
});
