/* global define */
define(['underscore', 'backbone', 'oroworkflow/js/workflow-management/attribute/form-option-view/row'],
function(_, Backbone, AttributeFormOptionRowView) {
    'use strict';

    var $ = Backbone.$;

    /**
     * @export  oroworkflow/js/workflow-management/attribute/form-option-view/list
     * @class   oro.WorkflowManagement.AttributeFormOptionListView
     * @extends Backbone.View
     */
    return Backbone.View.extend({
        options: {
            listElBodyEl: 'tbody',
            template: null,
            fields_selector_el: null,
            workflow: null,
            collection: [],
            entity_field_template: null
        },

        initialize: function() {
            var template = this.options.template || $('#attribute-form-option-list-template').html();
            this.template = _.template(template);
            this.rowViews = [];

            this.fieldUtil = this.options.fields_selector_el.data('oroentity-fieldChoice').entityFieldUtil;
            this.entityFieldTemplate = _.template(
                this.options.entity_field_template || $('#entity-column-chain-template').html()
            );

            this.listenTo(this.options.workflow, 'pathMappingInit', this.render);
            if (this.options.workflow.pathMappingInitialized) {
                this.render();
            }
        },

        addAllItems: function(items) {
            _.each(items, this.addItem, this);
        },

        addItem: function(data) {
            var fieldId = this.options.workflow.getFieldIdByPropertyPath(data.property_path);
            if (fieldId) {
                var pathData = this.fieldUtil.splitFieldId(fieldId);
                data.entityField = this.entityFieldTemplate(pathData);
            } else {
                data.entityField = data.property_path || data.attribute_name;
            }

            var rowView = new AttributeFormOptionRowView({
                data: data,
                workflow: this.options.workflow
            });
            rowView.on('removeFormOption', function(data) {
                this.trigger('removeFormOption', data);
            }, this);
            this.rowViews.push(rowView);
            this.$listElBody.append(rowView.render().$el);
        },

        getCollection: function() {
            return this.options.collection;
        },

        remove: function() {
            this.resetView();
            Backbone.View.prototype.remove.call(this);
        },

        resetView: function() {
            _.each(this.rowViews, function (rowView) {
                rowView.remove();
            });
            this.rowViews = [];
        },

        render: function() {
            this.resetView();
            if (this.getCollection().length) {
                this.$listEl = $(this.template());
                this.$listElBody = this.$listEl.find(this.options.listElBodyEl);
                this.$el.html(this.$listEl);
                this.addAllItems(this.getCollection());
            } else {
                this.$el.empty();
            }

            return this;
        }
    });
});
