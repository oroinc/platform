/* global define */
define(['underscore', 'backbone', 'oro/dialog-widget', 'oro/workflow-management/helper'],
function(_, Backbone, DialogWidget, Helper) {
    'use strict';

    var $ = Backbone.$;

    /**
     * @export  oro/workflow-management/transition/view/edit
     * @class   oro.WorkflowManagement.TransitionEditView
     * @extends Backbone.View
     */
    return Backbone.View.extend({
        attributes: {
            'class': 'widget-content'
        },

        options: {
            template: null,
            workflow: null,
            step_from: ''
        },

        initialize: function() {
            var template = this.options.template || $('#transition-form-template').html();
            this.template = _.template(template);
            this.widget = null;
        },

        onStepAdd: function() {
            var formData = Helper.getFormData(this.widget.form);

            if (!this.model.get('name')) {
                this.model.set('name', Helper.getNameByString(formData.label, 'transition_'));
            }
            this.model.set('label', formData.label);
            this.model.set('step_to', formData.step_to);
            this.model.set('display_type', formData.display_type);
            this.model.set('message', formData.message);

            this.trigger('transitionAdd', this.model, formData.step_from);
            this.widget.remove();
        },

        render: function() {
            var data = this.model.toJSON();
            var steps = this.options.workflow.get('steps').models;
            data.stepFrom = this.options.step_from ? this.options.step_from.get('name') : '';
            data.allowedStepsFrom = steps;
            data.allowedStepsTo = steps.slice(1);

            this.$el.append(this.template(data));

            this.widget = new DialogWidget({
                'title': this.model.get('name').length ? 'Edit transition' : 'Add new transition',
                'el': this.$el,
                'stateEnabled': false,
                'incrementalPosition': false,
                'dialogOptions': {
                    'close': _.bind(this.remove, this),
                    'width': 600,
                    'modal': true
                }
            });
            this.widget.render();

            // Disable widget submit handler and set our own instead
            this.widget.form.off('submit');
            this.widget.form.validate({
                'submitHandler': _.bind(this.onStepAdd, this)
            });

            return this;
        }
    });
});
