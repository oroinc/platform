/* global define */
define(['underscore', 'orotranslation/js/translator', 'backbone', 'oroui/js/messenger', 'oro/dialog-widget',
    'oroworkflow/js/workflow-management/helper',
    'oroui/js/mediator', 'oroworkflow/js/workflow-management/transition/view/list', 'jquery.validate'
],
function(_, __, Backbone, messenger, DialogWidget, Helper, mediator, TransitionsListView) {
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

        options: {
            template: null,
            transitionListContainerEl: '.transitions-list-container',
            workflow: null
        },

        initialize: function() {
            this.listenTo(this.model, 'destroy', this.remove);

            var template = this.options.template || $('#step-form-template').html();
            this.template = _.template(template);
            this.widget = null;
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
            this.model.set('_is_clone', false);

            this.trigger('stepAdd', this.model);

            this.widget.remove();
        },

        onCancel: function() {
            if (this.model.get('_is_clone')) {
                this.model.destroy();
            } else {
                this.remove();
            }
        },

        remove: function() {
            if (this.transitionsListView) {
                this.transitionsListView.remove();
            }
            Backbone.View.prototype.remove.call(this);
        },

        renderTransitions: function() {
            this.transitionsListView = new TransitionsListView({
                el: this.$el.find(this.options.transitionListContainerEl),
                workflow: this.options.workflow,
                collection: this.model.getAllowedTransitions(this.options.workflow),
                stepFrom: this.model
            });
            this.transitionsListView.render();
        },

        renderWidget: function() {
            if (!this.widget) {
                var title = this.model.get('name') ? __('Edit step') : __('Add new step');
                if (this.model.get('_is_clone')) {
                    title = __('Clone step');
                }

                this.widget = new DialogWidget({
                    'title': title,
                    'el': this.$el,
                    'stateEnabled': false,
                    'incrementalPosition': false,
                    'dialogOptions': {
                        'close': _.bind(this.onCancel, this),
                        'width': 800,
                        'modal': true
                    }
                });
                this.listenTo(this.widget, 'renderComplete', function(el) {
                    mediator.trigger('layout.init', el);
                });
                this.widget.render();
            } else {
                this.widget._adoptWidgetActions();
            }

            // Disable widget submit handler and set our own instead
            this.widget.form.off('submit');
            this.widget.form.validate({
                'submitHandler': _.bind(this.onStepAdd, this)
            });
        },

        render: function() {
            var data = this.model.toJSON();
            data.transitionsAllowed = (this.options.workflow.get('steps').length > 1 && this.model.get('name'));
            this.$el.html(this.template(data));

            if (data.transitionsAllowed) {
                this.renderTransitions();
            }
            this.renderWidget();

            return this;
        }
    });
});
