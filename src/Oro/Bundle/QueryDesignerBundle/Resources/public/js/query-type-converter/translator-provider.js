/**
 * TranslatorProvider allows to collect and fetch translator's constructors
 * (that are extends of specific base translator)
 */
class TranslatorProvider {
    /**
     * Registered providers
     * @type {Object}
     */
    static providers = {};

    /**
     * Creates an instance of translator provider
     *
     * @param {string} groupName
     * @param {function} BaseTranslator
     * @return {TranslatorProvider}
     */
    static createProvider(groupName, BaseTranslator) {
        const {providers} = TranslatorProvider;
        if (providers[groupName]) {
            throw new Error(`Provider with group name "${groupName}" already created`);
        }
        providers[groupName] = new TranslatorProvider(BaseTranslator);
        return providers[groupName];
    };

    /**
     * Returns instance of translator provider by group name
     *
     * @param {string} groupName
     * @return {TranslatorProvider|null}
     */
    static getProvider(groupName) {
        return TranslatorProvider.providers[groupName] || null;
    };


    /**
     * @param {Function} BaseTranslator
     * @constructor
     * @throws TypeError if BaseTranslator is not a function
     */
    constructor(BaseTranslator) {
        if (!(BaseTranslator instanceof Function)) {
            throw new TypeError('`BaseTranslator` is not a function');
        }
        /**
         * Map type of translator to its constructor
         * @property {Object.<string, Function>}
         */
        this.translators = {};
        this.BaseTranslator = BaseTranslator;
    }

    /**
     * Register passed translator constructor in the provider
     *
     * @param {string} type of translator
     * @param {Function} Translator constructor
     * @throws {Error} if passed translator is not instance of BaseTranslator
     */
    registerTranslator(type, Translator) {
        const prototype = Translator.prototype;
        if (prototype instanceof this.BaseTranslator) {
            this.translators[type] = Translator;
        } else {
            throw new Error(`Translator has to be instance of \`${this.BaseTranslator.name}\``);
        }
    }

    /**
     * Fetches Translator constructor by its type
     *
     * @param {string} type
     * @return {Function|null}
     */
    getTranslatorConstructor(type) {
        return this.translators[type] || null;
    }

    /**
     * Returns all registered translator constructors
     *
     * @return {Array<Function>}
     */
    getTranslatorConstructors() {
        return Object.values(this.translators);
    }
}

export default TranslatorProvider;
