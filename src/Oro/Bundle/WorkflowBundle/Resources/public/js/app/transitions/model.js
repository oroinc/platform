define([
    'jquery',
    'underscore',
    'oroui/js/app/models/base/model',
    'routing'
], function($, _, BaseModel, routing) {
    'use strict';

    var WorkflowTransitionModel;

    WorkflowTransitionModel = BaseModel.extend({
        defaults: {
            workflowName: '',
            workflowItemId: 0,
            entityId: 0,
            name: '',
            label: '',
            isAllowed: false,
            hasForm: false,
            isStart: false,
            displayType: '',
            frontendOptions: {},
            dialogUrl: false,
            transitionUrl: false
        },

        initialize: function() {
            WorkflowTransitionModel.__super__.initialize.apply(this, arguments);
            this.initializeUrlData();
        },

        /**
         * Initializes URL for transition, sets transitionUrl and dialogUrl attribute values.
         *
         * Check @OroWorkflowBundle:Widget:widget/buttons.html.twig and @OroWorkflowBundle::macros.html.twig for this
         * logic in Twig templates
         */
        initializeUrlData: function() {
            if (!this.get('workflowItemId')) {
                this.set(
                    'transitionUrl',
                    routing.generate('oro_api_workflow_start', {
                        workflowName: this.get('workflowName'),
                        transitionName: this.get('name'),
                        entityId: this.get('entityId')
                    })
                );
            } else {
                this.set(
                    'transitionUrl',
                    routing.generate('oro_api_workflow_transit', {
                        workflowItemId: this.get('workflowItemId'),
                        transitionName: this.get('name')
                    })
                );
            }

            if (this.get('displayType') === 'dialog' && this.get('hasForm')) {
                if (!this.get('workflowItemId')) {
                    this.set(
                        'dialogUrl',
                        routing.generate('oro_workflow_widget_start_transition_form', {
                            workflowName: this.get('workflowName'),
                            transitionName: this.get('name'),
                            entityId: this.get('entityId')
                        })
                    );
                } else {
                    this.set(
                        'dialogUrl',
                        routing.generate('oro_workflow_widget_transition_form', {
                            transitionName: this.get('name'),
                            workflowItemId: this.get('workflowItemId')
                        })
                    );
                }
            }
        }
    });

    return WorkflowTransitionModel;
});


