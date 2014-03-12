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
            this.rowViews = {};
            this.rowViewsByAttribute = {};
            this.$listElBody = null;

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

        initList: function () {
            if (!this.$listElBody) {
                var listEl = $(this.template());
                this.$listElBody = listEl.find(this.options.listElBodyEl);
                this.$el.html(listEl);
            }
        },

        addItem: function(data) {
            var fieldId = this.options.workflow.getFieldIdByPropertyPath(data.property_path);
            data.isSystemLabel = !data.label;
            if (fieldId) {
                var pathData = this.fieldUtil.splitFieldId(fieldId);
                if (!data.label) {
                    data.label = _.last(pathData).label;
                }
                data.entityField = this.entityFieldTemplate(pathData);
            } else {
                if (!data.label && data.attribute_name) {
                    var attribute = this.options.workflow.getAttributeByName(data.attribute_name);
                    data.label = attribute.get('translated_label');
                }
                data.entityField = data.property_path || data.attribute_name;
            }

            var viewId = data.view_id
                || (this.rowViewsByAttribute.hasOwnProperty(data.attribute_name)
                    ? this.rowViewsByAttribute[data.attribute_name]
                    : null);
            if (!viewId) {
                var rowView = new AttributeFormOptionRowView({
                    data: data,
                    workflow: this.options.workflow
                });

                rowView.on('editFormOption', function(data) {
                    this.trigger('editFormOption', data);
                }, this);

                rowView.on('removeFormOption', function(data) {
                    var collection = this.getCollection();
                    var i = collection.length - 1;
                    while (i >= 0) {
                        if (collection[i].attribute_name == data.attribute_name) {
                            collection.splice(i, 1);
                        }
                        i--;
                    }
                    if (!this.getCollection().length) {
                        this.render();
                    }
                    this.trigger('removeFormOption', data);
                }, this);

                this.rowViews[rowView.cid] = rowView;
                this.rowViewsByAttribute[data.attribute_name] = rowView.cid;
                this.initList();
                this.$listElBody.append(rowView.render().$el);
            } else {
                this.rowViews[viewId].options.data = data;
                this.rowViews[viewId].render();
            }
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
            this.rowViews = {};
        },

        render: function() {
            this.resetView();
            if (this.getCollection().length) {
                this.initList();
                this.addAllItems(this.getCollection());
            } else {
                this.$el.empty();
                this.$listElBody = null;
            }

            return this;
        }
    });
});
