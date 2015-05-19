define(function (require) {
    'use strict';
    var BaseView = require('./jsplumb/overlay'),
        JsplumbAreaView = require('./jsplumb/area'),
        TransitionOverlayView;

    TransitionOverlayView = BaseView.extend({
        template: require('tpl!oroworkflow/templates/flowchart/transition.html'),

        initialize: function (options) {
            if (!(options.areaView instanceof JsplumbAreaView)) {
                throw new Error('areaView options is required and must be a JsplumbAreaView');
            }
            this.areaView = options.areaView;
            this.stepFrom = options.stepFrom;
            TransitionOverlayView.__super__.initialize.apply(this, arguments);
        },

        events: {
            'click .workflow-step-edit': 'triggerEditTransition',
            'click .workflow-step-clone': 'triggerCloneTransition',
            'click .workflow-step-delete': 'triggerDeleteTransition'
        },

        triggerDeleteTransition: function (e) {
            e.preventDefault();
            this.areaView.model.trigger('requestRemoveTransition', this.model);
        },

        triggerEditTransition: function (e) {
            e.preventDefault();
            this.areaView.model.trigger('requestEditTransition', this.model, this.stepFrom);
        },

        triggerCloneTransition: function (e) {
            e.preventDefault();
            this.areaView.model.trigger('requestCloneTransition', this.model, this.stepFrom);
        }

    });

    return TransitionOverlayView;
});
