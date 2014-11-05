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
            relatedEntityClass: '',
            relatedEntityId: '',
            activityEntityClass: '',
            activityEntityId: '',
            createdAt: '',
            updatedAt: '',

            hasUpdate: false,
            editable: true,
            removable: true
        }
    });

    return ActivityModel;
});
