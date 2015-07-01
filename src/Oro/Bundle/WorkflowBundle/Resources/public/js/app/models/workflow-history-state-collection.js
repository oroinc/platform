/* global define */
define(function(require) {
    'use strict';

    var WorkflowHistoryStateCollection,
        BaseCollection = require('oroui/js/app/models/base/collection'),
        WorkflowHistoryStateModel = require('./workflow-history-state-model');

    WorkflowHistoryStateCollection = BaseCollection.extend({
        model: WorkflowHistoryStateModel
    });

    return WorkflowHistoryStateCollection;
});
