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
            createdAt: '',
            updatedAt: ''

            //hasUpdate: false,
            //editable: false,
            //removable: false,
        },

        initialize: function (options) {
            debugger;

            //this.options = _.defaults(options || {}, this.options);
            //this.collapsed = false;
            //
            //if (!this.options.template) {
            //    debugger;
            //    this.template = _.template($(this.options.template).html());
            //}
        }
    });

    return ActivityModel;
});
