define(['underscore', 'translator', 'module', 'json'],
function(_, Translator, module) {
    'use strict';

    var dict = {};
    var debug = false;
    var add = Translator.add;
    var get = Translator.get;
    var fromJSON = Translator.fromJSON;
    var config = module.config();

    Translator.placeHolderPrefix = '{{ ';
    Translator.placeHolderSuffix = ' }}';

    /**
     * Adds a translation to Translator object and stores
     * translation id in protected dictionary
     *
     * @param {string} id
     */
    Translator.add = function(id) {
        dict[id] = 1;
        add.apply(Translator, arguments);
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
        var string = get.call(Translator, id, placeholders, number);

        //#TODO remove those 2 conditions after updating lib/translator.js in scope of BAP-14788.
        if (string === undefined) {
            string = get.call(Translator, id, placeholders, 1);
        }
        if (string === undefined) {
            string = id;
        }

        var hasTranslation = checkTranslation(id);

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
        var domains = Translator.defaultDomains;
        var checker = function(domain) {
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
        __: _.bind(Translator.get, Translator)
    });

    /**
     * Shortcut for Translator.get() method call
     *
     * @export orotranslation/js/translator
     * @returns {string}
     */
    return _.__;
});
