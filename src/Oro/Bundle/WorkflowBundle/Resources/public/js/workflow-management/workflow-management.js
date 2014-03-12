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
    'oroui/js/app',
    'oroui/js/delete-confirmation',
    'oroentity/js/fields-loader'
],
function(_, Backbone, messanger, __,
     StepsListView,
     StepModel,
     TransitionModel,
     StepEditView,
     TransitionEditForm,
     Helper,
     Navigation,
     app,
     Confirmation
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
            this.addStartStep();
            this.initStartStepSelector();

            this.stepListView = new StepsListView({
                el: this.$(this.options.stepsEl),
                collection: this.model.get('steps'),
                workflow: this.model
            });
            this.$entitySelectEl = this.$('[name$="[related_entity]"]');
            this.initEntityFieldsLoader();

            this.listenTo(this.model, 'requestAddTransition', this.addNewStepTransition);
            this.listenTo(this.model, 'requestEditStep', this.openManageStepForm);
            this.listenTo(this.model, 'requestCloneStep', this.cloneStep);
            this.listenTo(this.model, 'requestRemoveStep', this.removeStep);
            this.listenTo(this.model.get('steps'), 'destroy', this.onStepRemove);

            this.listenTo(this.model, 'requestRemoveTransition', this.removeTransition);
            this.listenTo(this.model, 'requestCloneTransition', this.cloneTransition);
            this.listenTo(this.model, 'requestEditTransition', this.openManageTransitionForm);

            this.listenTo(this.model, 'change:entity', this.resetWorkflow);

            this.$saveBtn = $(this.options.saveBtnEl);
            this.$saveBtn.on('click', _.bind(this.saveConfiguration, this));
            this.model.url = this.$saveBtn.data('url');
        },

        initStartStepSelector: function() {
            var getSteps = _.bind(function(query) {
                var steps = [];
                _.each(this.model.get('steps').models, function(step) {
                    if (!step.get('_is_start') && step.get('label').indexOf(query.term) !== -1) {
                        steps.push({
                            'id': step.get('name'),
                            'text': step.get('label')
                        });
                    }
                }, this);

                query.callback({results: steps});
            }, this);

            this.$startStepEl = this.$('[name="start_step"]');

            var select2Options = {
                'allowClear': true,
                'query': getSteps,
                'placeholder': __('Choose step...'),
                'initSelection' : _.bind(function (element, callback) {
                    var startStep = this.model.getStepByName(element.val());
                    callback({
                        id: startStep.get('name'),
                        text: startStep.get('label')
                    });
                }, this)
            };

            this.$startStepEl.select2(select2Options);
        },

        initEntityFieldsLoader: function() {
            var confirm = new Confirmation({
                title: __('Change Entity Confirmation'),
                okText: __('Yes, I Agree'),
                content: __('Are you sure you want to change entity?')
            });
            confirm.on('ok', _.bind(function() {
                this.model.set('entity', this.$entitySelectEl.val());
            }, this));
            confirm.on('cancel', _.bind(function() {
                this.$entitySelectEl.select2('val', this.model.get('entity'));
            }, this));

            this.$entitySelectEl.fieldsLoader({
                router: 'oro_workflow_api_rest_entity_get',
                routingParams: {"with-relations": 1, "with-entity-details": 1, "deep-level": 2},
                confirm: confirm,
                requireConfirm: _.bind(function () {
                     return this.model.get('steps').length > 1 &&
                         (this.model.get('transitions').length
                            + this.model.get('transition_definitions').length
                            + this.model.get('attributes').length) > 0;
                }, this)
            });
            this.$entitySelectEl.on('change', _.bind(function() {
                if (!this.model.get('entity')) {
                    this.model.set('entity', this.$entitySelectEl.val());
                }
            }, this));

            this.$entitySelectEl.on('fieldsloadercomplete', _.bind(function(e) {
                this.createPathMapping($(e.target).data('fields'));
            }, this));
        },

        addStartStep: function() {
            this.model.get('steps').add(this._getStartStep());
        },

        resetWorkflow: function() {
            var resetCollection = function(collection) {
                if (collection.length) {
                    for (var i = collection.length -1; i > -1; i--) {
                        collection.at(i).destroy();
                    }
                }
            };

            this.model.set('start_step', null);
            resetCollection(this.model.get('attributes'));
            resetCollection(this.model.get('steps'));
            resetCollection(this.model.get('transition_definitions'));
            resetCollection(this.model.get('transitions'));

            this.addStartStep();
        },

        createPathMapping: function(fields) {
            var rootAttributeName = this.model.get('entity_attribute');
            var mapping = {};

            var addMapping = _.bind(function(field, parentPropertyPath, parentFieldId) {
                var propertyPath = parentPropertyPath + '.' + field.name;
                var fieldIdParts = [];

                if (parentFieldId) {
                    fieldIdParts.push(parentFieldId);
                }

                var fieldIdName = field.name;
                if (field.hasOwnProperty('related_entity_name')) {
                    fieldIdName += '+' + field.related_entity_name;
                }
                fieldIdParts.push(fieldIdName);

                if (!field.hasOwnProperty('related_entity_name')) {
                    mapping[propertyPath] = fieldIdParts.join('::');
                }

                if (field.hasOwnProperty('related_entity_fields')) {
                    _.each(field.related_entity_fields, function(relatedField) {
                        addMapping(relatedField, propertyPath, fieldIdParts.join('::'));
                    });
                }
            });

            _.each(fields, function(field) {
                addMapping(field, rootAttributeName, "");
            });

            this.model.setPropertyPathToFieldIdMapping(mapping);
        },

        saveConfiguration: function(e) {
            e.preventDefault();

            if (this.model.get('steps').length == 1 || this.model.get('transitions').length == 0) {
                messanger.notificationFlashMessage(
                    'error',
                    __('Could not save workflow. Please add steps and transitions.')
                );
                return;
            }

            var navigation = Navigation.isEnabled() ? Navigation.getInstance() : null;

            var formData = Helper.getFormData(this.$el);
            formData.steps_display_ordered = formData.hasOwnProperty('steps_display_ordered');

            if (!this.model.get('name')) {
                this.model.set('name', Helper.getNameByString(formData.label, 'workflow_'));
            }
            this.model.set('label', formData.label);
            this.model.set('steps_display_ordered', formData.steps_display_ordered);
            this.model.set('entity', formData.related_entity);
            this.model.set('start_step', formData.start_step);

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
                'error': function(model, response) {
                    if (navigation) {
                        navigation.hideLoading();
                    }
                    if (app.debug && !_.isUndefined(console) && !_.isUndefined(response.responseJSON.error)) {
                        console.error(response.responseJSON.error);
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

        cloneTransition: function(transition, step) {
            var clonedTransition = this.model.cloneTransition(transition);
            if (!_.isUndefined(step)) {
                step.getAllowedTransitions(this.model).add(clonedTransition);
            }
        },

        removeTransition: function(model) {
            this._removeHandler(model, __('Are you sure you want to delete this transition?'));
        },

        _removeHandler: function(model, message) {
            var confirm = new Confirmation({
                content: message
            });
            confirm.on('ok', function () {
                model.destroy();
            });
            confirm.open();
        },

        addNewStepTransition: function(step) {
            this.openManageTransitionForm(new TransitionModel(), step);
        },

        openManageTransitionForm: function(transition, step_from) {
            if (this.model.get('steps').length == 1) {
                messanger.notificationFlashMessage('error', __('At least one step should be added to add transition.'));
                return;
            }
            if (!this.$entitySelectEl.val()) {
                messanger.notificationFlashMessage('error', __('Related entity must be selected to add transition.'));
                return;
            }

            var transitionEditView = new TransitionEditForm({
                'model': transition,
                'workflow': this.model,
                'step_from': step_from,
                'entity_select_el': this.$entitySelectEl,
                'entities': this.options.entities,
                'workflowContainer': this.$el
            });
            transitionEditView.on('transitionAdd', this.addTransition, this);
            transitionEditView.render();
        },

        addTransition: function(transition, stepFrom) {
            if (!this.model.get('transitions').get(transition.cid)) {
                if (_.isString(stepFrom)) {
                    stepFrom = this.model.getStepByName(stepFrom);
                }
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
            if (!this.$entitySelectEl.val()) {
                messanger.notificationFlashMessage('error', __('Related entity must be selected to add step.'));
                return;
            }

            var stepEditView = new StepEditView({
                'model': step,
                'workflow': this.model,
                'workflowContainer': this.$el
            });
            stepEditView.on('stepAdd', this.addStep, this);
            stepEditView.render();
        },

        cloneStep: function(step) {
            this.model.cloneStep(step);
        },

        addStep: function(step) {
            if (!this.model.get('steps').get(step.cid)) {
                this.model.get('steps').add(step);
            }
        },

        removeStep: function(model) {
            this._removeHandler(model, __('Are you sure you want to delete this step?'));
        },

        onStepRemove: function(step) {
            //Deselect start_step if it was removed
            if (this.$startStepEl.val() == step.get('name')) {
                this.$startStepEl.select2('val', '');
            }

            var removeTransitions = function (models) {
                if (models.length) {
                    for (var i = models.length - 1; i > -1; i--) {
                        models[i].destroy();
                    }
                }
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
