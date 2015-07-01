define(function(require) {
    'use strict';

    var WorkflowHistoryStateModel,
        BaseModel = require('oroui/js/app/models/base/model');

    WorkflowHistoryStateModel = BaseModel.extend({
        defaults: {
            steps: null,
            transitions: null
        }
    });

    return WorkflowHistoryStateModel;
});
