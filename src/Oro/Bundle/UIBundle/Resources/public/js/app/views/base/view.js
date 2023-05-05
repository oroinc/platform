define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const Chaplin = require('chaplin');
    const BaseCollection = require('../../models/base/collection');

    const BaseView = Chaplin.View.extend(/** @lends BaseView.prototype */{
        /**
         * @inheritdoc
         */
        constructor: function BaseView(options) {
            BaseView.__super__.constructor.call(this, options);
        },

        getTemplateFunction: function(templateKey) {
            templateKey = templateKey || 'template';
            const template = this[templateKey];
            let templateFunc = null;

            // If templateSelector is set in an extended view
            if (this[templateKey + 'Selector'] && $(this[templateKey + 'Selector']).length) {
                templateFunc = _.template($(this[templateKey + 'Selector']).html());
            } else if (typeof template === 'string') {
                templateFunc = _.template(template);
                // share a compiled template with all instances built with same constructor
                this.constructor.prototype[templateKey] = templateFunc;
            } else {
                templateFunc = template;
            }

            if (typeof templateFunc === 'function') {
                return data => templateFunc(data).trim();
            }

            return templateFunc;
        },

        getTemplateData: function() {
            const data = BaseView.__super__.getTemplateData.call(this);
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
            let $el;
            const el = this.el;

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
            let $el;
            const region = Chaplin.mediator.execute('region:find', name);
            if (region) {
                const instance = region.instance;
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
            let $el;
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
