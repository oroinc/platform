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
        $el: null,

        widgetName: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.$el = options._sourceElement;
            var widgetOptions = _.omit(options, ['_sourceElement', 'widgetModule', 'widgetName']);

            this._deferredInit();

            tools.loadModules(options.widgetModule, function initializeJqueryWidget(widgetName) {
                widgetName = _.isString(widgetName) ? widgetName : '';
                this.widgetName = widgetName || options.widgetName;
                this.$el[this.widgetName](widgetOptions);
                this._resolveDeferredInit();
            }, this);
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
