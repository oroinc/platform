define(function(require) {
    'use strict';

    var FlowchartStateModel;
    var BaseModel = require('oroui/js/app/models/base/model');

    FlowchartStateModel = BaseModel.extend({
        defaults: {
            transitionLabelsVisible: true
        }
    });

    return FlowchartStateModel;
});
