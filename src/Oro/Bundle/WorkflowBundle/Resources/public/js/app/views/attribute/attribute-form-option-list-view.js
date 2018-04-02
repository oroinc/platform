define(function(require) {
    'use strict';

    var AttributeFormOptionListView;
    var _ = require('underscore');
    var $ = require('jquery');
    var BaseView = require('oroui/js/app/views/base/view');
    var AttributeFormOptionRowView = require('./attribute-form-option-row-view');

    AttributeFormOptionListView = BaseView.extend({
        options: {
            listElBodyEl: 'tbody',
            template: null,
            fieldsChoiceView: null,
            workflow: null,
            entityFieldsProvider: null,
            items: [],
            entity_field_template: null
        },

        requiredOptions: ['fieldsChoiceView', 'workflow', 'entityFieldsProvider'],

        /**
         * @inheritDoc
         */
        constructor: function AttributeFormOptionListView() {
            AttributeFormOptionListView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            options = options || {};
            var requiredMissed = this.requiredOptions.filter(function(option) {
                return _.isUndefined(options[option]);
            });
            if (requiredMissed.length) {
                throw new TypeError('Missing required option(s): ' + requiredMissed.join(', '));
            }
            this.options = _.defaults(options, this.options);
            var template = this.options.template || $('#attribute-form-option-list-template').html();
            this.template = _.template(template);
            this.rowViews = {};
            this.$listElBody = null;

            this.entityFieldTemplate = _.template(
                this.options.entity_field_template || $('#entity-column-chain-template').html()
            );
        },

        addAllItems: function(items) {
            _.each(items, this.addItem, this);
        },

        initList: function() {
            if (!this.$listElBody) {
                var listEl = $(this.template());
                this.$listElBody = listEl.find(this.options.listElBodyEl);
                this.$el.html(listEl);
            }
        },

        addItem: function(data) {
            var collection = this.getCollection();
            var fieldId = this.options.entityFieldsProvider.getPathByPropertyPathSafely(data.property_path);
            var fieldChoiceView = this.options.fieldsChoiceView;
            var hasEntityField = this.options.entityFieldsProvider.validatePath(fieldId);
            data.isSystemLabel = !data.label;

            if (fieldId && hasEntityField) {
                if (!data.label) {
                    data.label = _.result(_.last(fieldChoiceView.splitFieldId(fieldId)).field, 'label');
                }
                data.entityField = fieldChoiceView.formatChoice(fieldId, this.entityFieldTemplate);
            } else {
                if (!data.label && data.attribute_name) {
                    var attribute = this.options.workflow.getAttributeByName(data.attribute_name);
                    data.label = attribute.get('label');
                }

                if (hasEntityField) {
                    data.entityField = data.property_path || data.attribute_name;
                } else {
                    data.entityField = data.attribute_name;
                    data.is_entity_attribute = null;
                }
            }

            var collectionItem = data.itemId ? _.findWhere(collection, {itemId: data.itemId}) : null;
            if (collectionItem) {
                _.extend(collectionItem, data);
            } else {
                data.itemId = _.uniqueId();
                collection.push(data);
            }
            var rowView = this.subview('row:' + data.itemId);
            if (rowView) {
                rowView.update(data);
            } else {
                rowView = new AttributeFormOptionRowView({
                    data: data
                });

                rowView.on('editFormOption', function(data) {
                    this.trigger('editFormOption', data);
                }, this);

                rowView.on('removeFormOption', function(data) {
                    var collection = this.getCollection();
                    var item = _.findWhere(collection, {itemId: data.itemId});
                    collection.splice(collection.indexOf(item), 1);
                    this.removeSubview('row:' + data.itemId);
                }, this);

                this.subview('row:' + data.itemId, rowView);
                this.initList();
                this.$listElBody.append(rowView.render().$el);
            }
        },

        getCollection: function() {
            return this.options.items;
        },

        remove: function() {
            this.resetView();
            AttributeFormOptionListView.__super__.remove.call(this);
        },

        resetView: function() {
            _.each(this.rowViews, function(rowView) {
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

    return AttributeFormOptionListView;
});
