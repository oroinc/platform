define(function(require) {
    'use strict';

    const {defer, findWhere, pick} = require('underscore');
    let _data = null;
    const BaseView = require('oroui/js/app/views/base/view');

    const FieldChoiceMock = BaseView.extend({
        value: '',

        entity: null,

        data: null,

        constructor: function FieldChoiceMock(options) {
            FieldChoiceMock.lastCreatedInstance = this;
            this.data = {..._data};
            spyOn(this, 'setValue').and.callThrough();
            FieldChoiceMock.__super__.constructor.call(this, options);
        },

        initialize: function(options) {
            this.entity = options.entity;
            FieldChoiceMock.__super__.initialize.call(this, options);
        },

        render: function() {
            this._deferredRender();
            defer(this._resolveDeferredRender.bind(this));
        },

        getValue: function() {
            return this.value;
        },

        setValue: function(value) {
            this.value = value;
            this.trigger('change', value ? {id: value} : null);
        },

        getData: function() {
            const entity = this.data[this.entity];
            const field = findWhere(entity.fields, {name: this.value});
            return {id: this.value, text: field.label};
        },

        getApplicableConditions: function(fieldId) {
            const entity = this.data[this.entity];
            let signature = null;
            if (!entity || !fieldId) {
                return signature;
            }

            const field = findWhere(entity.fields, {name: fieldId});
            if (field) {
                signature = pick(field, 'type', 'relationType', 'identifier');
                signature.field = field.name;
                signature.entity = entity;
            }

            return signature;
        }
    }, {
        setData: function(data) {
            _data = data;
        }
    });

    return FieldChoiceMock;
});
