/*global define*/
define([
    'oroui/js/app/models/base/model'
], function (BaseModel) {
    'use strict';

    var ActivityModel;

    ActivityModel = BaseModel.extend({
        defaults: {
            id: '',
            verb: '',
            subject: '',
            data: '',

            briefTemplate: '',
            briefData: '',
            fullTemplate: '',
            fullData: '',

            relatedEntity_class: '',
            relatedEntity_id: '',
            activityEntity_class: '',
            activityEntity_id: '',

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
            updatedBy_avatar: null,

            createdAt: '',
            updatedAt: ''
        }
    });

    return ActivityModel;
});
