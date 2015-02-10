/*global define*/
define(function (require) {
    'use strict';

    var LoadingMaskView,
        BaseView = require('./base/view'),
        template = require('text!../../../templates/loading-mask-view.html'),
        _ = require('underscore');

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
         * @inheritDoc
         */
        initialize: function (options) {
            _.extend(this, _.pick(options, ['loadingHint']));
            LoadingMaskView.__super__.initialize.apply(this, arguments);
        },

        /**
         * Gets data
         *
         * @returns {Object}
         * @override
         */
        getTemplateData: function () {
            var data = {
                loadingHint: this.loadingHint
            };
            return data;
        },

        /**
         * Shows loading mask
         */
        show: function (hint) {
            if (hint && _.isString(hint)) {
                this.setLoadingHint(hint);
            }
            this.$parent = this.$el.parent();
            this.$parent.addClass('loading');
            this.$el.addClass('shown');
        },

        /**
         * Hides loading mask
         */
        hide: function () {
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
        toggle: function (visible) {
            if (typeof visible === 'undefined') {
                visible = !this.hasClass('shown');
            }
            this[visible ? 'show' : 'hide']();
        },

        /**
         * Sets loading hint for this mask
         *
         * @param {string} newHint
         */
        setLoadingHint: function (newHint) {
            var oldHint = this.loadingHint;
            this.loadingHint = newHint;
            this.render();
            return oldHint;
        },

        /**
         * @inheritDoc
         */
        dispose: function () {
            if (this.disposed) {
                return;
            }
            this.hide();
            LoadingMaskView.__super__.dispose.apply(this, arguments);
        }
    });

    return LoadingMaskView;
});
