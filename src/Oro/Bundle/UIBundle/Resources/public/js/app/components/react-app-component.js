import React from 'react';
import ReactDOM from 'react-dom';
import BaseComponent from 'oroui/js/app/components/base/component';
import loadModules from 'oroui/js/app/services/load-modules';

/**
 * Creates a React app with passed 'react_app' module and mount in _sourceElement
 */
const ReactAppComponent = BaseComponent.extend({
    app: null,

    appContainerElement: null,

    constructor: function ReactAppComponent(...args) {
        ReactAppComponent.__super__.constructor.apply(this, args);
    },

    initialize(options) {
        this._initVueApp(options);
    },

    async _initVueApp(options) {
        const {
            _sourceElement,
            _subPromises,
            name,
            reactApp,
            ...props
        } = options || {};

        this.appContainerElement = _sourceElement[0];

        this._deferredInit();
        const App = await this._loadModule(reactApp);

        if (this.disposed) {
            this._resolveDeferredInit();
            return;
        }

        this.createApp(App, props);

        this._resolveDeferredInit();
    },

    createApp(App, props) {
        this.app = ReactDOM.render(
            React.createElement(App, props),
            this.appContainerElement
        );
    },

    async _loadModule(reactApp) {
        if (!reactApp) {
            throw new Error('Missing app module name');
        }

        return await loadModules(reactApp);
    },

    dispose() {
        if (this.disposed) {
            return;
        }

        ReactDOM.unmountComponentAtNode(this.appContainerElement);
        delete this.app;

        ReactAppComponent.__super__.dispose.call(this);
    }
});

export default ReactAppComponent;
