define(function(require) {
    'use strict';

    const _ = require('underscore');
    const ExpressionEditorView = require('oroform/js/app/views/expression-editor-view');
    const EntityStructureDataProvider = require('oroentity/js/app/services/entity-structure-data-provider');
    const BaseComponent = require('oroui/js/app/components/base/component');

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
         */
        initialize: function(options) {
            this._deferredInit();
            EntityStructureDataProvider
                .createDataProvider(options.dataProviderConfig, this)
                .then(function(provider) {
                    this._resolveDeferredInit();
                    if (!this.disposed) {
                        this.initExpressionEditorView(options, provider);
                    }
                }.bind(this));
            ExpressionEditorComponent.__super__.initialize.call(this, options);
        },

        /**
         * Initializes ExpressionEditorView
         *
         * @param {Object} options
         * @param {EntityStructureDataProvider} provider
         */
        initExpressionEditorView: function(options, provider) {
            const viewOptions = _.extend({
                el: options._sourceElement,
                autoRender: true,
                entityDataProvider: provider
            }, _.omit(options, '_sourceElement', '_subPromises', 'dataProviderConfig'));

            this.view = new ExpressionEditorView(viewOptions);
        }
    });

    return ExpressionEditorComponent;
});
