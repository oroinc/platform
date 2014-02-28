/* global define */
define(['underscore', 'backbone', 'oro/dialog-widget', 'oro/workflow-management/helper'],
function(_, Backbone, DialogWidget, Helper) {
    'use strict';

    var $ = Backbone.$;

    /**
     * @export  oro/workflow-management/step/view/edit
     * @class   oro.WorkflowManagement.StepEditView
     * @extends Backbone.View
     */
    return Backbone.View.extend({
        attributes: {
            'class': 'widget-content'
        },

        options: {
            template: null
        },

        initialize: function() {
            var template = this.options.template || $('#step-form-template').html();
            this.template = _.template(template);
            this.widget = null;
        },

        onStepAdd: function() {
            var formData = Helper.getFormData(this.widget.form);
            var order = parseInt(formData.order);

            if (!this.model.get('name')) {
                this.model.set('name', Helper.getNameByString(formData.label, 'step'));
            }
            this.model.set('order', order > 0 ? order : 0);
            this.model.set('is_final', formData.hasOwnProperty('is_final'));
            this.model.set('label', formData.label);

            this.trigger('stepAdd', this.model);
            this.widget.remove();
        },

        render: function() {
            this.$el.append(
                this.template(this.model.toJSON())
            );

            this.widget = new DialogWidget({
                'title': this.model.get('name').length ? 'Edit step' : 'Add new step',
                'el': this.$el,
                'stateEnabled': false,
                'dialogOptions': {
                    'close': _.bind(this.remove, this),
                    'width': 600
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
