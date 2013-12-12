/* global define */
define(['underscore', 'backbone', 'oro/query-designer/util', 'oro/query-designer/abstract-view', 'oro/query-designer/column/collection',
    'oro/query-designer/grouping/view', 'oro/query-designer/function-manager', 'jquery-ui'],
function(_, Backbone, util, AbstractView, ColumnCollection,
         GroupingView, FunctionManager) {
    'use strict';

    var $ = Backbone.$;

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

        /** @property {oro.queryDesigner.FunctionManager} */
        functionManager: null,

        /** @property {jQuery} */
        sortingSelector: null,

        initialize: function() {
            AbstractView.prototype.initialize.apply(this, arguments);

            this.addFieldLabelGetter(this.getFunctionFieldLabel);
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

            this.functionManager = new FunctionManager({
                el: this.$el.find('[data-purpose="function-selector"]')
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
                // adjust function selector
                var $el = $(e.currentTarget);
                var value = $el.val();
                this.functionManager.setActiveFunctions(
                    (_.isNull(value) || value == '')
                        ? {}
                        : this.getFieldApplicableConditions(util.findSelectOption($el, value))
                );
            }, this));

            this.sortingSelector = this.form.find('[data-purpose="sorting-selector"]');

            this.initColumnSorting();
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

        getFunctionFieldLabel: function (field, name, value) {
            if (field.attr('name') == this.functionManager.$el.attr('name')) {
                if (_.isNull(value) || value == '') {
                    return '';
                }
                return this.functionManager.getFunctionLabel(value['group_type'], value['group_name'], value['name']);
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
        },

        getFormFieldValue: function (name, field) {
            if (field.attr('name') == this.functionManager.$el.attr('name')) {
                var value = field.val();
                if (value == '') {
                    return null;
                }
                return _.extend({name: value}, util.findSelectOption(field, value).data());
            }
            return AbstractView.prototype.getFormFieldValue.apply(this, arguments);
        },

        setFormFieldValue: function (name, field, value) {
            if (field.attr('name') == this.functionManager.$el.attr('name')) {
                if (_.isNull(value) || value == '') {
                    field.val('');
                } else {
                    field.val(value['name']);
                }
                return;
            }
            AbstractView.prototype.setFormFieldValue.apply(this, arguments);
        }
    });
});
