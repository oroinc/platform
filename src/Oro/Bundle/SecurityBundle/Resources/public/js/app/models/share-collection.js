define(function(require) {
    'use strict';

    var ShareCollection;
    var ShareModel = require('./share-model');
    var BaseCollection = require('oroui/js/app/models/base/collection');

    /**
     * @export orosecurity/js/app/models/share-collection
     */
    ShareCollection = BaseCollection.extend({
        model: ShareModel
    });

    return ShareCollection;
});
