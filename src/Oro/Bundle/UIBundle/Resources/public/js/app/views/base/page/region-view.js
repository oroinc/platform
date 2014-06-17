/*jslint nomen:true, eqeq:true*/
/*global define*/
define([
    'jquery',
    'underscore',
    'chaplin',
    './../view'
], function ($, _, Chaplin, BaseView) {
    'use strict';

    var PageRegionView = BaseView.extend({
        listen: {
            'page_fetch:update mediator': 'onPageUpdate'
        },

        data: null,
        pageItems: [],

        /**
         * Handles page load event
         *  - stores from page data corresponded page items
         *  - renders view
         *  - dispose cached data
         *
         * @param {Object} data
         * @param {Object} actionArgs arguments of controller's action point
         */
        onPageUpdate: function (data, actionArgs) {
            this.data = _.pick(data, this.pageItems);
            this.actionArgs = actionArgs;
            this.render();
            this.data = null;
            this.actionArgs = null;
        },

        /**
         * Prevents rendering a view without page data
         *
         * @override
         */
        render: function () {
            var data;
            data = this.getTemplateData();
            if (!data) {
                return;
            }

            BaseView.prototype.render.call(this);
        },

        /**
         * Gets cached page data
         *
         * @returns {Object}
         * @override
         */
        getTemplateData: function () {
            return this.data;
        },

        /**
         * Tries to find element in already declared regions, otherwise calls super _ensureElement method
         *
         * @private
         * @override
         */
        _ensureElement: function () {
            var $el;
            if (this.el && typeof this.el === 'string') {
                $el = this._findRegionElem(this.el);
            }

            if ($el) {
                this.setElement($el, false);
            } else {
                BaseView.prototype._ensureElement.call(this);
            }
        },

        /**
         * Tries to find element by region name
         *
         * @param {string} name
         * @returns {jQuery|undefined}
         * @private
         */
        _findRegionElem: function (name) {
            var $el, region, instance;
            region = Chaplin.mediator.execute('region:find', name);
            if (region != null) {
                instance = region.instance;
                if (instance.container != null) {
                    $el = instance.region != null ? $(instance.container).find(region.selector) : instance.container;
                } else {
                    $el = instance.$(region.selector);
                }
            }
            return $el;
        }
    });

    return PageRegionView;
});
