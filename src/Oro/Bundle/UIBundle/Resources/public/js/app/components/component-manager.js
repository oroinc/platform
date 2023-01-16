define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const tools = require('oroui/js/tools');
    const loadModules = require('oroui/js/app/services/load-modules');
    const BaseComponent = require('oroui/js/app/components/base/component');
    const ComponentShortcutsManager = require('oroui/js/component-shortcuts-manager');

    const console = window.console;

    function ComponentManager($el) {
        this.$el = $el;
        this.components = {};
        this.initPromises = {};
        this._bindContainerChangesEvents();
        this._bindInitOnEvents();
    }

    ComponentManager.prototype = {
        eventNamespace: '.component-manager',

        /**
         * Initializes Page Components for DOM element
         *
         * @param {Object?} options
         * @param {jQuery?} $container
         * @return {Promise}
         */
        init(options, $container) {
            this.initOptions = options || {};

            const elements = this._collectElements($container);
            const elementsData = this._readElementsData(elements);
            this._bindReferentialInitOnEvents($container);

            // collect nested elements' data
            elementsData.forEach(data => {
                data.subElementsData = elementsData.filter(subElementData => {
                    return data.options._sourceElement.has(subElementData.options._sourceElement).length;
                });
            });

            // initialize components in order reversed to what jQuery returns
            // (nested items have to be initialized first)
            elementsData.reverse();

            const promises = elementsData.map(data => {
                // collect promises of dependencies
                data.options._subPromises = Object.fromEntries(data.subElementsData.map(subElementData => {
                    return [subElementData.options.name, subElementData.promise];
                }));
                return (data.promise = this._loadAndInitializeComponent(data));
            });

            return $.when(...promises.filter(Boolean))
                .then((...args) => args.filter(Boolean));
        },

        /**
         * Subscribes the view to content changes
         *  - on 'content:changed' event -- updates layout
         *  - on 'content:remove' event -- disposes related components (if they are left undisposed)
         *
         * @private
         */
        _bindContainerChangesEvents() {
            // if the container catches content changed event -- updates its layout
            this.$el.on('content:changed' + this.eventNamespace, (event, {onInitialized} = {}) => {
                if (event.isDefaultPrevented()) {
                    return;
                }
                event.preventDefault();
                this.init(this.initOptions).done(() => {
                    $(event.target).trigger('content:initialized');
                    if (onInitialized) {
                        onInitialized();
                    }
                });
            });

            // if the container catches content remove event -- disposes related components
            this.$el.on('content:remove' + this.eventNamespace, event => {
                if (event.isDefaultPrevented()) {
                    return;
                }
                event.preventDefault();
                this.eraseElement($(event.target));
            });
        },

        /**
         * Disposed components initialized for an element
         *
         * @param {jQuery} $el
         */
        eraseElement($el) {
            $el.find('[data-bound-component]').each((i, el) => {
                Object.values(this.components).forEach(item => {
                    if (item.el === el) {
                        item.component.dispose();
                    }
                });
            });
        },

        /**
         * Bind suspended initialization handler on DOM-events
         *
         * @param {string[]} [events=['click', 'mouseover', 'focusin']]
         * @param {string?} selector
         * @param {jQuery?} $currentTarget
         * @private
         */
        _bindInitOnEvents(events = ['click', 'mouseover', 'focusin'], selector, $currentTarget) {
            const oppositeEvent = {
                click: null,
                mouseover: 'mouseout',
                focusin: 'focusout'
            };

            const initOnEvent = event => {
                const $initOnContainer = $currentTarget || $(event.currentTarget);
                if (!$initOnContainer.attr('data-page-component-init-on')) {
                    // already initialized on another event (when init-on has several events at once click,focusin
                    return;
                }
                $initOnContainer.removeAttr('data-page-component-init-on');
                const tempNS = _.uniqueId('.tempEventNS');
                const oppositeEventName = oppositeEvent[event.type];
                const $target = $(event.target);
                if (oppositeEventName) {
                    // listen to opposite event, and once it occurs -- invalidate initial event
                    $target.one(oppositeEventName + tempNS, () => event = null);
                }
                // add initial event to options, to make it available for handlers execution of the dispatched event
                const initOptions = Object.assign({}, this.initOptions, {
                    get _initEvent() {
                        return event;
                    }
                });
                this.init(initOptions, $initOnContainer)
                    .done(() => {
                        $target.off(tempNS);
                    });
            };

            events.forEach(eventName => {
                this.$el[selector ? 'one' : 'on'](
                    `${eventName}${this.eventNamespace}`,
                    selector || `[data-page-component-init-on*="${eventName}"]`,
                    initOnEvent
                );
            });
        },

        /**
         * @param {jQuery?} $container
         * @private
         */
        _bindReferentialInitOnEvents($container) {
            ($container || this.$el).find('[data-page-component-init-on]')
                .each((i, elem) => {
                    const $elem = $(elem);
                    if (!this._isInLayout($elem)) {
                        return;
                    }
                    const value = $elem.attr('data-page-component-init-on').trim();
                    const [, events, selector] = value.match(/^((?:click|focusin|mouseover|,)+)\s+(.+)$/) || [];
                    if (events) {
                        $elem.attr('data-page-component-init-on', 'bound');
                        this._bindInitOnEvents(events.split(','), selector, $elem);
                    }
                });
        },

        /**
         * Collect all elements that have components declaration from current layout
         *
         * @param {jQuery?} $container
         * @returns {Array.<jQuery>} elements
         * @private
         */
        _collectElements($container) {
            const elements = [];

            const shortcuts = Object.values(ComponentShortcutsManager.getAll());
            const selector = shortcuts.map(shortcut => `[${shortcut.dataAttr}]`);


            ($container || this.$el)
                .find(selector.join(','))
                .addBack(selector.join(','))
                .each((i, elem) => {
                    const $elem = $(elem);
                    if (!this._isInLayout($elem) || !this._isReadyToInit($elem)) {
                        return;
                    }

                    const elemData = $elem.data();
                    const shortcut = shortcuts.find(shortcut => shortcut.dataKey in elemData);

                    if (shortcut) {
                        const dataUpdate = ComponentShortcutsManager.getComponentData(shortcut, elemData);
                        $elem
                            .removeAttr(shortcut.dataAttr)
                            .removeData(shortcut.dataKey)
                            .data(dataUpdate);
                    }

                    elements.push($elem);
                });

            return elements;
        },

        /**
         * Check if the element belongs to the layout of contentManager's element
         *
         * @param {jQuery} $element
         * @return {boolean}
         * @private
         */
        _isInLayout($element) {
            // find nearest marked container with separate layout
            const $layoutElement = $element.parent().closest('[data-layout="separate"]', this.$el);
            return $layoutElement.is(this.$el);
        },

        /**
         * Check if the declaration of component on the element is ready to be initialized
         *  - filter out elements with components that have to be initialized on DOM event (click, focusin, mouseover)
         *
         * @param {jQuery} $element
         * @return {boolean}
         * @private
         */
        _isReadyToInit($element) {
            const $initOnContainer = $element.closest('[data-page-component-init-on]', this.$el);

            return !$initOnContainer.length || !this._isInLayout($initOnContainer) ||
                $initOnContainer.is('[data-page-component-init-on="asap"]');
        },

        /**
         * Reads initialization data from element, throws error if data is invalid
         *
         * @param {Array.<jQuery>} elements
         * @returns {Array.<{element: jQuery, module: string, options: Object}>}
         * @private
         */
        _readElementsData(elements) {
            const elementsData = elements.map($elem => {
                let data;
                try {
                    data = this._readData($elem);
                    data.element = $elem;
                    $elem.attr('data-bound-component', data.module);
                } catch (e) {
                    this._handleError(e.message, e);
                }

                this._cleanupData(data.element);

                return data;
            }).filter(Boolean);

            return elementsData;
        },

        /**
         * Read component's data attributes from the DOM element
         *
         * @param {jQuery} $elem
         * @throws {Error}
         * @private
         */
        _readData($elem) {
            const data = {
                module: $elem.data('pageComponentModule'),
                options: $.extend(true, {}, this.initOptions, $elem.data('pageComponentOptions'))
            };

            if (data.options._sourceElement) {
                data.options._sourceElement = $(data.options._sourceElement);
            } else {
                data.options._sourceElement = $elem;
            }

            data.options.name = $elem.data('pageComponentName') ||
                $elem.attr('data-ftid') || _.uniqueId('pageComponent');

            if (!data.options._sourceElement.get(0)) {
                throw new Error('Cannot resolve _sourceElement for component name "' +
                    data.options.name + '"');
            }

            return data;
        },

        /**
         * Cleanup trace of data attributes in the DOM element
         *
         * @param {jQuery} $elem
         * @private
         */
        _cleanupData($elem) {
            $elem
                .removeData('pageComponentModule')
                .removeData('pageComponentOptions')
                .removeAttr('data-page-component-module')
                .removeAttr('data-page-component-options')
                .removeAttr('data-page-component-init-on');
        },

        /**
         * Initializes component for the element
         *
         * @param {{element: jQuery, module: string, options: Object}} data
         * @returns {Promise}
         * @private
         */
        _loadAndInitializeComponent(data) {
            const initDeferred = $.Deferred();

            loadModules(data.module)
                .then(this._onComponentLoaded.bind(this, initDeferred, data.options))
                .catch(this._onComponentLoadError.bind(this, initDeferred));

            const initPromise = initDeferred
                .promise(Object.create({targetData: data}))
                .always(() => {
                    delete this.initPromises[data.options.name];
                });

            this.initPromises[data.options.name] = {promise: initPromise, dependsOn: []};

            return initPromise;
        },

        /**
         * Handles component load success
         *  - initializes the component
         *  - add the component to registry
         *
         * @param {jQuery.Deferred} initDeferred
         * @param {Object} options
         * @param {Function} Component
         * @private
         */
        _onComponentLoaded(initDeferred, options, Component) {
            if (this.disposed) {
                initDeferred.resolve();
                return;
            }

            let message;
            const name = options.name;

            if (this.components.hasOwnProperty(name)) {
                message = 'Component with the name "' + name + '" is already registered in the layout';
                this._handleError(message, new Error(message));

                // prevent interface from blocking by loader
                initDeferred.resolve();
                return;
            }

            let dependencies = this._getComponentDependencies(Component, options);

            if (!Object.keys(dependencies).length) {
                this._initializeComponent(initDeferred, options, Component);
            } else {
                try {
                    dependencies = this._resolveRelatedSiblings(name, dependencies);
                } catch (e) {
                    this._handleError(null, e);
                    // prevent interface from blocking by loader
                    initDeferred.resolve();
                    return;
                }

                $.when(...Object.values(dependencies)).then(() => {
                    options[BaseComponent.RELATED_SIBLING_COMPONENTS_PROPERTY_NAME] = dependencies;
                    this._initializeComponent(initDeferred, options, Component);
                });
            }
        },

        /**
         * Initializes the component with passed options and resolves initialization promise
         *
         * @param {jQuery.Deferred} initDeferred
         * @param {Object} options
         * @param {BaseComponent|Function} Component
         * @private
         */
        _initializeComponent(initDeferred, options, Component) {
            const name = options.name;
            const $elem = options._sourceElement;

            const component = new Component(options);
            if (component instanceof BaseComponent) {
                this.add(name, component, $elem[0]);
            }

            if (component.deferredInit) {
                component.deferredInit
                    .always(initDeferred.resolve.bind(initDeferred))
                    .fail(error => {
                        const componentModuleName = $elem.attr('data-bound-component');
                        const viewModuleName = $elem.attr('data-bound-view');
                        const widgetName = $elem.attr('data-bound-input-widget');
                        let notes = [];
                        if (name) {
                            notes.push(`component name "${name}"`);
                        }
                        if (viewModuleName) {
                            notes.push(`view module "${viewModuleName}"`);
                        }
                        if (widgetName) {
                            if (viewModuleName !== 'no-name') {
                                notes.push(` with widget "${viewModuleName}"`);
                            } else {
                                notes.push('with some widget');
                            }
                        }
                        notes = notes.length ? ` (${notes.join(', ')})` : '';
                        const message = `Initialization has failed for component "${componentModuleName}"${notes}`;
                        this._handleError(message, error);
                    });
            } else {
                initDeferred.resolve(component);
            }
        },

        /**
         * Collects dependency definition from component's prototype chain and updates componentNames using options
         *
         * @param {Function} Component constructor of a component
         * @param {Object} options configuration options for a Component
         * @return {Object.<string, string>} where key is internal name for component's instance,
         *                                  value is component's name in componentManager
         * @private
         */
        _getComponentDependencies(Component, options) {
            const dependencies = BaseComponent.getRelatedSiblingComponentNames(Component);

            // options can only change componentName of existing dependency, can not make it falsy
            const updateFromOptions = _.result(options, BaseComponent.RELATED_SIBLING_COMPONENTS_PROPERTY_NAME, {});
            Object.entries(updateFromOptions).forEach(([dependencyName, componentName]) => {
                if (componentName && dependencies[dependencyName]) {
                    dependencies[dependencyName] = componentName;
                }
            });

            return dependencies;
        },

        /**
         * Replaces componentName of dependency by the component instance or its initialization promise
         *
         * @param {string} componentName name of current component
         * @param {Object.<string, string>} dependencies
         * @throws error on circular dependency
         * @return {Object.<string, BaseComponent|Promise|undefined>}
         * @private
         */
        _resolveRelatedSiblings(componentName, dependencies) {
            const deps = Object.create({__initial__: dependencies});

            Object.entries(dependencies).forEach(([dependencyName, siblingComponentName]) => {
                let siblingComponent;
                if (this.initPromises[siblingComponentName]) {
                    if (this._hasCircularDependency(componentName, siblingComponentName)) {
                        throw new Error(`"${componentName}" component has circular dependency of sibling components`);
                    }
                    this.initPromises[componentName].dependsOn.push(siblingComponentName);
                    siblingComponent = this.initPromises[siblingComponentName].promise
                        .then(component => (deps[dependencyName] = component));
                } else {
                    siblingComponent = this.get(siblingComponentName) || void 0;
                }

                deps[dependencyName] = siblingComponent;
            });

            return deps;
        },

        /**
         * Check if there's circular dependency between two components
         *
         * @param {string} componentName name of depender component
         * @param {string} siblingComponentName name of dependee component
         * @return {boolean}
         * @private
         */
        _hasCircularDependency(componentName, siblingComponentName) {
            let name;
            const checked = [];
            const queue = [siblingComponentName].concat(this.initPromises[componentName].dependsOn);

            do {
                name = queue.pop();
                if (name === componentName) {
                    return true;
                }
                checked.push(name);
                if (this.initPromises[name]) {
                    queue.push(..._.difference(this.initPromises[name].dependsOn, checked));
                }
            } while (queue.length > 0);

            return false;
        },

        /**
         * Handles component load fail
         *
         * @param {jQuery.Deferred} initDeferred
         * @param {Error} error
         * @private
         */
        _onComponentLoadError(initDeferred, error) {
            this._handleError(null, error);
            // prevent interface from blocking by loader
            initDeferred.resolve();
        },

        /**
         * Error handler
         *  - in production mode shows user friendly message
         *  - in dev mode output in console expanded stack trace and throws the error
         *
         * @param {string|null} message
         * @param {Error} error
         * @private
         */
        _handleError(message, error) {
            if (console && console.error) {
                console.error(...[message, error].filter(Boolean));
            }
            if (!tools.debug) {
                // if there is unhandled error -- show user message
                require('chaplin').mediator
                    .execute('showMessage', 'error', __('oro.ui.components.initialization_error'));
            }
        },

        /**
         * Getter for components
         *
         * @param {string} name
         */
        get(name) {
            if (name in this.components) {
                return this.components[name].component;
            } else {
                return null;
            }
        },

        /**
         * Getter/setter for components
         *
         * @param {string} name
         * @param {BaseComponent} component to set
         * @param {HTMLElement} el
         */
        add(name, component, el) {
            if (this.disposed) {
                // in case the manager already disposed -- dispose passed component as well
                component.dispose();
                return;
            }
            this.remove(name);
            this.components[name] = {
                component: component,
                el: el
            };
            component.once('dispose', () => {
                delete this.components[name];
            });
            return component;
        },

        /**
         * Removes the component
         *
         * @param {string} name component name to remove
         */
        remove(name) {
            const item = this.components[name];
            delete this.components[name];
            if (item) {
                item.component.dispose();
            }
        },

        /**
         * Destroys all linked components
         */
        removeAll() {
            Object.keys(this.components)
                .forEach(name => this.remove(name));
        },

        /**
         * Disposes component manager
         */
        dispose() {
            this.$el.off(this.eventNamespace);
            this.removeAll();
            delete this.$el;
            this.disposed = true;
            return typeof Object.freeze === 'function' ? Object.freeze(this) : void 0;
        },

        /**
         * Applies callback function to all component in the collection
         *
         * @param {Function} callback
         * @param {Object} context
         */
        forEachComponent(callback, context) {
            Object.values(this.components).forEach(item => {
                callback.apply(context, [item.component]);
            });
        }
    };

    /**
     * @export oroui/js/app/components/component-manager
     */
    return ComponentManager;
});
