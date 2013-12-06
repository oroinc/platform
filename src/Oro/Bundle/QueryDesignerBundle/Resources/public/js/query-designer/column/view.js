/* global define */
define(['oro/query-designer/abstract-view', 'oro/query-designer/column/collection', 'oro/query-designer/grouping/view'],
function(AbstractView, ColumnCollection, GroupingView) {
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

            // try to guess a label when a column changed
            this.columnSelector.$el.on('change', _.bind(function (e) {
                if (!_.isUndefined(e.added)) {
                    var labelEl = this.findFormField('label');
                    if (labelEl.val() == ''
                        || (!_.isUndefined(e.removed) && !_.isUndefined(e.removed.text) && labelEl.val() == e.removed.text)) {
                        labelEl.val(e.added.text);
                    }
                }
            }, this));
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
        }
    });
});
