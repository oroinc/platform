/* global define */
define([
    'underscore', 'chaplin', 'jquery', 'orotranslation/js/translator',
    'oroworkflow/js/workflow-management/step/view/list',
    'oroui/js/delete-confirmation',
    'oroentity/js/fields-loader'
],
function (_, Chaplin, $, __,
     StepsListView,
     Confirmation
) {
    'use strict';

    /**
     * @export  oroworkflow/js/workflow-management
     * @class   oro.WorkflowManagement
     * @extends Backbone.View
     */
    return Chaplin.View.extend({
        events: {
            'click .add-step-btn': 'addNewStep',
            'click .add-transition-btn': 'addNewTransition'
        },

        options: {
            stepsEl: null,
            model: null,
            entities: []
        },

        initialize: function (options) {
            this.options = _.defaults(options || {}, this.options);

            this.initStartStepSelector();

            this.stepListView = new StepsListView({
                el: this.$(this.options.stepsEl),
                collection: this.model.get('steps'),
                workflow: this.model
            });

            this.$entitySelectEl = this.$('[name$="[related_entity]"]');
            this.initEntityFieldsLoader();
            this.initForm();

            this.listenTo(this.model.get('steps'), 'destroy', this.onStepRemove);
        },

        render: function () {
            this.renderSteps();
            return this;
        },

        renderSteps: function () {
            this.stepListView.render();
        },

        initForm: function () {
            this.model.url = this.$el.attr('action');
            this.$el.on('submit', _.bind(this.model.trigger, this.model, 'saveWorkflow'));
            this.$('[type=submit]').click(_.bind(function () {
                this.submitActor = this;
            }, this));
        },

        initStartStepSelector: function () {
            var select2Options,
                getSteps = _.bind(function (query) {
                var steps = [];
                _.each(this.model.get('steps').models, function (step) {
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

            select2Options = {
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

        initEntityFieldsLoader: function () {
            var confirm = new Confirmation({
                title: __('Change Entity Confirmation'),
                okText: __('Yes'),
                content: __('oro.workflow.change_entity_confirmation')
            });
            confirm.on('ok', _.bind(function () {
                this.model.set('entity', this.$entitySelectEl.val());
            }, this));
            confirm.on('cancel', _.bind(function () {
                this.$entitySelectEl.select2('val', this.model.get('entity'));
            }, this));

            this.$entitySelectEl.fieldsLoader({
                router: 'oro_workflow_api_rest_entity_get',
                routingParams: {},
                confirm: confirm,
                requireConfirm: _.bind(function () {
                    return this.model.get('steps').length > 1 &&
                        (this.model.get('transitions').length +
                            this.model.get('transition_definitions').length +
                            this.model.get('attributes').length) > 0;
                }, this)
            });

            this.$entitySelectEl.on('change', _.bind(function () {
                if (!this.model.get('entity')) {
                    this.model.set('entity', this.$entitySelectEl.val());
                }
            }, this));

            this.$entitySelectEl.on('fieldsloadercomplete', _.bind(function (e) {
                this.initEntityFieldsData($(e.target).data('fields'));
            }, this));

            this._preloadEntityFieldsData();
        },

        _preloadEntityFieldsData: function () {
            if (this.$entitySelectEl.val()) {
                var fieldsData = this.$entitySelectEl.fieldsLoader('getFieldsData');
                if (!fieldsData.length) {
                    this.$entitySelectEl.fieldsLoader('loadFields');
                } else {
                    this.initEntityFieldsData(fieldsData);
                }
            }
        },

        addNewTransition: function () {
            this.model.trigger('requestAddTransition');
        },

        addNewStep: function () {
            this.model.trigger('requestAddStep');
        },

        initEntityFieldsData: function (fields) {
            this.model.setEntityFieldsData(fields);
        },

        onStepRemove: function (step) {
            //Deselect start_step if it was removed
            if (this.$startStepEl.val() === step.get('name')) {
                this.$startStepEl.select2('val', '');
            }
        },

        isEntitySelected: function () {
            return !!this.$entitySelectEl.val();
        },

        getEntitySelect: function () {
            return this.$entitySelectEl;
        },

        valid: function () {
            return this.$el.valid();
        }
    });
});
