define(function(require) {
    'use strict';

    const BaseModel = require('oroui/js/app/models/base/model');

    const FlowchartStateModel = BaseModel.extend({
        defaults: {
            transitionLabelsVisible: true
        },

        /**
         * @inheritDoc
         */
        constructor: function FlowchartStateModel(...args) {
            FlowchartStateModel.__super__.constructor.apply(this, args);
        }
    });

    return FlowchartStateModel;
});
