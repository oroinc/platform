define(function (require) {
    'use strict';
    var Select2EntityFieldChoiseComponent,
        EntityFieldUtil = require('oroentity/js/entity-field-choice-util'),
        Select2EntityFieldComponent = require('oro/select2-entity-field-component');
    Select2EntityFieldChoiseComponent = Select2EntityFieldComponent.extend({
        initialize: function (options) {
            this.util = new EntityFieldUtil(options._sourceElement);
            Select2EntityFieldChoiseComponent.__super__.initialize.call(this, options);
        }
    });
    return Select2EntityFieldChoiseComponent;
});
