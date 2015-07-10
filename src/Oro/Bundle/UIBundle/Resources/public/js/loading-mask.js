define([
    'jquery',
    'underscore',
    'orotranslation/js/translator',
    'oroui/js/mediator',
    './app/views/base/view'
], function($, _, __, mediator, BaseView) {
    'use strict';

    var LoadingMaskView;
    var console = window.console;

    /**
     * Loading mask widget
     *
     * @export oroui/js/loading-mask
     * @name   oroui.LoadingMask
     * @deprecated since version 1.6
     */
    LoadingMaskView = BaseView.extend({

        /** @property {Boolean} */
        displayed: false,

        /** @property {Boolean} */
        liveUpdate: true,

        /** @property {String} */
        className: 'loading-mask',

        /** @property {String} */
        loadingHint: __('Loading...'),

        /** @property */
        template: _.template(
            '<div class="loading-wrapper"></div>' +
            '<div class="loading-frame">' +
                '<div class="box well">' +
                    '<div class="loading-content">' +
                        '<%= loadingHint %>' +
                    '</div>' +
                '</div>' +
            '</div>'
        ),

        /**
         * Initialize
         *
         * @param {Object} options
         * @param {Boolean} [options.liveUpdate] Update position of loading animation on window scroll and resize
         */
        initialize: function(options) {
            options = options || {};

            if (mediator.execute('retrieveOption', 'debug') && console) {
                console.warn('Module "oroui/js/loading-mask" is deprecated, ' +
                    'use "oroui/js/app/views/loading-mask-view" instead');
            }

            if (_.has(options, 'liveUpdate')) {
                this.liveUpdate = options.liveUpdate;
            }

            if (this.liveUpdate) {
                var updateProxy = $.proxy(this.updatePos, this);
                $(window)
                    .on('resize.' + this.cid, updateProxy)
                    .on('scroll.' + this.cid, updateProxy);
            }
            this.loadingElement = options.loadingElement;
            LoadingMaskView.__super__.initialize.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.loadingElement) {
                this.loadingElement.data('loading-mask-visible', false);
                this.loadingElement.removeClass('hide-overlays');
            }
            $(window).off('.' + this.cid);
            LoadingMaskView.__super__.dispose.call(this);
        },

        /**
         * Show loading mask
         *
         * @return {*}
         */
        show: function() {
            this.$el.show();
            if (this.loadingElement) {
                this.loadingElement.addClass('hide-overlays');
            }
            this.displayed = true;
            this.resetPos().updatePos();
            return this;
        },

        /**
         * Update position of loading animation
         *
         * @return {*}
         * @protected
         */
        updatePos: function() {
            if (!this.displayed) {
                return this;
            }
            var $containerEl = this.$('.loading-wrapper');
            var $loadingEl = this.$('.loading-frame');

            var loadingHeight = $loadingEl.height();
            var loadingWidth = $loadingEl.width();
            var containerWidth = $containerEl.outerWidth();
            var containerHeight = $containerEl.outerHeight();
            if (loadingHeight > containerHeight) {
                $containerEl.css('height', loadingHeight + 'px');
            }

            var halfLoadingHeight = loadingHeight / 2;
            var loadingTop = containerHeight / 2  - halfLoadingHeight;
            var loadingLeft = (containerWidth - loadingWidth) / 2;

            // Move loading message to visible center of container if container is visible
            var windowHeight = $(window).outerHeight();
            var containerTop = $containerEl.offset().top;
            if (containerTop < windowHeight && (containerTop + loadingTop + loadingHeight) > windowHeight) {
                loadingTop = (windowHeight - containerTop) / 2 - halfLoadingHeight;
            }

            loadingTop = loadingHeight > 0 ? loadingTop : 0;
            loadingTop = loadingTop < containerHeight - loadingHeight ? loadingTop : containerHeight - loadingHeight;
            loadingLeft = loadingLeft > 0 ? Math.round(loadingLeft) : 0;
            loadingTop = loadingTop > 0 ? Math.round(loadingTop) : 0;

            $loadingEl.css('top', loadingTop + 'px');
            $loadingEl.css('left', loadingLeft + 'px');
            return this;
        },

        /**
         * Update position of loading animation
         *
         * @return {*}
         * @protected
         */
        resetPos: function() {
            this.$('.loading-wrapper').css('height', '100%');
            return this;
        },

        /**
         * Hide loading mask
         *
         * @return {*}
         */
        hide: function() {
            this.$el.hide();
            if (this.loadingElement) {
                this.loadingElement.removeClass('hide-overlays');
            }
            this.displayed = false;
            this.resetPos();
            return this;
        },

        /**
         * Render loading mask
         *
         * @return {*}
         */
        render: function() {
            this.$el.empty();
            this.$el.append(this.template({
                loadingHint: this.loadingHint
            }));
            this.hide();
            return this;
        }
    });

    return LoadingMaskView;
});
