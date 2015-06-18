define(function (require) {
    'use strict';
    var Select2AclUserMultiselectComponent,
        Select2AclUserAutocompleteComponent = require('oroform/js/app/components/select2-acl-user-autocomplete-component');
    Select2AclUserMultiselectComponent = Select2AclUserAutocompleteComponent.extend({
        processExtraConfig: function (select2Config, params) {
            Select2AclUserMultiselectComponent.__super__.processExtraConfig(select2Config, params);
            select2Config.multiple = true;
            return select2Config;
        }
    });
    return Select2AclUserMultiselectComponent;
});
