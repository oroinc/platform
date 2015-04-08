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

            editor: '',
            editor_id: '',

            organization: '',
            verb: '',
            subject: '',
            data: '',
            configuration: '',
            commentCount: 0,

            activityEntityClass: '',
            activityEntityId: '',

            createdAt: '',
            updatedAt: '',

            is_loaded: false,
            is_head: false,
            contentHTML: '',

            editable: true,
            removable: true,
            commentable: false,

            targetEntityData: ''
        },

        initialize: function () {
            this.once('change:contentHTML', function () {
                this.set('is_loaded', true);
            });
            ActivityModel.__super__.initialize.apply(this, arguments);
        },

        getRelatedActivityClass: function () {
            return this.get('relatedActivityClass').replace(/\\/g, '_');
        },

        getUid: function () {
            return this.get('relatedActivityClass') + ':' + this.get('relatedActivityId');
        }
    });

    return ActivityModel;
});
