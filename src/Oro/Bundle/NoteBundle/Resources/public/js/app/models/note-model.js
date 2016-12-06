define([
    'oroui/js/app/models/base/model'
], function(BaseModel) {
    'use strict';

    var NoteModel;

    NoteModel = BaseModel.extend({
        defaults: {
            id: '',
            message: '',
            createdAt: '',
            updatedAt: '',
            hasUpdate: false,
            editable: false,
            removable: false,
            createdBy: null,
            createdBy_id: null,
            createdBy_viewable: false,
            createdBy_avatar: null,
            updatedBy: null,
            updatedBy_id: null,
            updatedBy_viewable: false,
            updatedBy_avatar: null
        }
    });

    return NoteModel;
});
