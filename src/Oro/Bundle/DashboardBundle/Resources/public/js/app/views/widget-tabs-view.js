define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const $ = require('jquery');
    const LoadingMaskView = require('oroui/js/app/views/loading-mask-view');

    const WidgetTabsView = BaseView.extend({
        events: {
            'shown.bs.tab .tab-button': 'onTabShown'
        },

        loadingMask: null,

        widgetComponentProcessingClass: 'widget-component-processing',

        /**
         * @inheritdoc
         */
        constructor: function WidgetTabsView(options) {
            WidgetTabsView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.subview('loading', new LoadingMaskView({container: this._getTabContent()}));

            WidgetTabsView.__super__.initialize.call(this, options);
        },

        _getTabContent: function() {
            return this.$el.closest('.widget-content').find('.tab-content');
        },

        onTabShown: function(e) {
            const $currentTarget = $(e.currentTarget);
            const previusActiveTab = $(e.relatedTarget);
            const loadingView = this.subview('loading');
            const $tabContainer = this._getTabContent();

            $tabContainer.attr('aria-labelledby', $currentTarget.attr('id'));
            loadingView.show();

            // add style like on standard tabs realization through tabs-component
            $currentTarget.addClass(this.widgetComponentProcessingClass);

            $.ajax({
                url: $currentTarget.data('url'),
                dataType: 'html',
                error: function() {
                    previusActiveTab.trigger('click');
                },
                success: function(data) {
                    $tabContainer.find('.content')
                        .trigger('content:remove')
                        .html(data)
                        .trigger('content:changed');
                },
                complete: function() {
                    loadingView.hide();
                    $currentTarget.removeClass(this.widgetComponentProcessingClass);
                }.bind(this)
            });
        }
    });

    return WidgetTabsView;
});
