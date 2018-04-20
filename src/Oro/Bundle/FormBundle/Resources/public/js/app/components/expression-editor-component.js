define(function(require) {
    'use strict';

    var ExpressionEditorComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var tools = require('oroui/js/tools');
    var ExpressionEditorView = require('oroform/js/app/views/expression-editor-view');
    var EntityStructureDataProvider = require('oroentity/js/app/services/entity-structure-data-provider');
    var BaseComponent = require('oroui/js/app/components/base/component');

    ExpressionEditorComponent = BaseComponent.extend({
        /**
         * @inheritDoc
         */
        constructor: function ExpressionEditorComponent(options) {
            ExpressionEditorComponent.__super__.constructor.call(this, options);
        },

        /**
         * @param {Object} options
         * @param {Object} options.dataProviderConfig
         * @param {Array<string>} options.expressionFunctionProviderModules
         */
        initialize: function(options) {
            this._deferredInit();
            $.when(
                this.initEntityStructureDataProvider(options),
                this.loadExpressionFunctionProviders(options)
            )
                .then(this._init.bind(this, options))
                .then(this._resolveDeferredInit.bind(this));

            ExpressionEditorComponent.__super__.initialize.call(this, options);
        },

        /**
         * Initializes entity structure data provider
         *
         * @param {Object} options
         * @return {Promise<EntityStructureDataProvider>}
         */
        initEntityStructureDataProvider: function(options) {
            return EntityStructureDataProvider
                .createDataProvider(options.dataProviderConfig, this);
        },

        /**
         * Loads FunctionProviders if modules are declared in options
         *
         * @return {Promise<[ExpressionFunctionProviderInterface]>|undefined}
         */
        loadExpressionFunctionProviders: function(options) {
            if (options.expressionFunctionProviderModules) {
                return tools.loadModules(options.expressionFunctionProviderModules)
                    .then(function() {
                        // combines separate providers from arguments to single array of providers
                        return _.values(arguments);
                    });
            }
        },

        /**
         * Continue initialization once all promises are resolved
         *
         * @param {Object} options
         * @param {EntityStructureDataProvider} entityStructureDataProvider
         * @param {Array<ExpressionFunctionProviderInterface>} [expressionFunctionProviders]
         */
        _init: function(options, entityStructureDataProvider, expressionFunctionProviders) {
            if (this.disposed) {
                return;
            }

            this.provider = entityStructureDataProvider;

            var viewOptions = _.extend({
                el: options._sourceElement,
                autoRender: true,
                entityDataProvider: entityStructureDataProvider,
                expressionFunctionProviders: expressionFunctionProviders
            }, _.omit(options, '_sourceElement', '_subPromises', 'dataProviderConfig'));

            this.view = new ExpressionEditorView(viewOptions);
        },

        /**
         * Sets root entity in instance EntityStructureDataProvider
         *
         * @param {string} entityClassName
         */
        setEntity: function(entityClassName) {
            this.provider.setRootEntityClassName(entityClassName);
        }
    });

    return ExpressionEditorComponent;
});
