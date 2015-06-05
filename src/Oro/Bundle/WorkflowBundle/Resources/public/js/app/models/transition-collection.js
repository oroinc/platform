/* global define */
define(function(require) {
    'use strict';

    var TransitionCollection,
        BaseCollection = require('oroui/js/app/models/base/collection'),
        TransitionModel = require('./transition-model');

    TransitionCollection = BaseCollection.extend({
        model: TransitionModel
    });

    return TransitionCollection;
});
