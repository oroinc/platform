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
            infoBlock: '> .accordion-group > .accordion-body .message .info',
            commentsBlock: '> .accordion-group > .accordion-body .message .comment',
            commentsCountBlock: '> .accordion-group > .accordion-heading .comment-count .count',
            ignoreHead: false
        },
        attributes: {
            'class': 'list-item'
        },
        events: {
            'click .item-edit-button': 'onEdit',
            'click .item-remove-button': 'onDelete',
            'click .accordion-toggle': 'onToggle',
            'click .accordion-heading': 'onAccordionHeaderClick'
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
            data.ignoreHead = this.options.ignoreHead;
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
            } else {
                data.editor_url = '';
            }
            data.routing = routing;
            data.dateFormatter = dateTimeFormatter;

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

        onAccordionHeaderClick: function (e) {
            var ignoreItems = 'a, button, .accordition-toggle';
            if ($(e.target).is(ignoreItems) || $(e.target).parents(ignoreItems).length) {
                // ignore clicks on links, buttons and accordition-toggle
                return;
            }
            this.getAccorditionToggle().trigger('click');
        },

        onToggle: function (e) {
            e.preventDefault();
            this.toggle();
        },

        toggle: function () {
            if (!this.options.ignoreHead && this.model.get('is_head')) {
                this.model.collection.trigger('toViewGroup', this.model);
            } else {
                this.model.collection.trigger('toView', this.model);
            }
        },

        getAccorditionToggle: function () {
            return this.$('> .accordion-group > .accordion-heading .accordion-toggle');
        },

        getAccorditionBody: function () {
            return this.$('> .accordion-group > .accordion-body');
        },

        isCollapsed: function () {
            return this.getAccorditionToggle().hasClass('collapsed');
        },

        _onContentChange: function () {
            this.$(this.options.infoBlock).html(this.model.get('contentHTML'));
            mediator.execute('layout:init', this.$el, this);
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
            this.listenTo(comments.collection, 'stateChange', this.updateCommentsQuantity, this);
        },

        hasCommentComponent: function () {
            return Boolean(this.subview('comments'));
        },

        updateCommentsQuantity: function (collection) {
            if (collection instanceof Backbone.Collection) {
                this.model.set('commentCount', collection.getState().totalItemsQuantity);
            }
        }
    });

    return ActivityView;
});
