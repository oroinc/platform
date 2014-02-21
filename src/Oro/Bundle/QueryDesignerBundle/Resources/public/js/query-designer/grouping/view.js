/*global define*/
define(['underscore', 'backbone', 'oroui/js/app', 'oroentity/js/entity-field-view', 'jquery-ui'
    ], function (_, Backbone, app, EntityFieldView) {
    'use strict';

    /**
     * @export  oroquerydesigner/js/query-designer/grouping/view
     * @class   oro.queryDesigner.grouping.View
     * @extends Backbone.View
     */
    return Backbone.View.extend({
        /** @property {Object} */
        options: {
            fieldsLabel: null,
            relatedLabel: null,
            findEntity: null
        },

        /** @property {Object} */
        selectors: {
            columnSelector: '[data-purpose="column-selector"]'
        },

        /** @property {oro.EntityFieldView} */
        columnSelector: null,

        /**
         * @property {Array}
         */
        grouping: null,

        initialize: function () {
            var metadata = this.$el.closest('[data-metadata]').data('metadata');
            metadata = _.extend({grouping: {exclude: []}}, metadata);
            this.grouping = metadata.grouping;

            this.columnSelector = new EntityFieldView({
                el: this.$el.find(this.selectors.columnSelector),
                fieldsLabel: this.options.fieldsLabel,
                relatedLabel: this.options.relatedLabel,
                findEntity: this.options.findEntity,
                exclude: _.bind(this.isFieldAllowedForGrouping, this)
            });
            this.columnSelector.$el.select2("container").find("ul.select2-choices").sortable({
                cursor: 'move',
                delay : 100,
                opacity: 0.7,
                revert: 10,
                containment: "parent",
                stop: _.bind(function () {
                    this.trigger('grouping:change');
                }, this)
            });
            this.columnSelector.$el.on('change', _.bind(function (e) {
                this.trigger('grouping:change');
            }, this));
        },

        changeEntity: function (entityName, columns) {
            this.columnSelector.changeEntity(entityName, columns);
        },

        isFieldAllowedForGrouping: function (criteria) {
            var matched = _.find(this.grouping.exclude, function (exclude) {
                var res = true;
                _.each(exclude, function (val, key) {
                    if (!_.has(criteria, key) || !app.isEqualsLoosely(val, criteria[key])) {
                        res = false;
                    }
                });
                return res;
            });
            return !_.isUndefined(matched);
        },

        getGroupingColumns: function () {
            var result = this.columnSelector.$el.val();
            if (_.isString(result)) {
                result = (result == '')
                    ? []
                    : result.split(',');
            }
            return result;
        },

        setGroupingColumns: function (columns) {
            this.columnSelector.$el.val(columns);
            this.columnSelector.$el.trigger('change');
        }
    });
});
