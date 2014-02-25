/* global define */
define(['underscore', 'backbone'],
function(_, Backbone) {
    'use strict';

    var $ = Backbone.$;

    /**
     * @export  oro/workflow-management
     * @class   oro.WorkflowManagement
     * @extends Backbone.View
     */
    return Backbone.View.extend({
        options: {
            stepsEl: null,
            metadataFormEl: null,
            model: null
        },

        initialize: function() {
            this.$stepsContainer = $(this.options.stepsEl);
        },

        setFormMetadataToModel: function() {
            var metadataElements = {
                'label': 'label',
                'related_entity': 'relatedEntity',
                'steps_display_ordered': 'stepsDisplayOrdered'
            };

            for (var elName in metadataElements) if (metadataElements.hasOwnProperty(elName)) {
                var el = this._getFormElement(this.$el, elName);
                this.model.set(metadataElements[elName], el.val());
            }
        },

        _getFormElement: function(form, name) {
            var elId = this.$el.attr('id') + '_' + name;
            return this.$('#' + elId);
        },

        render: function() {

        }
    });
});
