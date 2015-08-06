define(['oro/select2-component'], function(Select2Component) {
    'use strict';

    var Select2EmailRecipientsComponent = Select2Component.extend({
        preConfig: function(config) {
            var config = Select2EmailRecipientsComponent.__super__.preConfig.apply(this, arguments);

            config.ajax.results = function(data) {
                return data;
            };

            return config;
        }
    });

    return Select2EmailRecipientsComponent;
});
