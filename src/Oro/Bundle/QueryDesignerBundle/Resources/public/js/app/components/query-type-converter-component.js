define(function(require) {
    'use strict';

    var QueryTypeConverterComponent;
    var _ = require('underscore');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var BaseModel = require('oroui/js/app/models/base/model');
    var QueryTypeSwitcherView = require('oroquerydesigner/js/app/views/query-type-switcher-view');

    QueryTypeConverterComponent = BaseComponent.extend({
        relatedSiblingComponents: {
            conditionBuilderComponent: 'condition-builder',
            expressionEditorComponent: 'expression-editor'
        },

        defaultOptions: {
            defaultMode: 'simple'
        },

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
            options = _.defaults(options || {}, this.defaultOptions);
            this.queryTypeStateModel = new BaseModel();
            this.listenTo(this.queryTypeStateModel, 'change:mode', this.onModeChange);
            this.view = new QueryTypeSwitcherView({
                el: options._sourceElement,
                model: this.queryTypeStateModel
            });
            this.listenTo(this.view, 'switch', this.onModeSwitch);
            this.setMode(options.defaultMode);
            QueryTypeConverterComponent.__super__.initialize.call(this, options);
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
                    expressionView.setValue(this._convertToExpression(conditionsValue));
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
         * Checks if convertion is posibility
         *
         * @param {Array} value - JSON with condition builder value
         * @returns {boolean}
         * @protected
         */
        _isConvertibleToAdvanced: function(value) {
            // TODO: use real converter to check convert possibility
            var wrongCondition = _.find(value, function(item) {
                if (item === 'AND' || item === 'OR') {
                    return false;
                }
                if (!_.isObject(item) || !item.hasOwnProperty('columnName')) {
                    return true;
                }
            });
            return wrongCondition === void 0;
        },

        /**
         * Converts condition builder value to expression
         *
         * @param {Array} value - JSON with condition builder value
         * @returns {string}
         * @protected
         */
        _convertToExpression: function(value) {
            // TODO: use real converter
            return '';
        },

        /**
         * Checks if convertion is posibility
         *
         * @param {string} value - expression editor value
         * @returns {boolean}
         * @protected
         */
        _isConvertibleToSimple: function(value) {
            // TODO: use real converter to check convert possibility
            return value.indexOf('+') === -1;
        },

        /**
         * Converts expression to condition builder value
         *
         * @param {string} value - expression editor value
         * @returns {Array}
         * @protected
         */
        _convertToConditions: function(value) {
            // TODO: use real converter
            return [];
        }
    });

    return QueryTypeConverterComponent;
});
