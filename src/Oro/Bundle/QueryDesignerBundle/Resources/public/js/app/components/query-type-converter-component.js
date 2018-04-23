define(function(require) {
    'use strict';

    var QueryTypeConverterComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var BaseModel = require('oroui/js/app/models/base/model');
    var QueryTypeSwitcherView = require('oroquerydesigner/js/app/views/query-type-switcher-view');
    var FilterConfigProvider = require('oroquerydesigner/js/query-type-converter/filter-config-provider');
    var TranslatorProvider = require('oroquerydesigner/js/query-type-converter/translator-provider');
    var QueryConditionConverterToExpression =
        require('oroquerydesigner/js/query-type-converter/to-expression/query-condition-converter');
    var FieldIdTranslatorToExpression =
        require('oroquerydesigner/js/query-type-converter/to-expression/field-id-translator');

    QueryTypeConverterComponent = BaseComponent.extend({
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
        constructor: function QueryTypeConverterComponent() {
            QueryTypeConverterComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            if (!this.conditionBuilderComponent || !this.expressionEditorComponent) {
                // To converting both of switched components are required
                return;
            }
            options = _.defaults(options, this.defaultOptions);

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
        _init: function(options, filterConfigProvider) {
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
        initFilterConfigProvider: function() {
            var fieldConditionOptions = this.conditionBuilderComponent
                .view.getCriteriaOrigin('condition-item').data('options');

            var data = _.pick(fieldConditionOptions, 'filters', 'hierarchy');
            var filterConfigProvider = new FilterConfigProvider(data);
            return filterConfigProvider.loadInitModules()
                .then(function() {
                    return filterConfigProvider;
                });
        },

        /**
         * Initializes translator for conversion condition to expression
         */
        initTranslatorsToExpression: function() {
            var entityStructureDataProvider = this.expressionEditorComponent.entityStructureDataProvider;
            var filterIdTranslator = new FieldIdTranslatorToExpression(entityStructureDataProvider);
            var filterConfigProvider = this.filterConfigProvider;
            var filterTranslatorProvider = TranslatorProvider.getProvider('filterToExpression');
            var conditionTranslators = TranslatorProvider.getProvider('conditionToExpression')
                .getTranslatorConstructors()
                .map(function(ConditionTranslator) {
                    return new ConditionTranslator(
                        filterIdTranslator,
                        filterConfigProvider,
                        filterTranslatorProvider
                    );
                });
            this.toExpression = new QueryConditionConverterToExpression(conditionTranslators);
        },

        /**
         * Initializes translator for conversion expression to condition
         */
        initTranslatorsFromExpression: function() {
            // this.fromExpression = new QueryConditionTranslatorFromExpression();
        },

        /**
         * Sets mode to a state model
         *
         * @param {string} mode - one of ['simple', 'advanced']
         */
        setMode: function(mode) {
            this.queryTypeStateModel.set('mode', mode);
        },

        /**
         * Change model mode value to opposite
         */
        onModeSwitch: function() {
            if (this.queryTypeStateModel.get('disabled')) {
                return;
            }
            var mode = this.queryTypeStateModel.get('mode') === 'simple' ? 'advanced' : 'simple';
            this.setMode(mode);
        },

        /**
         * Converts value to another mode, updates views visibility and sets 'disabled' attr of the model
         * depends on value of current query type view
         *
         * @param {Backbone.Model} model
         * @param {string} value
         */
        onModeChange: function(model, value) {
            var expressionView = this.expressionEditorComponent.view;
            var conditionsView = this.conditionBuilderComponent.view;
            var isConvertible;
            if (value === 'advanced') {
                var conditionsValue = conditionsView.getValue();

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
                var expressionValue = expressionView.getValue();

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
        onConditionBuilderChange: function(value) {
            var isConvertible = this._isConvertibleToAdvanced(value);
            this.queryTypeStateModel.set('disabled', !isConvertible);
        },

        /**
         * @param {string} value
         * @param {boolean} isValid
         */
        onExpressionEditorChange: function(value, isValid) {
            var isConvertible = isValid && this._isConvertibleToSimple(value);
            this.queryTypeStateModel.set('disabled', !isConvertible);
        },

        /**
         * Checks if conversion condition to expression is possible
         *
         * @param {Array} condition - JSON with condition builder value
         * @returns {boolean}
         * @protected
         */
        _isConvertibleToAdvanced: function(condition) {
            var notEmpty = function(item) {
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
        _convertToExpression: function(condition) {
            return this.toExpression.convert(condition);
        },

        /**
         * Checks if conversion expression to condition is possible
         *
         * @param {string} expression - expression editor value
         * @returns {boolean}
         * @protected
         */
        _isConvertibleToSimple: function(expression) {
            // TODO: use real converter to check convert possibility
            return expression.indexOf('+') === -1;
        },

        /**
         * Converts expression to condition builder value
         *
         * @param {string} expression - expression editor value
         * @returns {Array}
         * @protected
         */
        _convertToConditions: function(expression) {
            // TODO: use real converter
            return [];
        }
    });

    return QueryTypeConverterComponent;
});
