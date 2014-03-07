/* global define */
define([
    'underscore', 'backbone', 'oroui/js/messenger', 'orotranslation/js/translator',
    'oroworkflow/js/workflow-management/step/view/list',
    'oroworkflow/js/workflow-management/step/model',
    'oroworkflow/js/workflow-management/transition/model',
    'oroworkflow/js/workflow-management/step/view/edit',
    'oroworkflow/js/workflow-management/transition/view/edit',
    'oroworkflow/js/workflow-management/helper',
    'oronavigation/js/navigation',
    'oroentity/js/fields-loader'
],
function(_, Backbone, messanger, __,
     StepsListView,
     StepModel,
     TransitionModel,
     StepEditView,
     TransitionEditForm,
     Helper,
     Navigation
) {
    'use strict';

    var $ = Backbone.$;

    /**
     * @export  oroworkflow/js/workflow-management
     * @class   oro.WorkflowManagement
     * @extends Backbone.View
     */
    return Backbone.View.extend({
        events: {
            'click .add-step-btn': 'addNewStep',
            'click .add-transition-btn': 'addNewTransition'
        },

        options: {
            stepsEl: null,
            saveBtnEl: null,
            model: null,
            entities: []
        },

        initialize: function() {
            this.model.get('steps').add(this._getStartStep());
            this.stepListView = new StepsListView({
                el: this.$(this.options.stepsEl),
                collection: this.model.get('steps'),
                workflow: this.model
            });
            this.$entitySelectEl = this.$('[name$="[related_entity]"]');
            this.initEntityFieldsLoader();

            this.listenTo(this.model.get('steps'), 'requestAddTransition', this.addNewStepTransition);
            this.listenTo(this.model.get('steps'), 'requestEdit', this.openManageStepForm);
            this.listenTo(this.model.get('steps'), 'requestClone', this.cloneStep);
            this.listenTo(this.model.get('steps'), 'destroy', this.onStepRemove);

            this.listenTo(this.model.get('transitions'), 'requestEdit', this.openManageTransitionForm);

            this.$saveBtn = $(this.options.saveBtnEl);
            this.$saveBtn.on('click', _.bind(this.saveConfiguration, this));
            this.model.url = this.$saveBtn.data('url');
        },

        initEntityFieldsLoader: function() {
            this.$entitySelectEl.fieldsLoader({
                router: 'oro_api_get_entity_fields',
                routingParams: {"with-relations": 1,"with-entity-details": 1, "deep-level": 2}
            });

            this.$entitySelectEl.on('fieldsloadercomplete', _.bind(function(e) {
                this.createPathMapping($(e.target).data('fields'));
            }, this));
        },

        createPathMapping: function(fields) {
            var rootAttributeName = this.model.get('entity_attribute');
            var mapping = {};

            var addMapping = _.bind(function(field, parent) {
                var propertyPath = parent + '.' + field.name;
                var fieldIdParts = [];

                if (mapping.hasOwnProperty(parent)) {
                    fieldIdParts.push(mapping[parent]);
                }

                var fieldIdName = field.name;
                if (field.hasOwnProperty('related_entity_name')) {
                    fieldIdName += '+' + field.related_entity_name;
                }
                fieldIdParts.push(fieldIdName);

                mapping[propertyPath] = fieldIdParts.join('::');

                if (field.hasOwnProperty('related_entity_fields')) {
                    _.each(field.related_entity_fields, function(relatedField) {
                        addMapping(relatedField, propertyPath);
                    });
                }
            });

            _.each(fields, function(field) {
                addMapping(field, rootAttributeName);
            });

            this.model.setPropertyPathToFieldIdMapping(mapping);
        },

        saveConfiguration: function(e) {
            e.preventDefault();
            var navigation = Navigation.isEnabled() ? Navigation.getInstance() : null;

            var formData = Helper.getFormData(this.$el);
            formData.steps_display_ordered = formData.hasOwnProperty('steps_display_ordered');

            if (!this.model.get('name')) {
                this.model.set('name', Helper.getNameByString(formData.label, 'workflow_'));
            }
            this.model.set('label', formData.label);
            this.model.set('steps_display_ordered', formData.steps_display_ordered);
            this.model.set('entity', formData.related_entity);

            if (navigation) {
                navigation.showLoading();
            }
            this.model.save(null, {
                'success': function() {
                    if (navigation) {
                        navigation.hideLoading();
                    }
                    messanger.notificationFlashMessage('success', __('Workflow saved.'));
                },
                'error': function() {
                    if (navigation) {
                        navigation.hideLoading();
                    }
                    messanger.notificationFlashMessage('error', __('Could not save workflow.'));
                }
            });
        },

        _getStartStep: function() {
            var startStepModel = new StepModel({
                'name': 'step:starting_point',
                'label': __('(Starting point)'),
                'order': -1,
                '_is_start': true
            });

            startStepModel
                .getAllowedTransitions(this.model)
                .reset(this.model.getStartTransitions());

            return startStepModel;
        },

        renderSteps: function() {
            this.stepListView.render();
        },

        addNewStep: function() {
            this.openManageStepForm(new StepModel());
        },

        addNewTransition: function() {
            this.addNewStepTransition(null);
        },

        addNewStepTransition: function(step) {
            this.openManageTransitionForm(new TransitionModel(), step);
        },

        openManageTransitionForm: function(transition, step_from) {
            var transitionEditView = new TransitionEditForm({
                'model': transition,
                'workflow': this.model,
                'step_from': step_from,
                'entity_select_el': this.$entitySelectEl,
                'entities': this.options.entities
            });
            transitionEditView.on('transitionAdd', this.addTransition, this);
            transitionEditView.render();
        },

        addTransition: function(transition, stepFromName) {
            if (!this.model.get('transitions').get(transition.cid)) {
                var stepFrom = this.model.getStepByName(stepFromName);
                transition.set('is_start', stepFrom.get('_is_start'));

                this.model
                    .get('transitions')
                    .add(transition);

                stepFrom
                    .getAllowedTransitions(this.model)
                    .add(transition);
            }
        },

        openManageStepForm: function(step) {
            var stepEditView = new StepEditView({
                'model': step
            });
            stepEditView.on('stepAdd', this.addStep, this);
            stepEditView.render();
        },

        cloneStep: function(step) {
//            var resetName = function(item) {
//                item.set('name', item.get('name') + '_clone_' + Helper.getRandomId());
//            };
//
//            var transitionsCollection = this.model.get('transitions');
//            var clonedStep = $.clone(step);
//            clonedStep.set('label', 'Clone of ' + clonedStep.get('label'));
//            var allowedTransitions = clonedStep.get('allowed_transitions');
//            allowedTransitions = [];
//            resetName(clonedStep);
//            _.each(clonedStep.getAllowedTransitions(this.model), function (transition) {
//                resetName(transition);
//                transitionsCollection.add(transition);
//                allowedTransitions.push(transition.get('name'));
//            }, this);
//            this.model.get('steps').add(clonedStep);
        },

        addStep: function(step) {
            if (!this.model.get('steps').get(step.cid)) {
                this.model.get('steps').add(step);
            }
        },

        onStepRemove: function(step) {
            var removeTransitions = function (models) {
                //Cloned because of iterator elements removing in loop
                _.each(_.clone(models), function(transition) {
                    transition.destroy();
                });
            };

            //Remove step transitions
            removeTransitions(step.getAllowedTransitions(this.model).models);
            //Remove transitions which lead into removed step
            removeTransitions(this.model.get('transitions').where({'step_to': step.get('name')}));
        },

        render: function() {
            this.renderSteps();
            return this;
        }
    });
});
