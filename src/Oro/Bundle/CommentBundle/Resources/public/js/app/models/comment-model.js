/*global define*/
define(function (require) {
    'use strict';

    var CommentModel,
        routing = require('routing'),
        BaseModel = require('oroui/js/app/models/base/model');

    CommentModel = BaseModel.extend({
        route: 'oro_api_comment_get_item',
        idAttribute: 'id',
        formName: null,

        /*defaults: {
            owner: '',
            owner_id: '',

            editor: '',
            editor_id: '',

            organization: '',
            data: '',
            configuration: '',

            createdAt: '',
            updatedAt: '',

            is_loaded: false,
            contentHTML: '',

            editable: true,
            removable: true
        },*/

        initialize: function (attrs, options) {
            this._defineFormName(options);
            CommentModel.__super__.initialize.apply(this, arguments);
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

        toJSON: function (options) {
            var serverAttrs = {},
                formName = this._defineFormName(options);
            serverAttrs[formName] = CommentModel.__super__.toJSON.call(this, options);
            return serverAttrs;
        },

        parse: function(resp, options) {
            var formName = this._defineFormName(options);
            if (typeof resp === 'object' && resp.hasOwnProperty(formName)) {
                resp = resp[formName];
            }
            return resp;
        },

        set: function(key, val, options) {
            var formName;
            if (typeof key === 'object') {
                options = val;
                formName = this._defineFormName(options);
                if (key.hasOwnProperty(formName)) {
                    arguments[0] = key[formName];
                }
            }
            return CommentModel.__super__.set.apply(this, arguments);
        },

        _defineFormName: function (options) {
            if (!this.formName) {
                this.formName = options.formName || options.collection.formName;
            }
            return this.formName;
        }
    });

    return CommentModel;
});
