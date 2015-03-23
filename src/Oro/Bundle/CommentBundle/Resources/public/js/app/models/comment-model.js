/*global define*/
define(function (require) {
    'use strict';

    var CommentModel,
        Chaplin = require('chaplin'),
        routing = require('routing'),
        dateTimeFormatter = require('orolocale/js/formatter/datetime'),
        BaseModel = require('oroui/js/app/models/base/model');

    CommentModel = BaseModel.extend({
        route: 'oro_api_comment_get_item',
        routeRemoveAttachment: 'oro_api_comment_remove_attachment_item',

        relatedEntityId: undefined,
        relatedEntityClassName: undefined,

        defaults: {
            owner: '',
            owner_id: null,
            editor: '',
            editor_id: null,
            message: '',
            relationClass: null,
            relationId: null,
            createdAt: '',
            updatedAt: '',
            attachmentURL: null,
            attachmentFileName: null,
            attachmentSize: null,
            editable: true,
            removable: true
        },

        initialize: function (attrs, options) {
            if (options) {
                this.relatedEntityId = options.relatedEntityId;
                this.relatedEntityClassName = options.relatedEntityClassName;
            }
            CommentModel.__super__.initialize.apply(this, arguments);
            this.on('request', this.beginSync);
            this.on('sync', this.finishSync);
            this.on('error', this.unsync);
        },

        urlRoot: function () {
            var parameters;
            if (this.collection) {
                return this.collection.url();
            } else {
                if (!this.relatedEntityClassName || !this.relatedEntityId) {
                    throw "Please specify relatedEntityClassName and relatedEntityId";
                }
                parameters = {
                    relationId:    this.relatedEntityId,
                    relationClass: this.relatedEntityClassName
                };
                return routing.generate('oro_api_comment_get_items', parameters);
            }
        },

        removeAttachment: function () {
            var model = this,
                url = routing.generate(this.routeRemoveAttachment, {id: model.id});
            return $.ajax({
                url: url,
                type: 'POST',
                success: function () {
                    model.set('attachmentURL', null);
                    model.set('attachmentFileName', null);
                    model.set('attachmentSize', null);
                },
                error: function (jqxhr) {
                    model.trigger('error', model, jqxhr);
                }
            });
        },

        serialize: function () {
            var data = CommentModel.__super__.serialize.call(this);
            data.isNew = this.isNew();
            data.hasActions = data.removable || data.editable;
            data.message = this.getMessage();
            data.shortMessage = this.getShortMessage();
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

        getShortMessage: function () {
            var shortMessage = this.getMessage(),
                lineBreak = shortMessage.indexOf('<br />');
            if (lineBreak > 0) {
                shortMessage = shortMessage.substr(0, shortMessage.indexOf('<br />'));
            }
            shortMessage = _.trunc(shortMessage, 70, true);
            return shortMessage;
        },

        getMessage: function () {
            var message = this.get('message');
            message = _.nl2br(_.escape(message));
            return message;
        }
    });

    _.extend(CommentModel.prototype, Chaplin.SyncMachine);

    return CommentModel;
});
