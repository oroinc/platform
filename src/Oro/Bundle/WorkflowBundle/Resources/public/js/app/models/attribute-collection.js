/* global define */
define(function(require) {
    'use strict';

    var AttributeCollection,
        BaseCollection = require('oroui/js/app/models/base/collection'),
        AttributeModel = require('oroworkflow/js/app/models/attribute-model');

    AttributeCollection = BaseCollection.extend({
        model: AttributeModel
    });

    return AttributeCollection;
});
