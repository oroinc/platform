/*global define*/
define(function (require) {
    'use strict';

    var LoadingMaskView,
        BaseView = require('./base/view'),
        template = require('text!../../../templates/loading-mask-view.html');

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
        show: function () {
            this.$el.parent().addClass('loading');
            this.$el.addClass('shown');
        },

        /**
         * Hides loading mask
         */
        hide: function () {
            this.$el.removeClass('shown');
            if (!this.$el.parent().find('>.loader-mask.shown').length) {
                // there are no more loaders for the element
                this.$el.parent().removeClass('loading');
            }
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
        }
    });

    return LoadingMaskView;
});
