import $ from 'jquery';
import _ from 'underscore';
import loadModules from 'oroui/js/app/services/load-modules';
import ExpressionEditorView from 'oroform/js/app/views/expression-editor-view';
import EntityStructureDataProvider from 'oroentity/js/app/services/entity-structure-data-provider';
import ExpressionEditorUtil from 'oroform/js/expression-editor-util';
import BaseComponent from 'oroui/js/app/components/base/component';

const ExpressionEditorComponent = BaseComponent.extend({
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
    initialize(options) {
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
    initEntityStructureDataProvider(options) {
        return EntityStructureDataProvider
            .createDataProvider(options.dataProviderConfig, this);
    },

    /**
     * Loads FunctionProviders if modules are declared in options
     *
     * @return {Promise<[ExpressionFunctionProviderInterface]>|undefined}
     */
    loadExpressionFunctionProviders(options) {
        if (options.expressionFunctionProviderModules) {
            return loadModules(options.expressionFunctionProviderModules);
        }
    },

    /**
     * Continue initialization once all promises are resolved
     *
     * @param {Object} options
     * @param {EntityStructureDataProvider} entityStructureDataProvider
     * @param {Array<ExpressionFunctionProviderInterface>} [expressionFunctionProviders]
     */
    _init(options, entityStructureDataProvider, expressionFunctionProviders) {
        if (this.disposed) {
            return;
        }

        this.entityStructureDataProvider = entityStructureDataProvider;

        const utilOptions = _.extend({
            dataSourceNames: _.keys(options.dataSource),
            entityDataProvider: entityStructureDataProvider,
            expressionFunctionProviders: expressionFunctionProviders
        }, _.pick(options, 'itemLevelLimit', 'allowedOperations', 'operations', 'supportedNames'));
        this.expressionEditorUtil = new ExpressionEditorUtil(utilOptions);

        const viewOptions = _.extend({
            el: options._sourceElement,
            autoRender: true,
            util: this.expressionEditorUtil,
            operationButtons: [{
                name: 'field',
                type: 'selectField',
                viewOptions: {
                    supportedNames: options.supportedNames,
                    dataSourceNames: _.keys(options.dataSource),
                    entityStructureDataProvider
                }
            }]
        }, _.pick(options, 'dataSource'));
        this.view = new ExpressionEditorView(viewOptions);
    },

    /**
     * Sets root entity in instance EntityStructureDataProvider
     *
     * @param {string} entityClassName
     */
    setEntity(entityClassName) {
        this.entityStructureDataProvider.setRootEntityClassName(entityClassName);
    }
});

export default ExpressionEditorComponent;
