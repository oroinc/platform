/* global define */
define(['underscore', 'backbone', 'oro/dialog-widget', 'oroworkflow/js/workflow-management/helper',
    'oroui/js/layout', 'oroworkflow/js/workflow-management/transition/view/list',
    'oroworkflow/js/workflow-management/transition/model'
],
function(_, Backbone, DialogWidget, Helper, layout, TransitionsListView, TransitionModel) {
    'use strict';

    var $ = Backbone.$;

    /**
     * @export  oroworkflow/js/workflow-management/step/view/edit
     * @class   oro.WorkflowManagement.StepEditView
     * @extends Backbone.View
     */
    return Backbone.View.extend({
        attributes: {
            'class': 'widget-content'
        },

        events: {
            'click .add-transition': 'addStepTransition'
        },

        options: {
            template: null,
            transitionListContainerEl: '.transitions-list-container',
            workflow: null
        },

        initialize: function() {
            var template = this.options.template || $('#step-form-template').html();
            this.template = _.template(template);
            this.widget = null;
            this.addedTransitions = [];
        },

        onStepAdd: function() {
            var formData = Helper.getFormData(this.widget.form);
            var order = parseInt(formData.order);

            if (!this.model.get('name')) {
                this.model.set('name', Helper.getNameByString(formData.label, 'step_'));
            }
            this.model.set('order', order > 0 ? order : 0);
            this.model.set('is_final', formData.hasOwnProperty('is_final'));
            this.model.set('label', formData.label);

            this.trigger('stepAdd', this.model);
            this.widget.remove();
        },

        addStepTransition: function() {
            var transition = new TransitionModel();
            this.addedTransitions.push(transition);
            this.options.workflow.trigger('requestEditTransition', transition, this.model);
        },

        remove: function() {
            this.transitionsListView.remove();
            Backbone.View.prototype.remove.call(this);
        },

        onCancel: function() {
            _.each(this.addedTransitions, function(transition) {
                transition.destroy();
            });
        },

        render: function() {
            this.$el.append(
                this.template(this.model.toJSON())
            );
            this.transitionsListView = new TransitionsListView({
                el: this.$el.find(this.options.transitionListContainerEl),
                workflow: this.options.workflow,
                collection: this.model.getAllowedTransitions(this.options.workflow),
                stepFrom: this.model
            });
            this.transitionsListView.render();

            this.widget = new DialogWidget({
                'title': this.model.get('name') ? 'Edit step' : 'Add new step',
                'el': this.$el,
                'stateEnabled': false,
                'incrementalPosition': false,
                'dialogOptions': {
                    'close': _.bind(this.remove, this),
                    'width': 600,
                    'modal': true
                }
            });
            this.widget.on('renderComplete', function(el) {
                layout.init(el);
            });
            this.widget.render();

            // Disable widget submit handler and set our own instead
            this.widget.form.off('submit');
            this.widget.form.validate({
                'submitHandler': _.bind(this.onStepAdd, this)
            });
            this.listenTo(this.widget, 'adoptedFormResetClick', this.onCancel);

            return this;
        }
    });
});
