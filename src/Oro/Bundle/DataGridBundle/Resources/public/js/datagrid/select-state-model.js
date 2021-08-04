define(function(require) {
    'use strict';

    const _ = require('underscore');
    const BaseModel = require('oroui/js/app/models/base/model');

    const SelectStateModel = BaseModel.extend({
        defaults: {
            inset: true,
            rows: []
        },

        /**
         * @inheritdoc
         */
        constructor: function SelectStateModel(attrs, options) {
            SelectStateModel.__super__.constructor.call(this, attrs, options);
        },

        addRow: function(model) {
            const id = model.get('id');
            this.set('rows', _.uniq(this.attributes.rows.concat(id)));
            return this;
        },

        removeRow: function(model) {
            const id = model.get('id');
            this.set('rows', _.without(this.attributes.rows, id));
            return this;
        },

        hasRow: function(model) {
            const id = model.get('id');
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
