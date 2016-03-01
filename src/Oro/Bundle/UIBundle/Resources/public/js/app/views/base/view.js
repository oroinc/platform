define([
    'jquery',
    'underscore',
    'chaplin',
    '../../models/base/collection'
], function($, _, Chaplin, BaseCollection) {
    'use strict';

    var BaseView;

    /**
     * @export  oroui/js/app/views/base/view
     * @class   oroui.app.views.BaseView
     * @extends Chaplin.View
     */
    BaseView = Chaplin.View.extend({
        getTemplateFunction: function() {
            var template = this.template;
            var templateFunc = null;

            if (typeof template === 'string') {
                templateFunc = _.template(template);
                // share a compiled template with all instances built with same constructor
                this.constructor.prototype.template = templateFunc;
            } else {
                templateFunc = template;
            }

            return templateFunc;
        },

        getTemplateData: function() {
            var data;
            data = BaseView.__super__.getTemplateData.apply(this, arguments);
            if (!this.model && this.collection && this.collection instanceof BaseCollection) {
                _.extend(data, this.collection.serializeExtraData());
            }
            return data;
        },

        /**
         * Tries to find element in already declared regions, otherwise calls super _ensureElement method
         *
         * @private
         * @override
         */
        _ensureElement: function() {
            var $el;
            var el = this.el;

            if (el && typeof el === 'string' && el.substr(0, 7) === 'region:') {
                $el = this._findRegionElem(el.substr(7));
            }

            if ($el) {
                this.setElement($el, false);
            } else {
                BaseView.__super__._ensureElement.call(this);
            }
        },

        /**
         * Tries to find element by region name
         *
         * @param {string} name
         * @returns {jQuery|undefined}
         * @private
         */
        _findRegionElem: function(name) {
            var $el;
            var region = Chaplin.mediator.execute('region:find', name);
            if (region) {
                var instance = region.instance;
                if (instance.container) {
                    $el = instance.region ? $(instance.container).find(region.selector) : instance.container;
                } else {
                    $el = instance.$(region.selector);
                }
            }
            return $el;
        }
    });

    return BaseView;
});
