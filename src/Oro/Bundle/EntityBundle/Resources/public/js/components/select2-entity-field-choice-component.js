define(function (require) {
    'use strict';
    var Select2EntityFieldChoiseComponent,
        EntityFieldUtil = require('../entity-field-choice-util'),
        Select2EntityFieldComponent = require('./select2-entity-field-component');
    Select2EntityFieldChoiseComponent = Select2EntityFieldComponent.extend({
        processExtraConfig: function (select2Config, params) {
            Select2EntityFieldSelectComponent.__super__.processExtraConfig(select2Config, params, EntityFieldUtil);
            return select2Config;
        }
    });
    return Select2EntityFieldChoiseComponent;
});
