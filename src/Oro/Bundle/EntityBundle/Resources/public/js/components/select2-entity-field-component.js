define(function (require) {
    'use strict';
    var Select2EntityFieldComponent,
        $ = require('jquery'),
        _ = require('underscore'),
        __ = require('orotranslation/js/translator'),
        EntityFieldUtil = require(),
        Select2Component = require('oroform/js/app/components/select2-component');
    Select2EntityFieldComponent = Select2Component.extend({
        processExtraConfig: function (select2Config, params, EntityFieldUtil) {
            if(typeof EntityFieldUtil !== 'function') {
                throw new Error('EntityFieldUtil should be defined in child class.')
            }
            Select2EntityFieldComponent.__super__.processExtraConfig(select2Config, params);
            params.$el.data('entity-field-util', new EntityFieldUtil(params.$el));
            if (select2Config.entities) {
                params.$el.data('entity-field-util').findEntity = function (entity) {
                    return _.findWhere(select2Config.entities, {name: entity});
                };
            }
            var formatContext = {
                getFieldData: function (fieldId) {
                    return params.$el.data('entity-field-util').getFieldData(fieldId);
                },
                splitFieldId: function (fieldId) {
                    return params.$el.data('entity-field-util').splitFieldId(fieldId);
                }
            };
            select2Config.formatContext = function () {
                return formatContext;
            };

            return select2Config;
        }
    });
    return Select2EntityFieldComponent;
});
