define(function(require) {
    'use strict';

    var HistoryStateModel,
        Backbone = require('backbone');

    HistoryStateModel = Backbone.Model.extend({
        defaults: {
            data: {}
        }
    });

    return HistoryStateModel;
});
