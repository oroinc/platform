define(function(require) {
    'use strict';

    var FieldChoiceMock;
    var _ = require('underscore');
    var _data = null;
    var BaseView = require('oroui/js/app/views/base/view');

    FieldChoiceMock = BaseView.extend({
        value: '',

        entity: null,

        data: null,

        constructor: function FieldChoiceMock() {
            FieldChoiceMock.lastCreatedInstance = this;
            this.data = _.clone(_data);
            spyOn(this, 'setValue').and.callThrough();
            FieldChoiceMock.__super__.constructor.apply(this, arguments);
        },

        initialize: function(options) {
            this.entity = options.entity;
            FieldChoiceMock.__super__.initialize.call(this, options);
        },

        render: function() {
            this._deferredRender();
            _.defer(this._resolveDeferredRender.bind(this));
        },

        getValue: function() {
            return this.value;
        },

        setValue: function(value) {
            this.value = value;
            this.trigger('change', value ? {id: value} : null);
        },

        getData: function() {
            var entity = this.data[this.entity];
            var field = _.findWhere(entity.fields, {name: this.value});
            return {id: this.value, text: field.label};
        },

        getApplicableConditions: function(fieldId) {
            var entity = this.data[this.entity];
            var signature = null;
            if (!entity || !fieldId) {
                return signature;
            }

            var field = _.findWhere(entity.fields, {name: fieldId});
            if (field) {
                signature = _.pick(field, 'type', 'relationType', 'identifier');
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
