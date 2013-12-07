/* global define */
define(['oro/query-designer/abstract-view', 'oro/query-designer/column/collection',
    'oro/query-designer/grouping/view', 'oro/query-designer/aggregate-manager'],
function(AbstractView, ColumnCollection,
         GroupingView, AggregateManager) {
    'use strict';

    /**
     * @export  oro/query-designer/column/view
     * @class   oro.queryDesigner.column.View
     * @extends oro.queryDesigner.AbstractView
     */
    return AbstractView.extend({
        /** @property {Object} */
        options: {
            groupingFormSelector: null
        },

        /** @property oro.queryDesigner.column.Collection */
        collectionClass: ColumnCollection,

        /** @property {oro.queryDesigner.grouping.View} */
        groupingColumnsSelector: null,

        /** @property {oro.queryDesigner.AggregateManager} */
        aggregateManager: null,

        /** @property {jQuery} */
        sortingSelector: null,

        initialize: function() {
            AbstractView.prototype.initialize.apply(this, arguments);

            this.addFieldLabelGetter(this.getAggregateFieldLabel);
            this.addFieldLabelGetter(this.getSortingFieldLabel);
        },

        initForm: function() {
            AbstractView.prototype.initForm.apply(this, arguments);

            this.groupingColumnsSelector = new GroupingView({
                el: this.$el.find(this.options.groupingFormSelector),
                entityName: this.options.entityName,
                fieldsLabel: this.options.fieldsLabel,
                relatedLabel: this.options.relatedLabel,
                findEntity: this.options.findEntity
            });
            this.listenTo(this.groupingColumnsSelector, 'grouping:change', _.bind(function (e) {
                this.trigger('grouping:change');
            }, this));

            this.aggregateManager = new AggregateManager({
                el: this.$el.find('[data-purpose="aggregate-selector"]')
            });

            this.columnSelector.$el.on('change', _.bind(function (e) {
                if (!_.isUndefined(e.added)) {
                    // try to guess a label when a column changed
                    var label = this.form.find('[data-purpose="label"]');
                    if (label.val() == ''
                        || (!_.isUndefined(e.removed) && !_.isUndefined(e.removed.text) && label.val() == e.removed.text)) {
                        label.val(e.added.text);
                    }
                }
                // adjust aggregate selector
                var $el = $(e.currentTarget);
                var value = $el.val();
                this.aggregateManager.setActiveAggregate(
                    (_.isNull(value) || value == '')
                        ? {}
                        : this.getFieldApplicableConditions(
                            $el.find('option[value="' + value.replace(/\\/g,"\\\\").replace(/:/g,"\\:") + '"]'))
                );
            }, this));

            this.sortingSelector = this.form.find('[data-purpose="sorting-selector"]');
        },

        changeEntity: function (entityName) {
            AbstractView.prototype.changeEntity.apply(this, arguments);
            this.groupingColumnsSelector.changeEntity(entityName);
        },

        updateColumnSelector: function (columns) {
            AbstractView.prototype.updateColumnSelector.apply(this, arguments);
            this.groupingColumnsSelector.updateColumnSelector(columns);
        },

        getGroupingColumns: function () {
            return this.groupingColumnsSelector.getGroupingColumns();
        },

        setGroupingColumns: function (columns) {
            this.groupingColumnsSelector.setGroupingColumns(columns);
        },

        getAggregateFieldLabel: function (field, name, value) {
            if (field.attr('name') == this.aggregateManager.$el.attr('name')) {
                if (_.isNull(value) || value == '') {
                    return '';
                }
                return this.aggregateManager.getAggregateFunctionLabel(value);
            }
            return null;
        },

        getSortingFieldLabel: function (field, name, value) {
            if (field.attr('name') == this.sortingSelector.attr('name')) {
                if (_.isNull(value) || value == '') {
                    return '';
                }
            }
            return null;
        }
    });
});
