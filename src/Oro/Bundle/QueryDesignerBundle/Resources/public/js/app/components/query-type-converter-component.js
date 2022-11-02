import $ from 'jquery';
import _ from 'underscore';
import BaseComponent from 'oroui/js/app/components/base/component';
import BaseModel from 'oroui/js/app/models/base/model';
import QueryTypeSwitcherView from 'oroquerydesigner/js/app/views/query-type-switcher-view';
import FilterConfigProvider from 'oroquerydesigner/js/query-type-converter/filter-config-provider';
import TranslatorProvider from 'oroquerydesigner/js/query-type-converter/translator-provider';
import QueryConditionConverterToExpression
    from 'oroquerydesigner/js/query-type-converter/to-expression/query-condition-converter';
import FieldIdTranslatorToExpression from 'oroquerydesigner/js/query-type-converter/to-expression/field-id-translator';

const QueryTypeConverterComponent = BaseComponent.extend({
    relatedSiblingComponents: {
        conditionBuilderComponent: 'condition-builder',
        expressionEditorComponent: 'expression-editor'
    },

    defaultOptions: {
        entityStructureDataProviderConfig: {
            filterPreset: 'querydesigner'
        },
        defaultMode: 'simple'
    },

    /**
     * @type {FilterConfigProvider}
     */
    filterConfigProvider: null,

    /**
     * @inheritDoc
     */
    constructor: function QueryTypeConverterComponent(options) {
        QueryTypeConverterComponent.__super__.constructor.call(this, options);
    },

    /**
     * @inheritDoc
     */
    initialize(options) {
        if (!this.conditionBuilderComponent || !this.expressionEditorComponent) {
            // To converting both of switched components are required
            return;
        }
        options = {...this.defaultOptions, ...options};

        this._deferredInit();
        $.when(
            this.initFilterConfigProvider()
        )
            .then(this._init.bind(this, options))
            .then(this._resolveDeferredInit.bind(this));

        QueryTypeConverterComponent.__super__.initialize.call(this, options);
    },

    /**
     * Continue initialization once all promises are resolved
     *
     * @param {Object} options
     * @param {FilterConfigProvider} filterConfigProvider
     * @protected
     */
    _init(options, filterConfigProvider) {
        if (this.disposed) {
            return;
        }
        this.filterConfigProvider = filterConfigProvider;

        // init translators
        this.initTranslatorsToExpression();
        this.initTranslatorsFromExpression();

        // init state model
        this.queryTypeStateModel = new BaseModel();
        this.listenTo(this.queryTypeStateModel, 'change:mode', this.onModeChange);

        // init type switcher view
        this.view = new QueryTypeSwitcherView({
            el: options._sourceElement,
            model: this.queryTypeStateModel
        });
        this.listenTo(this.view, 'switch', this.onModeSwitch);

        this.setMode(options.defaultMode);
    },

    /**
     * Initializes filter config provider
     *
     * @return {Promise<FilterConfigProvider>}
     */
    initFilterConfigProvider() {
        const fieldConditionOptions = this.conditionBuilderComponent
            .view.getCriteriaOrigin('condition-item').data('options');

        const data = _.pick(fieldConditionOptions, 'filters', 'hierarchy');
        const filterConfigProvider = new FilterConfigProvider(data);
        return filterConfigProvider.loadInitModules()
            .then(() => filterConfigProvider);
    },

    /**
     * Initializes translator for conversion condition to expression
     */
    initTranslatorsToExpression() {
        const entityStructureDataProvider = this.expressionEditorComponent.entityStructureDataProvider;
        const filterIdTranslator = new FieldIdTranslatorToExpression(entityStructureDataProvider);
        const filterConfigProvider = this.filterConfigProvider;
        const filterTranslatorProvider = TranslatorProvider.getProvider('filterToExpression');
        const conditionTranslators = TranslatorProvider.getProvider('conditionToExpression')
            .getTranslatorConstructors()
            .map(ConditionTranslator =>
                new ConditionTranslator(filterIdTranslator, filterConfigProvider, filterTranslatorProvider));
        this.toExpression = new QueryConditionConverterToExpression(conditionTranslators);
    },

    /**
     * Initializes translator for conversion expression to condition
     */
    initTranslatorsFromExpression() {
        // this.fromExpression = new QueryConditionTranslatorFromExpression();
    },

    /**
     * Sets mode to a state model
     *
     * @param {string} mode - one of ['simple', 'advanced']
     */
    setMode(mode) {
        this.queryTypeStateModel.set('mode', mode);
    },

    /**
     * Change model mode value to opposite
     */
    onModeSwitch() {
        if (this.queryTypeStateModel.get('disabled')) {
            return;
        }
        const mode = this.queryTypeStateModel.get('mode') === 'simple' ? 'advanced' : 'simple';
        this.setMode(mode);
    },

    /**
     * Converts value to another mode, updates views visibility and sets 'disabled' attr of the model
     * depends on value of current query type view
     *
     * @param {Backbone.Model} model
     * @param {string} value
     */
    onModeChange(model, value) {
        const expressionView = this.expressionEditorComponent.view;
        const conditionsView = this.conditionBuilderComponent.view;
        let isConvertible;
        if (value === 'advanced') {
            const conditionsValue = conditionsView.getValue();

            // update views visibility and listening
            conditionsView.$el.hide();
            this.stopListening(conditionsView, 'change');
            conditionsView.setValue([]);

            // check if it's transition from opposite mode and not setting initial state
            if (model.previous('mode') === 'simple' && !_.isEmpty(conditionsValue)) {
                expressionView.setValue(this._convertToExpression(conditionsValue) || '');
            }
            expressionView.$el.show();
            this.listenTo(expressionView, 'change', this.onExpressionEditorChange);

            // check possibility to convert in opposite mode
            if (expressionView.isValid()) {
                isConvertible = this._isConvertibleToSimple(expressionView.getValue());
            } else {
                isConvertible = false;
            }
        } else if (value === 'simple') {
            const expressionValue = expressionView.getValue();

            // update views visibility and listening
            expressionView.$el.hide();
            this.stopListening(conditionsView, 'change');
            expressionView.setValue('');

            // check if it's transition from opposite mode and not setting initial state
            if (model.previous('mode') === 'advanced' && _.trim(expressionValue) !== '') {
                conditionsView.setValue(this._convertToConditions(expressionValue));
            }
            conditionsView.$el.show();
            this.listenTo(conditionsView, 'change', this.onConditionBuilderChange);

            // check possibility to convert in opposite mode
            isConvertible = this._isConvertibleToAdvanced(conditionsView.getValue());
        }
        this.queryTypeStateModel.set('disabled', !isConvertible);
    },

    /**
     * @param {Array} value
     */
    onConditionBuilderChange(value) {
        const isConvertible = this._isConvertibleToAdvanced(value);
        this.queryTypeStateModel.set('disabled', !isConvertible);
    },

    /**
     * @param {string} value
     * @param {boolean} isValid
     */
    onExpressionEditorChange(value, isValid) {
        const isConvertible = isValid && this._isConvertibleToSimple(value);
        this.queryTypeStateModel.set('disabled', !isConvertible);
    },

    /**
     * Checks if conversion condition to expression is possible
     *
     * @param {Array} condition - JSON with condition builder value
     * @returns {boolean}
     * @protected
     */
    _isConvertibleToAdvanced(condition) {
        const notEmpty = item => {
            if (_.isArray(item)) {
                return _.all(item, notEmpty);
            }
            return item === 'AND' || item === 'OR' || _.isObject(item) && !_.isEmpty(item);
        };
        return _.isEmpty(condition) ||
            _.all(condition, notEmpty) && this._convertToExpression(condition) !== void 0;
    },

    /**
     * Converts condition builder value to expression
     *
     * @param {Array} condition - JSON with condition builder value
     * @returns {string|undefined}
     * @protected
     */
    _convertToExpression(condition) {
        return this.toExpression.convert(condition);
    },

    /**
     * Checks if conversion expression to condition is possible
     *
     * @param {string} expression - expression editor value
     * @returns {boolean}
     * @protected
     */
    _isConvertibleToSimple(expression) {
        // @todo: use real converter to check convert possibility
        return expression.indexOf('+') === -1;
    },

    /**
     * Converts expression to condition builder value
     *
     * @param {string} expression - expression editor value
     * @returns {Array}
     * @protected
     */
    _convertToConditions(expression) {
        // @todo: use real converter
        return [];
    }
});

export default QueryTypeConverterComponent;
