define(function(require) {
    'use strict';

    var ExpressionEditorComponent;
    var _ = require('underscore');
    var ExpressionEditorView = require('oroform/js/app/views/expression-editor-view');
    var EntityStructureDataProvider = require('oroentity/js/app/services/entity-structure-data-provider');
    var BaseComponent = require('oroui/js/app/components/base/component');

    ExpressionEditorComponent = BaseComponent.extend({
        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function(options) {
            this._deferredInit();
            var providerOptions = _.pick(options, 'filterPreset', 'exclude', 'include');
            var viewOptions = _.extend({
                el: options._sourceElement,
                autoRender: true
            }, _.omit(options, '_sourceElement', '_subPromises'));
            EntityStructureDataProvider.createDataProvider(providerOptions, this).then(function(viewOptions, provider) {
                this._resolveDeferredInit();
                if (this.disposed) {
                    return;
                }
                viewOptions.entityDataProvider = provider;
                this.view = new ExpressionEditorView(viewOptions);
            }.bind(this, viewOptions));
            ExpressionEditorComponent.__super__.initialize.call(this, options);
        }
    });

    return ExpressionEditorComponent;
});
