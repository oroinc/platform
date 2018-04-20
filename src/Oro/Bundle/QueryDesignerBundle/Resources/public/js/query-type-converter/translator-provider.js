define(function() {
    'use strict';

    /**
     * TranslatorProvider allows to collect and fetch translator's constructors
     * (that are extends of specific base translator)
     *
     * @param {Function} BaseTranslator
     * @constructor
     * @throws TypeError if BaseTranslator is not a function
     */
    function TranslatorProvider(BaseTranslator) {
        if (!(BaseTranslator instanceof Function)) {
            throw new TypeError('`BaseTranslator` is not a function');
        }
        this.translators = {};
        this.BaseTranslator = BaseTranslator;
    }

    Object.assign(TranslatorProvider.prototype, {
        constructor: TranslatorProvider,

        /**
         * Map type of translator to its constructor
         * @type {Object.<string, Function>}
         */
        translators: null,

        /**
         * Register passed translator constructor in the provider
         *
         * @param {string} type of translator
         * @param {Function} Translator constructor
         * @throws {Error} if passed translator is not instance of BaseTranslator
         */
        registerTranslator: function(type, Translator) {
            var prototype = Translator.prototype;
            if (prototype instanceof this.BaseTranslator) {
                this.translators[type] = Translator;
            } else {
                throw new Error('Translator has to be instance of `' + this.BaseTranslator.name + '`');
            }
        },

        /**
         * Fetches Translator constructor by its type
         *
         * @param {string} type
         * @return {Function|null}
         */
        getTranslatorConstructor: function(type) {
            return this.translators[type] || null;
        },

        /**
         * Returns all registered translator constructors
         *
         * @return {Array<Function>}
         */
        getTranslatorConstructors: function() {
            return Object.values(this.translators);
        }
    });

    /**
     * Registered providers
     * @type {Object}
     * @static
     */
    TranslatorProvider.providers = {};

    /**
     *
     * @param BaseTranslator
     * @return {TranslatorProvider}
     * @static
     */
    TranslatorProvider.getProviderOf = function(BaseTranslator) {
        var providers = TranslatorProvider.providers;
        var name = BaseTranslator.name;
        if (!providers[name]) {
            providers[name] = new TranslatorProvider(BaseTranslator);
        }
        return providers[name];
    };


    return TranslatorProvider;
});
