/* global define */
define(function(require) {
    'use strict';

    var WorkflowHistoryStateCollection,
        Backbone = require('backbone'),
        BaseCollection = require('oroui/js/app/models/base/collection');

    WorkflowHistoryStateCollection = BaseCollection.extend({
        model: Backbone.Model
    });

    return WorkflowHistoryStateCollection;
});
