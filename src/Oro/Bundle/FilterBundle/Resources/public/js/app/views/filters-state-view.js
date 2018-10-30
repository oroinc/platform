define(function(require) {
    'use strict';

    var FiltersStateView;
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');

    FiltersStateView = BaseView.extend({
        POPOVER_DELAY: 300,

        filters: [],

        template: require('tpl!../../../templates/filters-state-view.html'),

        popoverTemplate: require('tpl!../../../templates/filters-state-popover.html'),

        events: {
            'click .filters-state': 'onClick',
            'mouseenter .filters-state': 'onMouseEnter',
            'mouseleave .filters-state': 'onMouseLeave'
        },

        /**
         * @inheritDoc
         */
        constructor: function FiltersStateView() {
            FiltersStateView.__super__.constructor.apply(this, arguments);
        },

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
        },

        onMouseEnter: function(e) {
            var $filtersState = this.$('.filters-state');
            if ($filtersState[0].scrollWidth > $filtersState[0].clientWidth) {
                $filtersState.popover({
                    content: $filtersState.text(),
                    trigger: 'manual',
                    placement: 'bottom',
                    animation: false,
                    container: 'body',
                    template: this.popoverTemplate()
                });

                this.popoverDelay = _.delay(function() {
                    $filtersState.popover('show');
                }, this.POPOVER_DELAY);
            }
        },

        onMouseLeave: function(e) {
            if (this.popoverDelay) {
                clearTimeout(this.popoverDelay);
                delete this.popoverDelay;
            }
            var $filtersState = this.$('.filters-state');
            $filtersState.popover('hide');
            $filtersState.popover('dispose');
        },

        onClick: function() {
            this.trigger('clicked');
        }
    });

    return FiltersStateView;
});
