/*global define*/
define(function (require) {
    'use strict';

    var LoadingMaskView,
        BaseView = require('./base/view'),
        template = require('text!../../../templates/loading-mask-view.html');

    LoadingMaskView = BaseView.extend({
        /** @property {string|Function} */
        template: template,

        /** @property {string} */
        containerMethod: 'append',

        /** @property {string} */
        className: 'loader-mask',

        /** @property {string} */
        loadingHint: 'Loading...',

        /**
         * Gets cached page data
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

        show: function () {
            this.$el.parent().addClass('loading');
            this.$el.addClass('shown');
        },

        hide: function () {
            this.$el.removeClass('shown');
            if (!this.$el.parent().find('>.loader-mask.shown').length) {
                // there are no more loaders for the element
                this.$el.parent().removeClass('loading');
            }
        }
    });

    return LoadingMaskView;
});
