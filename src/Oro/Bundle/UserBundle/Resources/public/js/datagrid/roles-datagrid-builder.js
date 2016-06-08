define(function(require) {
    "use strict";

    var rolesDatagridBuilder;
    var _ = require('underscore');
    var BaseCollection = require('oroui/js/app/models/base/collection');

    rolesDatagridBuilder = {
        processDatagridOptions: function(deferred, options) {
            _.each(options.data.data, function(item) {
                item.permissions = new BaseCollection(item.permissions);
            });
            deferred.resolve();
        },

        init: function(deferred, options) {
            deferred.resolve();
        }
    };

    return rolesDatagridBuilder;
});
