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
     * @class   BaseView
     * @extends Chaplin.View
     */
    BaseView = Chaplin.View.extend(/** @lends BaseView.prototype */{
        /**
         * @inheritDoc
         */
        constructor: function BaseView() {
            BaseView.__super__.constructor.apply(this, arguments);
        },

        getTemplateFunction: function(templateKey) {
            templateKey = templateKey || 'template';
            var template = this[templateKey];
            var templateFunc = null;

            // If templateSelector is set in a extended view
            if (this[templateKey + 'Selector']) {
                templateFunc = _.template($(this[templateKey + 'Selector']).html());
            } else if (typeof template === 'string') {
                templateFunc = _.template(template);
                // share a compiled template with all instances built with same constructor
                this.constructor.prototype[templateKey] = templateFunc;
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
                this.setElement($el);
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
        },

        render: function() {
            BaseView.__super__.render.call(this);
            this.initControls();
            return this;
        }
    }, {
        /**
         * Resolves element declared in view's options
         *
         * @param {string|jQuery} el value of view's element declaration in options
         * @return {jQuery}
         */
        resolveElOption: function(el) {
            var $el;
            if (typeof el === 'string' && el.substr(0, 7) === 'region:') {
                $el = BaseView.prototype._findRegionElem(el.substr(7));
            } else {
                $el = $(el);
            }
            return $el;
        }
    });

    return BaseView;
});
