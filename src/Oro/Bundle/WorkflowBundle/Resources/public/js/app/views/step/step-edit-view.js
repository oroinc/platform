define(function(require) {
    'use strict';

    var StepEditView;
    var _ = require('underscore');
    var $ = require('jquery');
    var __ = require('orotranslation/js/translator');
    var BaseView = require('oroui/js/app/views/base/view');
    var DialogWidget = require('oro/dialog-widget');
    var helper = require('oroworkflow/js/tools/workflow-helper');
    var TransitionsListView = require('../transition/transition-list-view');

    StepEditView = BaseView.extend({
        attributes: {
            'class': 'widget-content'
        },

        options: {
            template: null,
            transitionListContainerEl: '.transitions-list-container',
            workflow: null
        },

        listen: {
            'destroy model': 'remove'
        },

        /**
         * @inheritDoc
         */
        constructor: function StepEditView() {
            StepEditView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            var template = this.options.template || $('#step-form-template').html();
            this.template = _.template(template);
            this.widget = null;
        },

        onStepAdd: function() {
            var formData = helper.getFormData(this.widget.form);
            var order = parseInt(formData.order);

            if (!this.model.get('name')) {
                this.model.set('name', helper.getNameByString(formData.label, 'step_'));
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
            StepEditView.__super__.remove.call(this);
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
                    title: title,
                    el: this.$el,
                    stateEnabled: false,
                    incrementalPosition: false,
                    dialogOptions: {
                        close: _.bind(this.onCancel, this),
                        width: 800,
                        modal: true
                    }
                });
                this.widget.render();
            } else {
                this.widget._adoptWidgetActions();
            }

            // Disable widget submit handler and set our own instead
            this.widget.form.off('submit');
            this.widget.form.validate({
                submitHandler: _.bind(this.onStepAdd, this),
                ignore: '[type="hidden"]',
                highlight: function(element) {
                    var tabContent = $(element).closest('.tab-pane');
                    if (tabContent.is(':hidden')) {
                        tabContent
                            .closest('.oro-tabs')
                            .find('[href="#' + tabContent.prop('id') + '"]')
                            .click();
                    }
                }
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

    return StepEditView;
});
