/* global define */
define(['underscore', 'backbone', 'oro/translator', 'oro/app', 'oro/messenger', 'oro/loading-mask',
    'oro/query-designer/column/view', 'oro/query-designer/filter/view'],
function(_, Backbone, __, app, messenger, LoadingMask,
         ColumnView, FilterView) {
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
            getLoadColumnsUrl: function (entityName) {
                return '';
            },
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
            filtersOptions: {
                collection: null,
                itemTemplateSelector: null,
                itemFormSelector: null
            }
        },

        /** @property {oro.LoadingMask} */
        loadingMask: null,

        /** @property {oro.queryDesigner.column.View} */
        columnsView: null,

        /** @property {oro.queryDesigner.filter.View} */
        filtersView: null,

        /** @property {jQuery} */
        storageEl: null,

        initialize: function() {
            if (this.options.storageElementSelector) {
                this.storageEl = $(this.options.storageElementSelector);
            }

            // initialize loading mask control
            this.loadingMask = new LoadingMask();
            this.$el.append(this.loadingMask.render().$el);

            // initialize views
            this.initColumnsView();
            this.initFiltersView();
        },

        isEmpty: function () {
            return this.columnsView.getCollection().isEmpty()
                && this.filtersView.getCollection().isEmpty();
        },

        changeEntity: function (entityName, columns) {
            if (_.isNull(entityName) || entityName == '') {
                this.updateColumnSelectors(_.isNull(entityName) ? '' : entityName, []);
            } else if (!_.isUndefined(columns) && !_.isNull(columns)) {
                this.updateColumnSelectors(_.isNull(entityName) ? '' : entityName, columns);
            } else {
                var url = this.options.getLoadColumnsUrl(entityName.replace(/\\/g,"_"));
                if (!_.isNull(url) && url != '') {
                    this.disableViews();
                    $.ajax({
                        url: url,
                        success: _.bind(function(data) {
                            this.updateColumnSelectors(entityName, data);
                            this.enableViews();
                        }, this),
                        error: _.bind(function (jqXHR) {
                            this.showError(jqXHR.responseJSON);
                            this.enableViews();
                        }, this)
                    });
                }
            }
        },

        updateColumnSelectors: function (entityName, data) {
            this.columnsView.changeEntity(entityName, data);
            this.filtersView.changeEntity(entityName, data);
        },

        updateStorage: function () {
            if (this.storageEl) {
                var columns = this.columnsView.getCollection().toJSON();
                _.each(columns, function (value) {
                    delete value.id;
                });
                var filters = this.filtersView.getCollection().toJSON();
                _.each(filters, function (value) {
                    delete value.id;
                    delete value.index;
                });
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
            this.listenTo(this.columnsView, 'collection:change', _.bind(this.updateStorage, this));
            this.listenTo(this.columnsView, 'grouping:change', _.bind(this.updateStorage, this));

            // render FiltersView
            this.filtersView.render();
            if (!_.isUndefined(data['filters']) && !_.isEmpty(data['filters'])) {
                this.filtersView.getCollection().reset(data['filters']);
            }
            this.listenTo(this.filtersView, 'collection:change', _.bind(this.updateStorage, this));

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
        },

        initFiltersView: function () {
            var filtersOptions = _.extend(
                {
                    columnChainTemplateSelector: this.options.columnChainTemplateSelector,
                    fieldsLabel: this.options.fieldsLabel,
                    relatedLabel: this.options.relatedLabel,
                    findEntity: this.options.findEntity
                },
                this.options.filtersOptions
            );
            this.filtersView = new FilterView(filtersOptions);
            delete this.options.filtersOptions;
        },

        enableViews: function () {
            this.loadingMask.hide();
        },

        disableViews: function () {
            this.loadingMask.show();
        },

        showError: function (err) {
            if (!_.isUndefined(console)) {
                console.error(_.isUndefined(err.stack) ? err : err.stack);
            }
            var msg = __('Sorry, unexpected error was occurred');
            if (app.debug) {
                if (!_.isUndefined(err.message)) {
                    msg += ': ' + err.message;
                } else if (!_.isUndefined(err.errors) && _.isArray(err.errors)) {
                    msg += ': ' + err.errors.join();
                } else if (_.isString(err)) {
                    msg += ': ' + err;
                }
            }
            messenger.notificationFlashMessage('error', msg);
        }
    });
});
