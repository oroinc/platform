define(function(require) {
    'use strict';

    var FlowchartStateModel;
    var BaseModel = require('oroui/js/app/models/base/model');

    FlowchartStateModel = BaseModel.extend({
        defaults: {
            transitionLabelsVisible: true
        },

        /**
         * @inheritDoc
         */
        constructor: function FlowchartStateModel() {
            FlowchartStateModel.__super__.constructor.apply(this, arguments);
        }
    });

    return FlowchartStateModel;
});
