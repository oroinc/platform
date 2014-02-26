/* global define */
define(['underscore', 'backbone', 'oro/workflow-management/step/view/list', 'oro/workflow-management/step/model',
    'oro/workflow-management/transition/collection'],
function(_, Backbone, StepsListView, StepModel, TransitionCollection) {
    'use strict';

    var $ = Backbone.$;

    /**
     * @export  oro/workflow-management
     * @class   oro.WorkflowManagement
     * @extends Backbone.View
     */
    return Backbone.View.extend({
        options: {
            stepsEl: null,
            metadataFormEl: null,
            model: null
        },

        initialize: function() {
            var steps = this.model.get('steps');
            steps.add(this._getStartStep());
            this.stepListView = new StepsListView({
                el: this.$(this.options.stepsEl),
                collection: steps,
                workflow: this.model
            });
        },

        setFormMetadataToModel: function() {
            var metadataElements = {
                'label': 'label',
                'related_entity': 'relatedEntity',
                'steps_display_ordered': 'stepsDisplayOrdered'
            };

            for (var elName in metadataElements) if (metadataElements.hasOwnProperty(elName)) {
                var el = this._getFormElement(this.$el, elName);
                this.model.set(metadataElements[elName], el.val());
            }
        },

        _getStartStep: function() {
            return new StepModel({
                'label': '(Starting point)',
                'order': -1,
                '_isStart': true,
                'allowedTransitions': new TransitionCollection(this.model.getStartTransitions())
            });
        },

        renderSteps: function() {
            this.stepListView.render();
        },

        _getFormElement: function(form, name) {
            var elId = this.$el.attr('id') + '_' + name;
            return this.$('#' + elId);
        },

        render: function() {
            this.renderSteps();
        }
    });
});
