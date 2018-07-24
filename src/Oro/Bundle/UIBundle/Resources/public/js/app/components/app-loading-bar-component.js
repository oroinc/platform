define(function(require) {
    'use strict';

    var AppLoadingBarComponent;
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var LoadingBarView = require('oroui/js/app/views/loading-bar-view');

    AppLoadingBarComponent = BaseComponent.extend({
        listen: {
            'page:beforeChange mediator': 'showLoading',
            'page:afterChange mediator': 'hideLoading'
        },

        /**
         * @inheritDoc
         */
        constructor: function AppLoadingBarComponent(options) {
            AppLoadingBarComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.initView(options);

            mediator.setHandler('showLoadingBar', this.showLoading, this);
            mediator.setHandler('hideLoadingBar', this.hideLoading, this);

            if (options.showOnStartup) {
                this.showLoading();
            }

            AppLoadingBarComponent.__super__.initialize.call(this, options);
        },

        /**
         * Initializes loading bar view
         *
         * @param {Object} options
         */
        initView: function(options) {
            var viewOptions = _.defaults({}, options.viewOptions, {
                ajaxLoading: true
            });
            this.view = new LoadingBarView(viewOptions);
        },

        /**
         * Shows loading bar
         */
        showLoading: function() {
            this.view.showLoader();
        },

        /**
         * Hides loading bar
         */
        hideLoading: function() {
            this.view.hideLoader();
        }
    });

    return AppLoadingBarComponent;
});
