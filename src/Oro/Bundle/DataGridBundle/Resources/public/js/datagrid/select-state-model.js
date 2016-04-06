define(function(require) {
    'use strict';

    var SelectStateModel;
    var _ = require('underscore');
    var BaseModel = require('oroui/js/app/models/base/model');

    SelectStateModel = BaseModel.extend({
        defaults: {
            inset: true,
            rows: []
        },

        addRow: function(model) {
            var id = model.get('id');
            this.set('rows', _.uniq(this.attributes.rows.concat(id)));
            return this;
        },

        removeRow: function(model) {
            var id = model.get('id');
            this.set('rows', _.without(this.attributes.rows, id));
            return this;
        },

        hasRow: function(model) {
            var id = model.get('id');
            return this.get('rows').indexOf(id) !== -1;
        },

        isEmpty: function() {
            return this.get('rows').length === 0;
        },

        reset: function(options) {
            this.set('rows', []);
            this.set('inset', !_.isObject(options) || options.inset !== false);
            return this;
        }
    });

    return SelectStateModel;
});
