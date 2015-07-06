define(function(require) {
    'use strict';

    var AttributeCollection,
        BaseCollection = require('oroui/js/app/models/base/collection'),
        AttributeModel = require('./attribute-model');

    AttributeCollection = BaseCollection.extend({
        model: AttributeModel
    });

    return AttributeCollection;
});
