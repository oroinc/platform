define(function(require) {
    'use strict';

    const _ = require('underscore');
    const mediator = require('oroui/js/mediator');
    const BaseComponent = require('oroui/js/app/components/base/component');
    const LoadingMaskView = require('oroui/js/app/views/loading-mask-view');

    const AppLoadingMaskComponent = BaseComponent.extend({
        listen: {
            'page:beforeChange mediator': 'showLoading',
            'page:afterChange mediator': 'hideLoading'
        },

        /**
         * @inheritdoc
         */
        constructor: function AppLoadingMaskComponent(options) {
            AppLoadingMaskComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
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
            const viewOptions = _.defaults({}, options.viewOptions, {
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
