define(function(require) {
    'use strict';

    var WidgetPickerModel;
    var BaseModel = require('oroui/js/app/models/widget-picker/widget-picker-model');
    var __ = require('orotranslation/js/translator');

    WidgetPickerModel = BaseModel.extend({

        initialize: function(options) {
            if (options.description) {
                this.set('description', __(options.description));
            }
            WidgetPickerModel.__super__.initialize.apply(this, arguments);
        }
    });

    return WidgetPickerModel;
});
