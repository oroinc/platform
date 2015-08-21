define(function(require) {
    'use strict';

    var ShareModel;
    var BaseModel = require('oroui/js/app/models/base/model');

    /**
     * @export orosecurity/js/app/models/share-model
     */
    ShareModel = BaseModel.extend({
        defaults: {
            label: '',
            first: '',
            className: '',
            gridName: ''
        }
    });

    return ShareModel;
});
