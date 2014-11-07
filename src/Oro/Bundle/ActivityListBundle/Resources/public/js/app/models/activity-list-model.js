/*global define*/
define([
    'oroui/js/app/models/base/model'
], function (BaseModel) {
    'use strict';

    var ActivityModel;

    ActivityModel = BaseModel.extend({
        defaults: {
            id: '',
            owner: '',
            owner_id: '',
            organization: '',
            verb: '',
            subject: '',
            data: '',
            configuration: '',

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
