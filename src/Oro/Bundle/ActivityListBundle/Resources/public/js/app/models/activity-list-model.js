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
