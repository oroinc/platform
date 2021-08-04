define(function(require) {
    'use strict';

    const _ = require('underscore');
    const BaseView = require('oroui/js/app/views/base/view');
    const ANIMATED_INIT_CLASS = 'animated-init';

    const FiltersStateView = BaseView.extend({
        POPOVER_DELAY: 300,

        filters: [],

        template: require('tpl-loader!orofilter/templates/filters-state-view.html'),

        popoverTemplate: require('tpl-loader!orofilter/templates/filters-state-popover.html'),

        events: {
            'click .filters-state': 'onClick',
            'mouseenter .filters-state': 'onMouseEnter',
            'mouseleave .filters-state': 'onMouseLeave'
        },

        /**
         * @inheritdoc
         */
        constructor: function FiltersStateView(options) {
            FiltersStateView.__super__.constructor.call(this, options);
        },

        initialize: function(options) {
            _.extend(this, _.pick(options, ['filters']));

            _.each(this.filters, function(filter) {
                this.listenTo(filter, 'update updateCriteriaLabels', this.render);
            }, this);

            if (options.useAnimationOnInit) {
                this.$el.addClass(ANIMATED_INIT_CLASS);
            }

            FiltersStateView.__super__.initialize.call(this, options);
        },

        getTemplateData: function() {
            const data = FiltersStateView.__super__.getTemplateData.call(this);
            data.filters = [];
            _.each(this.filters, function(filter) {
                if (!filter.isEmptyValue()) {
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
            FiltersStateView.__super__.render.call(this);

            if (_.isEmpty(this.filters)) {
                this.$el.hide();
            }

            return this;
        },

        show: function() {
            this.$el.show();
        },

        hide: function() {
            this.$el.removeClass(ANIMATED_INIT_CLASS).hide();
        },

        onMouseEnter: function(e) {
            const $filtersState = this.$('.filters-state');
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
            const $filtersState = this.$('.filters-state');
            $filtersState.popover('hide');
            $filtersState.popover('dispose');
        },

        onClick: function() {
            this.trigger('clicked');
        }
    });

    return FiltersStateView;
});
