/*global define*/
define([
    'oroui/js/app/models/base/model'
], function (BaseModel) {
    'use strict';

    var CommentModel;

    CommentModel = BaseModel.extend({
        defaults: {
            id: '',

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
        }
    });

    return CommentModel;
});
