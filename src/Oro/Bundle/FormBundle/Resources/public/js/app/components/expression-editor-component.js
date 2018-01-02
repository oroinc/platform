define(function(require) {
    'use strict';

    var ExpressionEditorComponent;
    var _ = require('underscore');
    var ExpressionEditorView = require('oroform/js/app/views/expression-editor-view');
    var EntityStructureDataProvider = require('oroentity/js/app/services/entity-structure-data-provider');
    var BaseComponent = require('oroui/js/app/components/base/component');

    ExpressionEditorComponent = BaseComponent.extend({
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
            var viewOptions = _.extend({
                el: options._sourceElement,
                autoRender: true,
                entityDataProvider: provider
            }, _.omit(options, '_sourceElement', '_subPromises', 'dataProviderConfig'));

            this.view = new ExpressionEditorView(viewOptions);
        }
    });

    return ExpressionEditorComponent;
});
