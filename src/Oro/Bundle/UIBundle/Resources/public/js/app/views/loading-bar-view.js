/**
 * This component display line loader when page is loading and ajax request sending
 */
import BaseView from 'oroui/js/app/views/base/view';
import $ from 'jquery';

const LoadingBarView = BaseView.extend({
    autoRender: true,

    optionNames: BaseView.prototype.optionNames.concat([
        'ajaxLoading', 'pageLoading'
    ]),

    /**
     * @property {string}
     */
    className: 'loading-bar',

    /**
     * @property {string}
     */
    container: 'body',

    /**
     * @property {Boolean}
     */
    ajaxLoading: false,

    /**
     * @property {Boolean}
     */
    pageLoading: false,

    /**
     * @property {Boolean}
     */
    active: false,

    /**
     * @property {Boolean}
     */
    requestPending: false,

    /**
     * @inheritdoc
     */
    constructor: function LoadingBarView(options) {
        LoadingBarView.__super__.constructor.call(this, options);
    },

    /**
     * @constructor
     */
    initialize: function() {
        this.bindEvents(this);
    },

    /**
     * Bind ajaxStart, ajaxComplete, ready and load listeners
     */
    bindEvents: function() {
        if (this.pageLoading) {
            $(document).on('ready' + this.eventNamespace(), () => {
                this.showLoader();
            });

            $(window).on('load' + this.eventNamespace(), () => {
                this.hideLoader();
            });
        }

        if (this.ajaxLoading) {
            $(document).on('ajaxStart' + this.eventNamespace(), () => {
                this.showLoader();
            });

            $(document).on('ajaxStop' + this.eventNamespace(), () => {
                this.hideLoader();
            });
        }
    },

    showLoader: function() {
        if (this.active) {
            return;
        }

        this.$el.addClass('show');
        this.active = true;
    },

    hideLoader: function(callback) {
        if (!this.active) {
            return;
        }

        const loaderWidth = this.$el.width();

        this.$el.width(loaderWidth).css({animation: 'none'}).width('var(--final-width, 100%)');
        this.$el.delay(200).fadeOut(300, () => {
            if (this.disposed) {
                return;
            }
            this.$el
                .attr('style', null)
                .removeClass('show');
            if (callback) {
                callback();
            }
        });
        this.active = false;
        this.isRequestPending(false);
    },

    setProgress(percentNumber) {
        this.$el.width(`${percentNumber}%`);
    },

    /**
     * @param {Boolean} [state] True to notify that an application is going to send a request, false otherwise.
     *
     * @returns {Boolean} True if an application is going to send a request, false otherwise.
     */
    isRequestPending(state) {
        if (state !== undefined) {
            this.requestPending = state;
        }

        return this.requestPending;
    },

    dispose: function() {
        if (this.disposed) {
            return;
        }

        $(document).off(this.eventNamespace());
        $(window).off(this.eventNamespace());

        LoadingBarView.__super__.dispose.call(this);
    }
});

export default LoadingBarView;
