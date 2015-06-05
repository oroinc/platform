/* global define */
define(function(require) {
    'use strict';

    var TransitionDefinitionCollection,
        BaseCollection = require('oroui/js/app/models/base/collection'),
        TransitionDefinitionModel = require('./transition-definition-model');

    TransitionDefinitionCollection = BaseCollection.extend({
        model: TransitionDefinitionModel
    });

    return TransitionDefinitionCollection;
});
