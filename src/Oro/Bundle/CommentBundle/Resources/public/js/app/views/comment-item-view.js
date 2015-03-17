/*global define*/
define(function (require) {
    'use strict';

    var CommentItemView,
        dateTimeFormatter = require('orolocale/js/formatter/datetime'),
        _ = require('underscore'),
        routing = require('routing'),
        mediator = require('oroui/js/mediator'),
        __ = require('orotranslation/js/translator'),
        LoadingMaskView = require('oroui/js/app/views/loading-mask-view'),
        BaseView = require('oroui/js/app/views/base/view'),
        template = require('text!../../../templates/comment/comment-item-view.html'),
        DeleteConfirmation = require('oroui/js/delete-confirmation');

    CommentItemView = BaseView.extend({
        template: template,
        tagName: 'li',
        className: 'comment-item',

        events: {
            'click .item-remove-button': 'removeModel',
            'click .item-edit-button': 'editModel',
            'click .item-remove-attachment': 'removeAttachment',
            'shown .accordion-body': 'onToggle',
            'hidden .accordion-body': 'onToggle'
        },

        listen: {
            'request model': 'showLoading',
            'sync model': 'hideLoading',
            'error model': 'hideLoading'
        },

        accordionId: null,
        collapsed: true,

        initialize: function (options) {
            _.extend(this, _.pick(options || {}, ['accordionId', 'collapsed']));
            CommentItemView.__super__.initialize.apply(this, arguments);
        },

        getTemplateData: function () {
            var data = CommentItemView.__super__.getTemplateData.call(this);
            data.cid = this.cid;
            data.accordionId = this.accordionId;
            data.accordionTargetId = this.getAccordionTargetId();
            data.hasActions = data.removable || data.editable;
            data.message = this.prepareMessage();
            data.shortMessage = this.prepareShortMessage();
            data.isCollapsible = data.message !== data.shortMessage || data.attachmentURL;
            data.collapsed = this.collapsed;
            if (data.createdAt) {
                data.createdTime = dateTimeFormatter.formatDateTime(data.createdAt);
            }
            if (data.updatedAt) {
                data.updatedTime = dateTimeFormatter.formatDateTime(data.updatedAt);
            }
            if (data.owner_id) {
                data.owner_url = routing.generate('oro_user_view', {id: data.owner_id});
            }
            if (data.editor_id) {
                data.editor_url = routing.generate('oro_user_view', {id: data.editor_id});
            }
            return data;
        },

        render: function () {
            var loading;
            CommentItemView.__super__.render.apply(this, arguments);
            this.$('.dropdown-toggle').on('mouseover', function () {
                $(this).trigger('click');
            });
            this.$('.dropdown-menu').on('mouseleave', function () {
                $(this).parent().find('a.dropdown-toggle').trigger('click');
            });

            loading = new LoadingMaskView({
                container: this.$el
            });
            this.subview('loading', loading);

            return this;
        },

        showLoading: function () {
            this.subview('loading').show();
        },

        hideLoading: function () {
            this.subview('loading').hide();
        },

        removeModel: function (e) {
            e.stopPropagation();

            var model   = this.model;
            var confirm = new DeleteConfirmation({
                content: __('oro.comment.deleteConfirmation')
            });

            confirm.on('ok', _.bind(function () {
                model.destroy();
            }, this));

            confirm.open();
        },

        removeAttachment: function(e) {
            var itemView = this;
            e.stopPropagation();
            this.model.removeAttachment().then(function () {
                itemView.$('.attachment-item').remove();
                mediator.execute('showFlashMessage', 'success', __('oro.comment.attachment.delete_message'));
            });
        },

        editModel: function (e) {
            e.stopPropagation();
            if (!this.$('form').length) {
                // if it's not edit mode yet
                this.model.trigger('toEdit', this.model);
            }
            this.$('#' + this.getAccordionTargetId()).collapse({
                toggle: false
            }).collapse('show');
            this.$('form :input:first').click().focus();
        },

        onToggle: function (e) {
            this.collapsed = e.type === 'hidden';
        },

        getAccordionTargetId: function () {
            return 'accordion-item-' + this.cid;
        },

        prepareShortMessage: function () {
            var shortMessage = this.prepareMessage(),
                lineBreak = shortMessage.indexOf('<br />');
            if (lineBreak > 0) {
                shortMessage = shortMessage.substr(0, shortMessage.indexOf('<br />'));
            }
            shortMessage = _.trunc(shortMessage, 70, true);
            return shortMessage;
        },

        prepareMessage: function () {
            var message = this.model.get('message');
            message = _.nl2br(_.escape(message));
            return message;
        }
    });

    return CommentItemView;
});
