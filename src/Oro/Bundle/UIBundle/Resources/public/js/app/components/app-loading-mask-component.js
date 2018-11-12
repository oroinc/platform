define(function(require) {
    'use strict';

    var AppLoadingMaskComponent;
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var LoadingMaskView = require('oroui/js/app/views/loading-mask-view');

    AppLoadingMaskComponent = BaseComponent.extend({
        listen: {
            'page:beforeChange mediator': 'showLoading',
            'page:afterChange mediator': 'hideLoading'
        },

        /**
         * @inheritDoc
         */
        constructor: function AppLoadingMaskComponent(options) {
            AppLoadingMaskComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.initView(options);

            mediator.setHandler('showLoading', this.showLoading, this);
            mediator.setHandler('hideLoading', this.hideLoading, this);

            if (options.showOnStartup) {
                this.showLoading();
            }

            AppLoadingMaskComponent.__super__.initialize.call(this, options);
        },

        /**
         * Initializes loading mask view
         *
         * @param {Object} options
         */
        initView: function(options) {
            var viewOptions = _.defaults({}, options.viewOptions, {
                container: 'body',
                hideDelay: 25
            });
            this.view = new LoadingMaskView(viewOptions);
        },

        /**
         * Shows loading mask
         */
        showLoading: function() {
            this.view.show();
        },

        /**
         * Hides loading mask
         */
        hideLoading: function() {
            this.view.hide();
        }
    });

    return AppLoadingMaskComponent;
});
