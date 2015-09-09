define(function(require) {
    'use strict';

    var ShareWithDatagridModel;
    var BaseModel = require('oroui/js/app/models/base/model');

    /**
     * @export orosecurity/js/app/models/share-datagrid-model
     */
    ShareWithDatagridModel = BaseModel.extend({
        defaults: {
            label: '',
            first: '',
            className: '',
            gridName: '',
            isGranted: false
        }
    });

    return ShareWithDatagridModel;
});
