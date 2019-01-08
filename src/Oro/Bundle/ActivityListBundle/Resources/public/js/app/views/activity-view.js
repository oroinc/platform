define(function(require) {
    'use strict';

    var ActivityView;
    var $ = require('jquery');
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');
    var mediator = require('oroui/js/mediator');
    var routing = require('routing');
    var dateTimeFormatter = require('orolocale/js/formatter/datetime');
    var LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    var CommentComponent = require('orocomment/js/app/components/comment-component');
    var transitionHandler = require('oroworkflow/js/transition-handler');

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
            // commentsBlock is placed in next container after infoBlock
            commentsBlock: '> .accordion-group > .accordion-body .activity-item-content > .info + * > .comment',
            commentsCountBlock: '> .accordion-group > .accordion-heading .comment-count .count',
            ignoreHead: false
        },
        attributes: {
            'data-layout': 'separate',
            'class': 'list-item'
        },
        events: {
            'click .transition-item': 'onTransition',
            'click .item-edit-button': 'onEdit',
            'click .item-remove-button': 'onDelete',
            'click [data-toggle="collapse"]': 'onToggle',
            'click .accordion-heading': 'onAccordionHeaderClick',
            'click .activity-actions > .dropdown-menu': 'onActionClick',
            'mouseover .activity-actions > [data-toggle="dropdown"]': 'showActionsDropdown',
            'mouseleave .activity-actions': 'hideActionsDropdown'
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

        /**
         * @inheritDoc
         */
        constructor: function ActivityView() {
            ActivityView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
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
                data.owner_url = routing.generate('oro_user_view', {id: data.owner_id});
            } else {
                data.owner_url = '';
            }
            if (data.editor_id) {
                data.editor_url = routing.generate('oro_user_view', {id: data.editor_id});
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
            if (this.$('.activity-actions > .dropdown-menu li').children().length === 0) {
                this.$('.activity-actions > .dropdown-menu').hide();
                this.$('.activity-actions > [data-toggle="dropdown"]').text('');
            }
            this.initLayout();
            return this;
        },

        onTransition: function(e) {
            e.preventDefault();
            var $el = $(e.target);
            var activityModel = this.model;

            $el.one('transitions_success', function() {
                // manually update the model in case the workflow transition does not change any of it's attributes
                activityModel.set('updatedAt', new Date());
                mediator.trigger('widget_success:activity_list:item:update');
            });

            transitionHandler.call($el, false);
        },

        onEdit: function(e) {
            this.model.collection.trigger('toEdit', this.model, this.$(e.currentTarget).data('actionExtraOptions'));
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

        onActionClick: function(e) {
            this.$('.activity-actions > .dropdown-menu').trigger('tohide.bs.dropdown');
        },

        showActionsDropdown: function(e) {
            if (!_.isMobile() && !this.$('.activity-actions > [data-toggle="dropdown"]').parent().hasClass('show')) {
                this.$('.activity-actions > [data-toggle="dropdown"]').dropdown('toggle');
            }
        },

        hideActionsDropdown: function(e) {
            if (!_.isMobile() && this.$('.activity-actions > [data-toggle="dropdown"]').parent().hasClass('show')) {
                this.$('.activity-actions > [data-toggle="dropdown"]').dropdown('toggle');
            }
        },

        toggle: function() {
            if (!this.options.ignoreHead && this.model.get('is_head')) {
                this.model.collection.trigger('toViewGroup', this.model);
            } else {
                this.model.collection.trigger('toView', this.model);
            }
        },

        getAccorditionToggle: function() {
            return this.$('> .accordion-group > .accordion-heading [data-toggle="collapse"]');
        },

        getAccorditionBody: function() {
            return this.$('> .accordion-group > .accordion-body');
        },

        isCollapsed: function() {
            return this.getAccorditionToggle().hasClass('collapsed');
        },

        _onContentChange: function() {
            this.$(this.options.infoBlock)
                .trigger('content:remove') // to dispose only components related to infoBlock
                .html(this.model.get('contentHTML'));
            this.initLayout().done(this._handleLayoutInit.bind(this));
        },

        _handleLayoutInit: function() {
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
        },

        _onCommentCountChange: function() {
            var quantity = this.model.get('commentCount');
            var $elem = this.$(this.options.commentsCountBlock);
            $elem.html(quantity);
            $elem.parent()[quantity > 0 ? 'show' : 'hide']();
        },

        _onContentLoadingStatusChange: function(model) {
            if (!this.subview('loading')) {
                this.subview('loading', new LoadingMaskView({
                    container: this.$el
                }));
            }
            if (this.model.get('isContentLoading')) {
                this.subview('loading').show();
            } else if (!model.hasChanged('contentHTML')) {
                // in case contentHTML was not changed after load action -- hide loading mask now,
                // otherwise it'll be hidden on initLayout done
                this.subview('loading').hide();
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
