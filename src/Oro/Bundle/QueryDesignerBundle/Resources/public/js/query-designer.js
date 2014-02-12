/*global define*/
/*jslint nomen: true*/
define(['underscore', 'backbone', 'oro/translator', 'oro/app', 'oro/messenger',
    'oro/query-designer/column/view', 'oroquerydesigner/js/condition-builder'
    ], function (_, Backbone, __, app, messenger, ColumnView) {
    'use strict';

    var $ = Backbone.$;

    /**
     * @export  oro/query-designer
     * @class   oro.QueryDesigner
     * @extends Backbone.View
     */
    return Backbone.View.extend({
        /** @property {Object} */
        options: {
            storageElementSelector: null,
            columnChainTemplateSelector: null,
            fieldsLabel: 'Fields',
            relatedLabel: 'Related',
            findEntity: function (entityName) {
                return {name: entityName, label: entityName, plural_label: entityName, icon: null};
            },
            columnsOptions: {
                collection: null,
                groupingFormSelector: null,
                itemTemplateSelector: null,
                itemFormSelector: null
            },
            conditionBuilderSelector: ''
        },

        /** @property {oro.queryDesigner.column.View} */
        columnsView: null,

        /** @property {jQuery} */
        storageEl: null,

        initialize: function () {
            if (this.options.storageElementSelector) {
                this.storageEl = $(this.options.storageElementSelector);
            }

            // initialize views
            this.initColumnsView();
            this.$conditions = $(this.options.conditionBuilderSelector);
        },

        isEmpty: function () {
            return this.columnsView.getCollection().isEmpty() &&
                _.isEmpty(this.$conditions.conditionBuilder('getValue'));
        },

        changeEntity: function (entityName, columns) {
            this.updateColumnSelectors(entityName || '', columns || []);
        },

        updateColumnSelectors: function (entityName, data) {
            this.columnsView.changeEntity(entityName, data);
        },

        updateStorage: function () {
            if (this.storageEl) {
                var columns = this.columnsView.getCollection().toJSON();
                _.each(columns, function (value) {
                    delete value.id;
                });
                var filters = this.$conditions.conditionBuilder('getValue');
                var groupingColumns = [];
                _.each(this.columnsView.getGroupingColumns(), function (name) {
                    groupingColumns.push({
                        name: name
                    });
                });
                var data = {
                    columns: columns,
                    grouping_columns: groupingColumns,
                    filters: filters
                };
                this.storageEl.val(JSON.stringify(data));
            }
        },

        render: function() {
            // get source data
            var data = [];
            if (this.storageEl && this.storageEl.val() != '') {
                data = JSON.parse(this.storageEl.val());
            }

            // render ColumnsView
            this.columnsView.render();
            var groupingColumnNames = [];
            _.each(data['grouping_columns'], function (column) {
                groupingColumnNames.push(column['name']);
            });
            this.columnsView.setGroupingColumns(groupingColumnNames);
            if (!_.isUndefined(data['columns']) && !_.isEmpty(data['columns'])) {
                this.columnsView.getCollection().reset(data['columns']);
            }
            this.columnsView.$itemContainer.on('collection:change', _.bind(this.updateStorage, this));
            this.listenTo(this.columnsView, 'grouping:change', _.bind(this.updateStorage, this));
            this.$conditions.on('changed', _.bind(this.updateStorage, this));
            if (!_.isEmpty(data.filters)) {
                this.$conditions.conditionBuilder('setValue', data.filters);
            }

            return this;
        },

        initColumnsView: function () {
            var columnsOptions = _.extend(
                {
                    columnChainTemplateSelector: this.options.columnChainTemplateSelector,
                    fieldsLabel: this.options.fieldsLabel,
                    relatedLabel: this.options.relatedLabel,
                    findEntity: this.options.findEntity
                },
                this.options.columnsOptions
            );
            this.columnsView = new ColumnView(columnsOptions);
            delete this.options.columnsOptions;
        }
    });
});
