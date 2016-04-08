define(function(require) {
    'use strict';

    var CommentModel;
    var $ = require('jquery');
    var _ = require('underscore');
    var Chaplin = require('chaplin');
    var routing = require('routing');
    var BaseModel = require('oroui/js/app/models/base/model');

    CommentModel = BaseModel.extend({
        route: 'oro_api_comment_get_item',
        routeRemoveAttachment: 'oro_api_comment_remove_attachment_item',

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
            avatarUrl: null,
            attachmentURL: null,
            attachmentFileName: null,
            attachmentSize: null,
            editable: true,
            removable: true
        },

        initialize: function(attrs, options) {
            CommentModel.__super__.initialize.apply(this, arguments);
            this.on('request', this.beginSync);
            this.on('sync', this.finishSync);
            this.on('error', this.unsync);
        },

        url: function() {
            var url;
            var parameters;
            if (this.isNew()) {
                if (!this.get('relationClass') || !this.get('relationId')) {
                    throw 'Please specify relationClass and relationId';
                }
                parameters = {
                    relationId:    this.get('relationId'),
                    relationClass: this.get('relationClass')
                };
                url = routing.generate('oro_api_comment_get_items', parameters);
            } else {
                parameters = {
                    id: this.get('id'),
                    _format: 'json'
                };
                url = routing.generate(this.route, parameters);
            }
            return url;
        },

        removeAttachment: function() {
            var model = this;
            var url = routing.generate(this.routeRemoveAttachment, {id: model.id});
            return $.ajax({
                url: url,
                type: 'POST',
                success: function() {
                    model.set('attachmentURL', null);
                    model.set('attachmentFileName', null);
                    model.set('attachmentSize', null);
                },
                error: function(jqxhr) {
                    model.trigger('error', model, jqxhr);
                }
            });
        },

        serialize: function() {
            var data = CommentModel.__super__.serialize.call(this);
            data.isNew = this.isNew();
            data.hasActions = data.removable || data.editable;
            data.message = this.getMessage();
            data.shortMessage = this.getShortMessage();
            if (data.owner_id) {
                data.owner_url = routing.generate('oro_user_view', {id: data.owner_id});
            }
            if (data.editor_id) {
                data.editor_url = routing.generate('oro_user_view', {id: data.editor_id});
            }
            return data;
        },

        getShortMessage: function() {
            var shortMessage = this.getMessage();
            var lineBreak = shortMessage.indexOf('<br />');
            if (lineBreak > 0) {
                shortMessage = shortMessage.substr(0, shortMessage.indexOf('<br />'));
            }
            shortMessage = _.trunc(shortMessage, 70, true);
            return shortMessage;
        },

        getMessage: function() {
            var message = this.get('message');
            message = _.nl2br(message);
            return message;
        }
    });

    _.extend(CommentModel.prototype, Chaplin.SyncMachine);

    return CommentModel;
});
