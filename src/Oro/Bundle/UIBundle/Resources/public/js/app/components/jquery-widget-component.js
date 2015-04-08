/*global define*/
define(function (require) {
    'use strict';

    var JqueryWidgetComponent,
        _ = require('underscore'),
        tools = require('oroui/js/tools'),
        BaseComponent = require('oroui/js/app/components/base/component');

    /**
     * Initializes jquery widget on _sourceElement
     */
    JqueryWidgetComponent = BaseComponent.extend({
        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function (options) {
            var $elem, widgetOptions;

            widgetOptions = _.omit(options, ['_sourceElement', 'widgetModule', 'widgetName']);
            $elem = options._sourceElement;

            this._deferredInit();

            tools.loadModules(options.widgetModule, function initializeView() {
                $elem[options.widgetName](widgetOptions);
                this._resolveDeferredInit();
            }, this);
        }
    });

    return JqueryWidgetComponent;
});
