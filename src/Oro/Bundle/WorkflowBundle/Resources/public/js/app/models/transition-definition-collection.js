define(function(require) {
    'use strict';

    var TransitionDefinitionCollection;
    var BaseCollection = require('oroui/js/app/models/base/collection');
    var TransitionDefinitionModel = require('./transition-definition-model');

    TransitionDefinitionCollection = BaseCollection.extend({
        model: TransitionDefinitionModel
    });

    return TransitionDefinitionCollection;
});
