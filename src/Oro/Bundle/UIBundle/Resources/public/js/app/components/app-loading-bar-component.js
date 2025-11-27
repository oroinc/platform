import _ from 'underscore';
import mediator from 'oroui/js/mediator';
import BaseComponent from 'oroui/js/app/components/base/component';
import LoadingBarView from 'oroui/js/app/views/loading-bar-view';

const AppLoadingBarComponent = BaseComponent.extend({
    listen: {
        'page:beforeChange mediator': 'showLoading',
        'page:afterChange mediator': 'hideLoading'
    },

    /**
     * @inheritdoc
     */
    constructor: function AppLoadingBarComponent(options) {
        AppLoadingBarComponent.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
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
        const viewOptions = _.defaults({}, options.viewOptions, {
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

export default AppLoadingBarComponent;
