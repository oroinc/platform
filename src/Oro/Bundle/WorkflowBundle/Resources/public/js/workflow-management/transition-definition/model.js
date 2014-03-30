/* global define */
define(['backbone'],
function(Backbone) {
    'use strict';

    /**
     * @export  oroworkflow/js/workflow-management/transition-definition/model
     * @class   oro.workflowManagement.TransitionDefinitionModel
     * @extends Backbone.Model
     */
    return Backbone.Model.extend({
        defaults: {
            name: null,
            pre_conditions: null,
            conditions: null,
            post_actions: null
        },

        initialize: function() {
            if (this.get('pre_conditions') === null) {
                this.set('pre_conditions', {});
            }
            if (this.get('conditions') === null) {
                this.set('conditions', {});
            }
            if (this.get('post_actions') === null) {
                this.set('post_actions', []);
            }
        }
    });
});
