define(function(require) {
    'use strict';

    var WidgetTabsView;
    var BaseView = require('oroui/js/app/views/base/view');
    var $ = require('jquery');
    var LoadingMaskView = require('oroui/js/app/views/loading-mask-view');

    WidgetTabsView = BaseView.extend({
        events: {
            'shown.bs.tab .tab-button': 'onTabClick'
        },

        loadingMask: null,

        widgetComponentProcessingClass: 'widget-component-processing',

        /**
         * @inheritDoc
         */
        constructor: function WidgetTabsView() {
            WidgetTabsView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function() {
            this.subview('loading', new LoadingMaskView({container: this._getTabContent()}));

            WidgetTabsView.__super__.initialize.apply(this, arguments);
        },

        _getTabContent: function() {
            return this.$el.closest('.widget-content').find('.tab-content');
        },

        onTabClick: function(e) {
            var $currentTarget = $(e.currentTarget);
            var previusActiveTab = $(e.relatedTarget);
            var loadingView = this.subview('loading');
            var $tabContainer = this._getTabContent();

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
