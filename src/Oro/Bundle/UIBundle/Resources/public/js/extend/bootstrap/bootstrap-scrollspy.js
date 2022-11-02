define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const MutationObserver = window.MutationObserver;

    const NAME = 'scrollspy';
    const DATA_KEY = 'bs.scrollspy';
    const EVENT_KEY = '.' + DATA_KEY;
    const DATA_API_KEY = '.data-api';
    const NAV_LINKS = '.nav > a';
    const Event = {
        LOAD_DATA_API: 'load' + EVENT_KEY + DATA_API_KEY
    };
    const Selector = {
        DATA_SPY: '[data-spy="scroll"]'
    };

    require('bootstrap-scrollspy');

    const ScrollSpy = $.fn[NAME].Constructor;
    const JQUERY_NO_CONFLICT = $.fn[NAME].noConflict();

    const OroScrollSpy = function OroScrollSpy(element, options) {
        ScrollSpy.call(this, element, options);

        this._selector += ', ' + this._config.target + ' ' + NAV_LINKS;

        if (!MutationObserver) {
            return;
        }

        const $element = $(element);
        const $collection = $element.is('body') ? $element.children() : $element;

        this._mutationObserver = new MutationObserver(_.debounce(function(mutations) {
            // Destroy scrollspy if element is not exist in the DOM
            if ($(document).find($element).length) {
                $element.scrollspy('refresh');
            } else {
                this.dispose();
            }
        }.bind(this), 50));

        $collection.each(function(index, element) {
            this._mutationObserver.observe(element, {
                attributes: true,
                childList: true,
                subtree: true,
                characterData: true
            });
        }.bind(this));
    };

    OroScrollSpy.__super__ = ScrollSpy.prototype;
    OroScrollSpy.prototype = Object.assign(Object.create(ScrollSpy.prototype), {
        constructor: OroScrollSpy,

        /**
         * Method for destroy scrollspy, disable event listener
         * disconnect observer if that exist
         */
        dispose: function() {
            if (this._mutationObserver) {
                this._mutationObserver.disconnect();
                this._mutationObserver = null;
            }

            return ScrollSpy.prototype.dispose.call(this);
        }
    });

    OroScrollSpy._jQueryInterface = function _jQueryInterface(config) {
        return this.each(function() {
            let data = $(this).data(DATA_KEY);
            const _config = typeof config === 'object' && config;

            if (!data) {
                data = new OroScrollSpy(this, _config);
                $(this).data(DATA_KEY, data);
            }
            if (typeof config === 'string') {
                if (typeof data[config] === 'undefined') {
                    throw new TypeError('No method named ' + config);
                }

                data[config]();
            }
        });
    };

    Object.defineProperties(OroScrollSpy, {
        VERSION: {
            configurable: true,
            get: function get() {
                return ScrollSpy.VERSION;
            }
        },
        Default: {
            configurable: true,
            get: function get() {
                return ScrollSpy.Default;
            }
        }
    });

    $(window).off(Event.LOAD_DATA_API).on(Event.LOAD_DATA_API, function() {
        const scrollSpys = $.makeArray($(Selector.DATA_SPY));

        for (let i = scrollSpys.length; i--;) {
            const $spy = $(scrollSpys[i]);

            ScrollSpy._jQueryInterface.call($spy, $spy.data());
        }
    });

    $.fn[NAME] = OroScrollSpy._jQueryInterface;
    $.fn[NAME].Constructor = OroScrollSpy;

    $.fn[NAME].noConflict = function() {
        $.fn[NAME] = JQUERY_NO_CONFLICT;
        return OroScrollSpy._jQueryInterface;
    };

    return OroScrollSpy;
});
