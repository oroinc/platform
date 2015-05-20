define(function (require) {
    'use strict';
    var BaseView = require('./jsplumb/overlay'),
        JsplumbAreaView = require('./jsplumb/area'),
        TransitionOverlayView;

    TransitionOverlayView = BaseView.extend({
        template: require('tpl!oroworkflow/templates/flowchart/transition.html'),

        initialize: function (options) {
            this.stepFrom = options.stepFrom;
            TransitionOverlayView.__super__.initialize.apply(this, arguments);
        },

        events: {
            'dblclick': 'triggerEditTransition',
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
