/*global define*/
define(function (require) {
    'use strict';

    var CommentModel,
        Chaplin = require('chaplin'),
        routing = require('routing'),
        BaseModel = require('oroui/js/app/models/base/model');

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
            attachmentURL: null,
            attachmentFileName: null,
            attachmentSize: null,
            editable: true,
            removable: true
        },

        initialize: function() {
            CommentModel.__super__.initialize.apply(this, arguments);
            this.on('request', this.beginSync);
            this.on('sync', this.finishSync);
            this.on('error', this.unsync);
        },

        url: function () {
            var url, parameters;
            if (!this.isNew()) {
                parameters = {
                    id: this.get('id'),
                    _format: 'json'
                };
                url = routing.generate(this.route, parameters);
            } else {
                url = CommentModel.__super__.url.call(this);
            }
            return url;
        },

        removeAttachment: function() {
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
        }
    });

    _.extend(CommentModel.prototype, Chaplin.SyncMachine);

    return CommentModel;
});
