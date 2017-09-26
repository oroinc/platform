define(function(require) {
    'use strict';

    var WidgetTabsView;
    var BaseView = require('oroui/js/app/views/base/view');
    var $ = require('jquery');
    var LoadingMaskView = require('oroui/js/app/views/loading-mask-view');

    WidgetTabsView = BaseView.extend({
        events: {
            'click .tab-button': 'onTabClick'
        },

        loadingMask: null,

        widgetComponentProcessingClass: 'widget-component-processing',

        initialize: function() {
            this.subview('loading', new LoadingMaskView({container: this._getTabContent()}));

            WidgetTabsView.__super__.initialize.apply(this, arguments);
        },

        _getTabContent: function() {
            return this.$el.closest('.widget-content').find('.tab-content');
        },

        onTabClick: function(e) {
            var $currentTarget = $(e.currentTarget);
            var $prevTab = this.$('.nav-tabs').find('li.active');
            var $currentTab = $currentTarget.closest('li');
            var loadingView = this.subviewsByName.loading;

            $prevTab.removeClass('active');
            $currentTab.addClass('active');

            loadingView.show();

            // add style like on standard tabs realization through tabs-component
            $currentTarget.addClass(this.widgetComponentProcessingClass);

            $.ajax({
                url: $currentTarget.data('url'),
                dataType: 'html',
                error: function() {
                    $currentTab.removeClass('active');
                    $prevTab.addClass('active');
                },
                success: function(data) {
                    this._getTabContent().find('.content')
                        .trigger('content:remove')
                        .html(data)
                        .trigger('content:changed');
                }.bind(this),
                complete: function() {
                    loadingView.hide();
                    $currentTarget.removeClass(this.widgetComponentProcessingClass);
                }.bind(this)
            });
        }
    });

    return WidgetTabsView;
});
