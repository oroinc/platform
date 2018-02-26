define(function(require) {
    'use strict';

    var JqueryWidgetComponent;
    var _ = require('underscore');
    var $ = require('jquery');
    var tools = require('oroui/js/tools');
    var BaseComponent = require('oroui/js/app/components/base/component');

    /**
     * Initializes jquery widget on _sourceElement
     */
    JqueryWidgetComponent = BaseComponent.extend({
        $el: null,

        widgetName: null,

        /**
         * @inheritDoc
         */
        constructor: function JqueryWidgetComponent() {
            JqueryWidgetComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.$el = options._sourceElement;
            var subPromises = _.values(options._subPromises);
            var widgetOptions = _.omit(options, ['_sourceElement', '_subPromises', 'widgetModule', 'widgetName']);
            var initializeJqueryWidget = _.bind(function(widgetName) {
                widgetName = _.isString(widgetName) ? widgetName : '';
                this.widgetName = widgetName || options.widgetName;
                this.$el[this.widgetName](widgetOptions);
                this._resolveDeferredInit();
            }, this);

            this._deferredInit();
            if (subPromises.length) {
                // ensure that all nested components are already initialized
                $.when.apply($, subPromises).then(function() {
                    tools.loadModules(options.widgetModule, initializeJqueryWidget);
                });
            } else {
                tools.loadModules(options.widgetModule, initializeJqueryWidget);
            }
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            if (this.$el[this.widgetName]('instance')) {
                this.$el[this.widgetName]('destroy');
            }
            return JqueryWidgetComponent.__super__.dispose.apply(this, arguments);
        }
    });

    return JqueryWidgetComponent;
});
