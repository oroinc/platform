define(function(require) {
    'use strict';

    var HistoryStateModel;
    var BaseModel = require('oroui/js/app/models/base/model');

    HistoryStateModel = BaseModel.extend({
        defaults: function() {
            return {
                data: {}
            };
        }
    });

    return HistoryStateModel;
});
