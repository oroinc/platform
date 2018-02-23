define(function(require) {
    'use strict';

    var HistoryStateModel;
    var BaseModel = require('oroui/js/app/models/base/model');

    HistoryStateModel = BaseModel.extend({
        /**
         * @inheritDoc
         */
        constructor: function HistoryStateModel() {
            HistoryStateModel.__super__.constructor.apply(this, arguments);
        },

        defaults: function() {
            return {
                data: {}
            };
        }
    });

    return HistoryStateModel;
});
