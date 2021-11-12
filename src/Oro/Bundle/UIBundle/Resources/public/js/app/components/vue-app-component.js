import {createApp} from 'vue';
import BaseComponent from 'oroui/js/app/components/base/component';
import loadModules from 'oroui/js/app/services/load-modules';

/**
 * Creates a Vue app with passed 'vue_app' module and mount in _sourceElement
 */
const VueAppComponent = BaseComponent.extend({
    app: null,

    appContainerElement: null,

    constructor: function VueAppComponent(...args) {
        VueAppComponent.__super__.constructor.apply(this, args);
    },

    initialize(options) {
        this._initVueApp(options);
    },

    async _initVueApp(options = {}) {
        const {
            _sourceElement,
            _subPromises,
            name,
            vueApp,
            ...props
        } = options || {};

        this.appContainerElement = _sourceElement[0];

        this._deferredInit();

        const App = await this._loadModule(vueApp);

        if (this.disposed) {
            this._resolveDeferredInit();
            return;
        }

        this.createApp(App, props);

        this._resolveDeferredInit();
    },

    beforeAppMount() {},

    createApp(App, props) {
        this.app = createApp(App, props);

        this.beforeAppMount();
        this.appMount(this.appContainerElement);
    },

    async _loadModule(vueAppModule) {
        if (!vueAppModule) {
            throw new Error('Missing app module name');
        }

        return await loadModules(vueAppModule);
    },

    appMount(mountElement) {
        if (!mountElement) {
            return console.warn('Missing DOM element to mount app instance');
        }

        if (!this.app) {
            return;
        }

        this.app.mount(mountElement);
    },

    dispose() {
        if (this.disposed) {
            return;
        }

        this.app.unmount();
        delete this.app;

        VueAppComponent.__super__.dispose.call(this);
    }
});

export default VueAppComponent;
