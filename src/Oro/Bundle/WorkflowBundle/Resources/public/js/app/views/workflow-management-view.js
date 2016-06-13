define(function(require) {
    'use strict';

    var WorkflowManagementView;
    var _ = require('underscore');
    var $ = require('jquery');
    var __ = require('orotranslation/js/translator');
    var Confirmation = require('oroui/js/delete-confirmation');
    var BaseView = require('oroui/js/app/views/base/view');
    var StepsListView = require('./step/step-list-view');
    require('oroentity/js/fields-loader');

    /**
     * @export  oroworkflow/js/workflow-management
     * @class   oro.WorkflowManagement
     * @extends Backbone.View
     */
    WorkflowManagementView = BaseView.extend({
        events: {
            'click .add-step-btn': 'addNewStep',
            'click .add-transition-btn': 'addNewTransition',
            'click .refresh-btn': 'refreshChart',
            'submit': 'onSubmit',
            'click [type=submit]': 'setSubmitActor'
        },

        options: {
            stepsEl: null,
            model: null,
            entities: []
        },

        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.initStartStepSelector();

            this.stepListView = new StepsListView({
                el: this.$(this.options.stepsEl),
                collection: this.model.get('steps'),
                workflow: this.model
            });

            this.$entitySelectEl = this.$('[name$="[related_entity]"]');
            this.initEntityFieldsLoader();
            this.listenTo(this.model.get('steps'), 'destroy ', this.onStepRemove);
        },

        render: function() {
            this.renderSteps();
            return this;
        },

        renderSteps: function() {
            this.stepListView.render();
        },

        onSubmit: function(e) {
            this.model.trigger('saveWorkflow', e);
        },

        setSubmitActor: function(e) {
            this.submitActor = e.target;
        },

        initStartStepSelector: function() {
            var getSteps = _.bind(function(query) {
                var steps = [];
                _.each(this.model.get('steps').models, function(step) {
                    // starting point is not allowed to be a start step
                    var stepLabel = step.get('label');
                    if (!step.get('_is_start') &&
                        (!query.term || query.term === stepLabel || _.indexOf(stepLabel, query.term) !== -1)
                    ) {
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
                'initSelection': _.bind(function(element, callback) {
                    var startStep = this.model.getStepByName(element.val());
                    callback({
                        id: startStep.get('name'),
                        text: startStep.get('label')
                    });
                }, this)
            };

            this.$startStepEl.inputWidget('create', 'select2', {initializeOptions: select2Options});
        },

        initEntityFieldsLoader: function() {
            var confirm = new Confirmation({
                title: __('Change Entity Confirmation'),
                okText: __('Yes'),
                content: __('oro.workflow.change_entity_confirmation')
            });
            confirm.on('ok', _.bind(function() {
                this.model.set('entity', this.$entitySelectEl.val());
            }, this));
            confirm.on('cancel', _.bind(function() {
                this.$entitySelectEl.inputWidget('val', this.model.get('entity'));
            }, this));

            this.$entitySelectEl.fieldsLoader({
                router: 'oro_api_workflow_entity_get',
                routingParams: {},
                confirm: confirm,
                requireConfirm: _.bind(function() {
                    return this.model.get('steps').length > 1 &&
                        (this.model.get('transitions').length +
                            this.model.get('transition_definitions').length +
                            this.model.get('attributes').length) > 0;
                }, this)
            });

            this.$entitySelectEl.on('change', _.bind(function() {
                if (!this.model.get('entity')) {
                    this.model.set('entity', this.$entitySelectEl.val());
                }
            }, this));

            this.$entitySelectEl.on('fieldsloadercomplete', _.bind(function(e) {
                this.initEntityFieldsData($(e.target).data('fields'));
            }, this));

            this._preloadEntityFieldsData();
        },

        _preloadEntityFieldsData: function() {
            if (this.$entitySelectEl.val()) {
                var fieldsData = this.$entitySelectEl.fieldsLoader('getFieldsData');
                if (!fieldsData.length) {
                    this.$entitySelectEl.fieldsLoader('loadFields');
                } else {
                    this.initEntityFieldsData(fieldsData);
                }
            }
        },

        addNewTransition: function() {
            this.model.trigger('requestAddTransition');
        },

        addNewStep: function() {
            this.model.trigger('requestAddStep');
        },

        refreshChart: function() {
            this.model.trigger('requestRefreshChart');
        },

        initEntityFieldsData: function(fields) {
            this.model.setEntityFieldsData(fields);
        },

        onStepRemove: function(step) {
            //Deselect start_step if it was removed
            if (this.$startStepEl.val() === step.get('name')) {
                this.$startStepEl.inputWidget('val', '');
            }
        },

        isEntitySelected: function() {
            return Boolean(this.$entitySelectEl.val());
        },

        getEntitySelect: function() {
            return this.$entitySelectEl;
        },

        valid: function() {
            return this.$el.valid();
        }
    });

    return WorkflowManagementView;
});
