import _ from 'underscore';
import loadModules from 'oroui/js/app/services/load-modules';
import BaseComponent from 'oroui/js/app/components/base/component';

/**
 * Initializes jquery widget on _sourceElement
 */
const JqueryWidgetComponent = BaseComponent.extend({
    $el: null,

    widgetName: null,

    /**
     * @inheritdoc
     */
    constructor: function JqueryWidgetComponent(options) {
        JqueryWidgetComponent.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        this.$el = options._sourceElement;
        const subPromises = _.values(options._subPromises);
        const widgetOptions = _.omit(options, ['_sourceElement', '_subPromises', 'widgetModule', 'widgetName']);
        const initializeJqueryWidget = widgetName => {
            widgetName = _.isString(widgetName) ? widgetName : '';
            this.widgetName = widgetName || options.widgetName;
            this.$el[this.widgetName](widgetOptions);
            this._resolveDeferredInit();
        };

        this._deferredInit();
        if (subPromises.length) {
            // ensure that all nested components are already initialized
            Promise.all(subPromises).then(function() {
                loadModules(options.widgetModule, initializeJqueryWidget);
            });
        } else {
            loadModules(options.widgetModule, initializeJqueryWidget);
        }
    },

    /**
     * @inheritdoc
     */
    dispose: function() {
        if (this.disposed) {
            return;
        }
        if (this.$el[this.widgetName]('instance')) {
            this.$el[this.widgetName]('destroy');
        }
        return JqueryWidgetComponent.__super__.dispose.call(this);
    }
});

export default JqueryWidgetComponent;
