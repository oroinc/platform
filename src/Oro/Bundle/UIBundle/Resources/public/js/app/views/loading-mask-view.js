define(function(require) {
    'use strict';

    var LoadingMaskView;
    var BaseView = require('./base/view');
    var template = require('tpl!oroui/templates/loading-mask-view.html');
    var $ = require('jquery');
    var _ = require('underscore');

    LoadingMaskView = BaseView.extend({
        autoRender: true,

        /** @property {string|Function} */
        template: template,

        /** @property {string} */
        containerMethod: 'append',

        /** @property {string} */
        className: 'loader-mask',

        /** @property {string} */
        loadingHint: 'Loading...',

        /** @property {jQuery} */
        $parent: null,

        /**
         * Delay of loading mask hide. Allows avoid blinking.
         * Set to negative number to disable
         *
         * @property {number}
         */
        hideDelay: -1,

        /**
         * Timeout id of current hide request
         * If defined means that hide is queued
         *
         * @property {number}
         */
        hideTimeoutId: undefined,

        /**
         * @inheritDoc
         */
        constructor: function LoadingMaskView() {
            LoadingMaskView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            _.extend(this, _.pick(options, ['loadingHint', 'hideDelay']));
            $(window).on(
                'pagehide' + this.eventNamespace(),
                _.bind(function() {
                    this.hide();
                }, this)
            );
            LoadingMaskView.__super__.initialize.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        getTemplateData: function() {
            var data = {
                loadingHint: this.loadingHint
            };
            return data;
        },

        /**
         * Shows loading mask
         *
         * @param hint {string=}
         */
        show: function(hint) {
            if (hint && _.isString(hint)) {
                this.setLoadingHint(hint);
            }

            if (this.hideTimeoutId) {
                // clear deferred hide timeout
                clearTimeout(this.hideTimeoutId);
                delete this.hideTimeoutId;
            }

            if (!this.isShown()) {
                this.$parent = this.$el.parent();
                this.$parent.addClass('loading');
                this.$el.addClass('shown');
            }
        },

        /**
         * Hides loading mask with delay
         * @see this.hideDelay
         *
         * @param {boolean=} instant if true loading mask will disappear instantly
         */
        hide: function(instant) {
            if (instant || this.hideDelay < 0) {
                // instant hide
                this._hide();
            } else {
                // defer hiding if mask is visible and it is not deferred already
                if (this.isShown() && !this.hideTimeoutId) {
                    this.hideTimeoutId = setTimeout(_.bind(this._hide, this), this.hideDelay);
                }
            }
        },

        /**
         * Hides loading mask
         */
        _hide: function() {
            clearTimeout(this.hideTimeoutId);
            delete this.hideTimeoutId;

            if (!this.isShown()) {
                // nothing to do
                return;
            }

            this.$el.removeClass('shown');
            if (this.$parent && !this.$parent.find('>.loader-mask.shown').length) {
                // there are no more loaders for the element
                this.$parent.removeClass('loading');
            }
            this.$parent = null;
        },

        /**
         * Toggles loading mask
         *
         * @param {boolean=} visible
         */
        toggle: function(visible) {
            if (typeof visible === 'undefined') {
                visible = !this.isShown();
            }
            this[visible ? 'show' : 'hide']();
        },

        /**
         * Returns state of loading mask
         *
         * @returns {boolean}
         */
        isShown: function() {
            return !this.disposed && this.$el.hasClass('shown');
        },

        /**
         * Sets loading hint for this mask
         *
         * @param {string} newHint
         */
        setLoadingHint: function(newHint) {
            var oldHint = this.loadingHint;
            this.loadingHint = newHint;
            this.render();
            return oldHint;
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            $(window).off('pagehide' + this.eventNamespace());
            this.hide(true);
            LoadingMaskView.__super__.dispose.apply(this, arguments);
        }
    });

    return LoadingMaskView;
});
