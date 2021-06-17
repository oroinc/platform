define(function(require, exports, module) {
    'use strict';

    const _ = require('underscore');
    const Translator = require('orotranslation/lib/translator');
    const config = require('module-config').default(module.id);

    window.Translator = Translator; // add global variable for translations JSONP-loader Translator.fromJSON({...})

    const dict = {};
    let debug = false;
    const add = Translator.add;
    const trans = Translator.trans;
    const transChoice = Translator.transChoice;
    const fromJSON = Translator.fromJSON;

    Translator.placeHolderPrefix = '{{ ';
    Translator.placeHolderSuffix = ' }}';

    /**
     * Adds a translation to Translator object and stores
     * translation id in protected dictionary
     *
     * @param {string} id
     */
    Translator.add = function(id, ...rest) {
        dict[id] = 1;
        add.call(Translator, id, ...rest);
    };

    /**
     * Fetches translation by its id,
     * but before checks if the id was registered in dictionary
     *
     * @param {string} id
     * @param {Object} placeholders
     * @param {Number} number
     * @returns {string}
     */
    Translator.get = function(id, placeholders, number) {
        // The Translator lib deletes all properties from placeholders Object
        // We should clone it to prevent loosing placeholders data from Object given by reference
        if (typeof placeholders !== 'undefined') {
            placeholders = _.clone(placeholders);
        }
        let string;
        if (typeof number === 'undefined') {
            string = trans.call(Translator, id, placeholders);
        } else {
            string = transChoice.call(Translator, id, number, placeholders);
        }

        const hasTranslation = checkTranslation(id);

        if (!config.debugTranslator) {
            return string;
        }

        if (hasTranslation) {
            if (string.indexOf(']JS') === -1) {
                return '[' + string + ']JS';
            } else {
                return string;
            }
        } else {
            if (string.indexOf('---!!!JS') === -1) {
                return '!!!---' + string + '---!!!JS';
            } else {
                return string;
            }
        }
    };

    /**
     * Parses JSON data in store translations inside,
     * also turns on debug mode if in data was such directive
     *
     * @param {Object} data
     * @returns {Object} Translator
     */
    Translator.fromJSON = function(data) {
        if (typeof data === 'string') {
            data = JSON.parse(data);
        }
        debug = data.debug || false;
        return fromJSON.call(Translator, data);
    };

    /**
     * Checks if translation for passed id exist, if it's debug mode
     * and there's no translation - output error message in console
     *
     * @param {string} id
     */
    function checkTranslation(id) {
        if (!debug) {
            return true;
        }
        let domains = Translator.defaultDomains;
        const checker = function(domain) {
            return dict.hasOwnProperty(domain ? domain + ':' + id : id);
        };
        domains = _.union([undefined], _.isArray(domains) ? domains : [domains]);
        if (!_.some(domains, checker)) {
            window.console.error('Untranslated: %s', id);
            return false;
        }
        return true;
    }

    _.mixin({
        /**
         * Shortcut for Translator.get() method call,
         * Due to it's underscore mixin, it can be used inside templates
         * @returns {string}
         */
        __: Translator.get.bind(Translator)
    });

    /**
     * Shortcut for Translator.get() method call
     *
     * @export orotranslation/js/translator
     * @returns {string}
     */
    return _.__;
});
