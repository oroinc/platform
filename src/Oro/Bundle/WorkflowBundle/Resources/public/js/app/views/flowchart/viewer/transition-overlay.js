define(function (require) {
    'use strict';
    var BaseView = require('../jsplumb/overlay'),
        TransitionOverlayView;

    TransitionOverlayView = BaseView.extend({
        template: require('tpl!oroworkflow/templates/flowchart/viewer/transition.html')
    });

    return TransitionOverlayView;
});
