define(function(require) {
    'use strict';

    var AttributeCollection;
    var BaseCollection = require('oroui/js/app/models/base/collection');
    var AttributeModel = require('./attribute-model');

    AttributeCollection = BaseCollection.extend({
        model: AttributeModel
    });

    return AttributeCollection;
});
