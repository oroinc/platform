define(function(require) {
    'use strict';

    var ShareWithDatagridCollection;
    var ShareWithDatagridModel = require('./share-with-datagrid-model');
    var BaseCollection = require('oroui/js/app/models/base/collection');

    /**
     * @export orosecurity/js/app/models/share-datagrid-collection
     */
    ShareWithDatagridCollection = BaseCollection.extend({
        model: ShareWithDatagridModel
    });

    return ShareWithDatagridCollection;
});
