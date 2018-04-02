define(function(require) {
    'use strict';

    var Select2RelationComponent;
    var Select2Component = require('oro/select2-component');

    Select2RelationComponent = Select2Component.extend({
        /**
         * @inheritDoc
         */
        constructor: function Select2RelationComponent() {
            Select2RelationComponent.__super__.constructor.apply(this, arguments);
        },

        makeQuery: function(query, configs) {
            return [query, configs.target_entity, configs.target_field].join(',');
        }
    });

    return Select2RelationComponent;
});
