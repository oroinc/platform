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
        init: function(options, $container) {
            this.initOptions = options || {};

            const elements = this._collectElements($container);
            const elementsData = this._readElementsData(elements);

            // collect nested elements' data
            _.each(elementsData, function(data) {
                data.subElementsData = _.filter(elementsData, function(subElementData) {
                    return data.options._sourceElement.has(subElementData.options._sourceElement).length;
                });
            });

            // initialize components in order reversed to what jQuery returns
            // (nested items have to be initialized first)
            elementsData.reverse();

            const promises = _.map(elementsData, function(data) {
                // collect promises of dependencies
                data.options._subPromises = _.object(_.map(data.subElementsData, function(subElementData) {
                    return [subElementData.options.name, subElementData.promise];
                }));
                return (data.promise = this._loadAndInitializeComponent(data));
            }, this);

            return $.when(..._.compact(promises)).then(function(...args) {
                return _.compact(args);
            });
        },

        /**
         * Subscribes the view to content changes
         *  - on 'content:changed' event -- updates layout
         *  - on 'content:remove' event -- disposes related components (if they are left undisposed)
         *
         * @protected
         */
        _bindContainerChangesEvents: function() {
            const self = this;

            // if the container catches content changed event -- updates its layout
            this.$el.on('content:changed' + this.eventNamespace, event => {
                if (event.isDefaultPrevented()) {
                    return;
                }
                event.preventDefault();
                this.init(this.initOptions).done(function() {
                    $(event.target).trigger('content:initialized');
                });
            });

            // if the container catches content remove event -- disposes related components
            this.$el.on('content:remove' + this.eventNamespace, event => {
                if (event.isDefaultPrevented()) {
                    return;
                }
                event.preventDefault();
                $(event.target).find('[data-bound-component]').each(function() {
                    const el = this;
                    _.each(self.components, function(item) {
                        if (item.el === el) {
                            item.component.dispose();
                        }
                    });
                });
            });

            const initOnEvent = event => {
                const tempNS = _.uniqueId('.tempEventNS');
                const {oppositeEventName} = event.data;
                const $target = $(event.target);
                const $container = $(event.currentTarget);
                $container.removeAttr('data-page-component-init-on');
                if (oppositeEventName) {
                    // listen to opposite event, and once it occurs -- invalidate initial event
                    $target.one(oppositeEventName + tempNS, () => event = null);
                }
                this.init(this.initOptions, $container)
                    .done(() => {
                        $target.off(tempNS);
                        if (event) { // re-trigger initial event if it's still valid after initialization
                            $target.trigger(event);
                        }
                    });
            };

            [
                {event: 'click', opposite: null},
                {event: 'mouseover', opposite: 'mouseout'},
                {event: 'focusin', opposite: 'focusout'}
            ].forEach(({event, opposite}) => {
                this.$el.on(
                    `${event}${this.eventNamespace}`,
                    `[data-page-component-init-on*="${event}"]`,
                    {oppositeEventName: opposite},
                    initOnEvent
                );
            });
        },

        /**
         * Collect all elements that have components declaration from current layout
         *
         * @param {jQuery?} $container
         * @returns {Array.<jQuery>} elements
         * @protected
         */
        _collectElements: function($container) {
            const elements = [];

            const shortcuts = Object.values(ComponentShortcutsManager.getAll());
            const selector = _.map(shortcuts, function(shortcut) {
                return '[' + shortcut.dataAttr + ']';
            });


            ($container || this.$el)
                .find(selector.join(','))
                .addBack(selector.join(','))
                .each((i, elem) => {
                    const $elem = $(elem);
                    if (!this._isReadyToInit($elem) || this._isInOwnLayout($elem)) {
                        return;
                    }

                    const elemData = $elem.data();
                    const shortcut = _.find(shortcuts, function(shortcut) {
                        return shortcut.dataKey in elemData;
                    });

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
         * @protected
         */
        _isInOwnLayout: function($element) {
            // find nearest marked container with separate layout
            const $separateLayout = $element.parents('[data-layout="separate"]:first');
            // collects container elements from current layout
            return $separateLayout.length && _.contains($separateLayout.parents(), this.$el[0]);
        },

        /**
         * Check if the declaration of component on the element is ready to be initialized
         *  - filter out elements with components that have to be initialized on DOM event (click, focusin, mouseover)
         *
         * @param {jQuery} $element
         * @return {boolean}
         * @protected
         */
        _isReadyToInit: function($element) {
            return !$element.is('[data-page-component-init-on], [data-page-component-init-on] *');
        },

        /**
         * Reads initialization data from element, throws error if data is invalid
         *
         * @param {Array.<jQuery>} elements
         * @returns {Array.<{element: jQuery, module: string, options: Object}>}
         * @protected
         */
        _readElementsData: function(elements) {
            return _.compact(_.map(elements, function($elem) {
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
            }, this));
        },

        /**
         * Read component's data attributes from the DOM element
         *
         * @param {jQuery} $elem
         * @throws {Error}
         * @protected
         */
        _readData: function($elem) {
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
         * @protected
         */
        _cleanupData: function($elem) {
            $elem
                .removeData('pageComponentModule')
                .removeData('pageComponentOptions')
                .removeAttr('data-page-component-module')
                .removeAttr('data-page-component-options');
        },

        /**
         * Initializes component for the element
         *
         * @param {{element: jQuery, module: string, options: Object}} data
         * @returns {Promise}
         * @protected
         */
        _loadAndInitializeComponent: function(data) {
            const initDeferred = $.Deferred();

            loadModules(data.module)
                .then(this._onComponentLoaded.bind(this, initDeferred, data.options))
                .catch(this._onComponentLoadError.bind(this, initDeferred));

            const initPromise = initDeferred
                .promise(Object.create({targetData: data}))
                .always(function() {
                    delete this.initPromises[data.options.name];
                }.bind(this));

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
         * @protected
         */
        _onComponentLoaded: function(initDeferred, options, Component) {
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

            if (_.isEmpty(dependencies)) {
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

                $.when(..._.values(dependencies)).then(function() {
                    options[BaseComponent.RELATED_SIBLING_COMPONENTS_PROPERTY_NAME] = dependencies;
                    this._initializeComponent(initDeferred, options, Component);
                }.bind(this));
            }
        },

        /**
         * Initializes the component with passed options and resolves initialization promise
         *
         * @param {jQuery.Deferred} initDeferred
         * @param {Object} options
         * @param {BaseComponent|Function} Component
         * @protected
         */
        _initializeComponent: function(initDeferred, options, Component) {
            const name = options.name;
            const $elem = options._sourceElement;

            const component = new Component(options);
            if (component instanceof BaseComponent) {
                this.add(name, component, $elem[0]);
            }

            if (component.deferredInit) {
                component.deferredInit
                    .always(initDeferred.resolve.bind(initDeferred))
                    .fail(function(error) {
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
                    }.bind(this));
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
         */
        _getComponentDependencies: function(Component, options) {
            const dependencies = BaseComponent.getRelatedSiblingComponentNames(Component);

            // options can only change componentName of existing dependency, can not make it falsy
            const updateFromOptions = _.result(options, BaseComponent.RELATED_SIBLING_COMPONENTS_PROPERTY_NAME);
            _.each(updateFromOptions, function(componentName, dependencyName) {
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
         */
        _resolveRelatedSiblings: function(componentName, dependencies) {
            const deps = _.mapObject(dependencies, function(siblingComponentName, dependencyName) {
                if (this.initPromises[siblingComponentName]) {
                    if (!this._hasCircularDependency(componentName, siblingComponentName)) {
                        this.initPromises[componentName].dependsOn.push(siblingComponentName);
                    } else {
                        throw new Error('The "' + componentName +
                            '" component has circular dependency of sibling components');
                    }
                    return this.initPromises[siblingComponentName].promise
                        .then(function(component) {
                            return (deps[dependencyName] = component);
                        });
                } else if (this.get(siblingComponentName)) {
                    return this.get(siblingComponentName);
                } else {
                    return void 0;
                }
            }, this);

            return deps;
        },

        /**
         * Check if there's circular dependency between two components
         *
         * @param {string} componentName name of depender component
         * @param {string} siblingComponentName name of dependee component
         * @return {boolean}
         */
        _hasCircularDependency: function(componentName, siblingComponentName) {
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
         * @protected
         */
        _onComponentLoadError: function(initDeferred, error) {
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
         * @protected
         */
        _handleError: function(message, error) {
            if (console && console.error) {
                console.error(..._.compact([message, error]));
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
        get: function(name) {
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
        add: function(name, component, el) {
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
            component.once('dispose', function() {
                delete this.components[name];
            }.bind(this));
            return component;
        },

        /**
         * Removes the component
         *
         * @param {string} name component name to remove
         */
        remove: function(name) {
            const item = this.components[name];
            delete this.components[name];
            if (item) {
                item.component.dispose();
            }
        },

        /**
         * Destroys all linked components
         */
        removeAll: function() {
            _.each(this.components, function(item, name) {
                this.remove(name);
            }, this);
        },

        /**
         * Disposes component manager
         */
        dispose: function() {
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
        forEachComponent: function(callback, context) {
            _.each(this.components, function(item) {
                callback.apply(context, [item.component]);
            });
        }
    };

    /**
     * @export oroui/js/app/components/component-manager
     */
    return ComponentManager;
});
