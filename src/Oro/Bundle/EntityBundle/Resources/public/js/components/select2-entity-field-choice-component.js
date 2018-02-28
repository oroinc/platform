define(function(require) {
    'use strict';

    var Select2EntityFieldChoiceComponent;
    var EntityFieldUtil = require('oroentity/js/entity-field-choice-util');
    var Select2EntityFieldComponent = require('oro/select2-entity-field-component');

    Select2EntityFieldChoiceComponent = Select2EntityFieldComponent.extend({
        /**
         * @inheritDoc
         */
        constructor: function Select2EntityFieldChoiceComponent() {
            Select2EntityFieldChoiceComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.util = new EntityFieldUtil(options._sourceElement);
            Select2EntityFieldChoiceComponent.__super__.initialize.call(this, options);
        }
    });

    return Select2EntityFieldChoiceComponent;
});
