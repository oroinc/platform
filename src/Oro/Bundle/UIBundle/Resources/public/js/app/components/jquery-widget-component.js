define(function(require) {
    'use strict';

    var JqueryWidgetComponent;
    var _ = require('underscore');
    var tools = require('oroui/js/tools');
    var BaseComponent = require('oroui/js/app/components/base/component');

    /**
     * Initializes jquery widget on _sourceElement
     */
    JqueryWidgetComponent = BaseComponent.extend({
        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function(options) {
            var widgetOptions = _.omit(options, ['_sourceElement', 'widgetModule', 'widgetName']);
            var $elem = options._sourceElement;

            this._deferredInit();

            tools.loadModules(options.widgetModule, function initializeJqueryWidget(widgetName) {
                widgetName = _.isString(widgetName) ? widgetName : '';
                $elem[widgetName || options.widgetName](widgetOptions);
                this._resolveDeferredInit();
            }, this);
        }
    });

    return JqueryWidgetComponent;
});
