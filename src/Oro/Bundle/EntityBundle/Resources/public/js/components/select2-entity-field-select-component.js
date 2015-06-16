define(function (require) {
    'use strict';
    var Select2EntityFieldSelectComponent,
        EntityFieldUtil = require('../entity-field-select-util'),
        Select2EntityFieldComponent = require('./select2-entity-field-component');
    Select2EntityFieldSelectComponent = Select2EntityFieldComponent.extend({
        processExtraConfig: function (select2Config, params) {
            Select2EntityFieldSelectComponent.__super__.processExtraConfig(select2Config, params, EntityFieldUtil);
            select2Config.collapsibleResults = true;
            select2Config.data = function () {
                return {more: false, results: params.$el.data('entity-field-util')._getData() };
            }
            return select2Config;
        }
    });
    return Select2EntityFieldSelectComponent;
});
