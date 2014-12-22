/*global define*/
define(function (require) {
    'use strict';

    var CommentModel,
        routing = require('routing'),
        BaseModel = require('oroui/js/app/models/base/model');

    CommentModel = BaseModel.extend({
        route: 'oro_api_comment_get_item',
        idAttribute: 'id',

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
        }
    });

    return CommentModel;
});
