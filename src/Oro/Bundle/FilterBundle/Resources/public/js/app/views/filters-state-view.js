define(function(require) {
    'use strict';

    var FiltersStateView;
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');

    FiltersStateView = BaseView.extend({
        filters: [],

        template: require('tpl!../../../templates/filters-state-view.html'),

        initialize: function(options) {

            _.extend(this, _.pick(options, ['filters']));

            _.each(this.filters, function(filter) {
                this.listenTo(filter, 'update updateCriteriaLabels', this.render);
            }, this);

            FiltersStateView.__super__.initialize.apply(this, arguments);
        },

        getTemplateData: function() {
            var data = FiltersStateView.__super__.getTemplateData.apply(this, arguments);
            data.filters = [];
            _.each(this.filters, function(filter) {
                if (!filter.isEmpty()) {
                    data.filters.push(filter.getState());
                }
            }, this);

            return data;
        },

        /**
         * Render filter list
         *
         * @return {*}
         */
        render: function() {
            FiltersStateView.__super__.render.apply(this, arguments);

            if (_.isEmpty(this.filters)) {
                this.$el.hide();
            }

            return this;
        },

        show: function() {
            this.$el.show();
        },

        hide: function() {
            this.$el.hide();
        }
    });

    return FiltersStateView;
});
