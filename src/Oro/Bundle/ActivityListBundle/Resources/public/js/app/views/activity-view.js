define(function(require) {
    'use strict';

    var ActivityView;
    var $ = require('jquery');
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');
    var routing = require('routing');
    var dateTimeFormatter = require('orolocale/js/formatter/datetime');
    var LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    var CommentComponent = require('orocomment/js/app/components/comment-component');

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
            'addedToParent': function() {
                var view = this.getEmailThreadView();
                if (view) {
                    view.refreshEmails();
                }
            },
            'change:contentHTML model': '_onContentChange',
            'change:commentCount model': '_onCommentCountChange',
            'change:isContentLoading model': '_onContentLoadingStatusChange'
        },

        initialize: function(options) {
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

        getTemplateData: function() {
            var data = ActivityView.__super__.getTemplateData.call(this);
            data.has_comments = this.options.configuration.has_comments;
            data.ignoreHead = this.options.ignoreHead;
            data.collapsed = this.collapsed;
            data.createdAt = dateTimeFormatter.formatSmartDateTime(data.createdAt);
            data.updatedAt = dateTimeFormatter.formatSmartDateTime(data.updatedAt);
            // use special model's method to get activity class name with replaced slashes
            data.relatedActivityClass = _.escape(this.model.getRelatedActivityClass());
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
            data.editable = this.model.get('editable');
            data.removable = this.model.get('removable');

            return data;
        },

        render: function() {
            ActivityView.__super__.render.apply(this, arguments);
            this.$('.dropdown-toggle.activity-item').on('mouseover', function() {
                $(this).trigger('click');
            });
            this.$('.dropdown-menu.activity-item').on('mouseleave', function() {
                $(this).parent().find('a.dropdown-toggle').trigger('click');
            });
            if (this.$('.dropdown-menu.activity-item .launcher-item').children().length === 0) {
                this.$('.dropdown-menu.activity-item').hide();
                this.$('.dropdown-toggle.activity-item').text('');
            }
            this.initLayout();
            return this;
        },

        onEdit: function() {
            this.model.collection.trigger('toEdit', this.model);
        },

        onDelete: function() {
            this.model.collection.trigger('toDelete', this.model);
        },

        onAccordionHeaderClick: function(e) {
            var ignoreItems = 'a, button, .accordition-toggle';
            if ($(e.target).is(ignoreItems) || $(e.target).parents(ignoreItems).length) {
                // ignore clicks on links, buttons and accordition-toggle
                return;
            }
            this.getAccorditionToggle().trigger('click');
        },

        onToggle: function(e) {
            e.preventDefault();
            this.toggle();
        },

        toggle: function() {
            if (!this.options.ignoreHead && this.model.get('is_head')) {
                this.model.collection.trigger('toViewGroup', this.model);
            } else {
                this.model.collection.trigger('toView', this.model);
            }
        },

        getAccorditionToggle: function() {
            return this.$('> .accordion-group > .accordion-heading .accordion-toggle');
        },

        getAccorditionBody: function() {
            return this.$('> .accordion-group > .accordion-body');
        },

        isCollapsed: function() {
            return this.getAccorditionToggle().hasClass('collapsed');
        },

        _onContentChange: function() {
            this.$(this.options.infoBlock).html(this.model.get('contentHTML'));
            this.initLayout().done(_.bind(function() {
                // if the activity has an EmailTreadView -- handle comment count change in own way
                var emailTreadView = this.getEmailThreadView();
                if (emailTreadView) {
                    this.listenTo(emailTreadView, 'commentCountChanged', function(diff) {
                        this.model.set('commentCount', this.model.get('commentCount') + diff);
                    });
                }
                var loadingView = this.subview('loading');
                if (loadingView) {
                    loadingView.hide();
                }
            }, this));
        },

        _onCommentCountChange: function() {
            var quantity = this.model.get('commentCount');
            var $elem = this.$(this.options.commentsCountBlock);
            $elem.html(quantity);
            $elem.parent()[quantity > 0 ? 'show' : 'hide']();
        },

        _onContentLoadingStatusChange: function() {
            if (this.model.get('isContentLoading')) {
                this.subview('loading', new LoadingMaskView({
                    container: this.$el
                }));
                this.subview('loading').show();
            }
        },

        /**
         * Initializes comments component if it is necessary
         *
         * @param {Object} options
         */
        initCommentsComponent: function(options) {
            var commentsComponent;
            if (!this.isCommentComponentRequired()) {
                return;
            }
            options._sourceElement = this.$(this.options.commentsBlock);
            commentsComponent = new CommentComponent(options);
            this.pageComponent('comments', commentsComponent, options._sourceElement[0]);
            this.listenTo(commentsComponent.collection, 'stateChange', this.updateCommentsQuantity, this);
        },

        /**
         * Check if comments component have to be initialized
         * @returns {boolean}
         */
        isCommentComponentRequired: function() {
            // comments component is not initialized yet, activity is "commentable" and it has place to be initialized
            return !this.pageComponent('comments') &&
                this.model.get('commentable') &&
                Boolean(this.$(this.options.commentsBlock).length);
        },

        updateCommentsQuantity: function() {
            var component = this.pageComponent('comments');
            if (component !== null) {
                this.model.set('commentCount', component.collection.getState().totalItemsQuantity);
            }
        },

        getEmailThreadView: function() {
            var threadViewComponent = this.pageComponent('thread-view');
            var view;

            if (threadViewComponent) {
                view = threadViewComponent.view.pageComponent('email-thread').view;
            }

            return view;
        }
    });

    return ActivityView;
});
