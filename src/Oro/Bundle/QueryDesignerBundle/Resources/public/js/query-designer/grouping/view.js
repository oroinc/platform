/* global define */
define(['underscore', 'backbone', 'oro/entity-field-choice-view', 'jquery-ui'],
function(_, Backbone, EntityFieldChoiceView) {
    'use strict';

    var $ = Backbone.$;

    /**
     * @export  oro/query-designer/grouping/view
     * @class   oro.queryDesigner.grouping.View
     * @extends Backbone.View
     */
    return Backbone.View.extend({
        /** @property {Object} */
        options: {
            entityName: null,
            fieldsLabel: null,
            relatedLabel: null,
            findEntity: null
        },

        /** @property {Object} */
        selectors: {
            columnSelector: '[data-purpose="column-selector"]'
        },

        /** @property {oro.EntityFieldChoiceView} */
        columnSelector: null,

        initialize: function() {
            this.columnSelector = new EntityFieldChoiceView({
                el: this.$el.find(this.selectors.columnSelector),
                fieldsLabel: this.options.fieldsLabel,
                relatedLabel: this.options.relatedLabel,
                findEntity: this.options.findEntity
            });
            this.columnSelector.$el.select2("container").find("ul.select2-choices").sortable({
                cursor: 'move',
                delay : 100,
                containment: "parent",
                stop: _.bind(function() {
                    this.trigger('grouping:change');
                }, this)
            });
            this.columnSelector.$el.on('change', _.bind(function (e) {
                this.trigger('grouping:change');
            }, this));
        },

        changeEntity: function (entityName) {
            this.options.entityName = entityName;
        },

        updateColumnSelector: function (columns) {
            this.columnSelector.changeEntity(this.options.entityName, columns);
        },

        getGroupingColumns: function () {
            return _.map(this.columnSelector.$el.select2("data"), function (val) {
                return val.id;
            });
        },

        setGroupingColumns: function (columns) {
            this.columnSelector.$el.val(columns);
            this.columnSelector.$el.trigger('change');
        }
    });
});
