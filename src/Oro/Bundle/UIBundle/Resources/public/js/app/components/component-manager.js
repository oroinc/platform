define([
    'require',
    'jquery',
    'underscore',
    'orotranslation/js/translator',
    'oroui/js/tools',
    'oroui/js/app/components/base/component',
    'oroui/js/component-shortcuts-manager',
    'chaplin' // it is a circular dependency, so there's no local variable assigned
], function(require, $, _, __, tools, BaseComponent, ComponentShortcutsManager) {
    'use strict';

    var console = window.console;

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
         * @return {Promise}
         */
        init: function(options) {
            this.initOptions = options || {};

            var elements = this._collectElements();
            var elementsData = this._readElementsData(elements);

            // collect nested elements' data
            _.each(elementsData, function(data) {
                data.subElementsData = _.filter(elementsData, function(subElementData) {
                    return data.options._sourceElement.has(subElementData.options._sourceElement).length;
                });
            });

            // initialize components in order reversed to what jQuery returns
            // (nested items have to be initialized first)
            elementsData.reverse();

            var promises = _.map(elementsData, function(data) {
                // collect promises of dependencies
                data.options._subPromises = _.object(_.map(data.subElementsData, function(subElementData) {
                    return [subElementData.options.name, subElementData.promise];
                }));
                return (data.promise = this._loadAndInitializeComponent(data));
            }, this);

            return $.when.apply($, _.compact(promises)).then(function() {
                return _.compact(arguments);
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
            var self = this;

            // if the container catches content changed event -- updates its layout
            this.$el.on('content:changed' + this.eventNamespace, _.bind(function(e) {
                if (e.isDefaultPrevented()) {
                    return;
                }
                e.preventDefault();
                this.init(this.initOptions).done(function() {
                    $(e.target).trigger('content:initialized');
                });
            }, this));

            // if the container catches content remove event -- disposes related components
            this.$el.on('content:remove' + this.eventNamespace, function(e) {
                if (e.isDefaultPrevented()) {
                    return;
                }
                e.preventDefault();
                $(e.target).find('[data-bound-component]').each(function() {
                    var component = self.findComponent(this);
                    if (component) {
                        component.dispose();
                    }
                });
            });
        },

        /**
         * Collect all elements that have components declaration from current layout
         *
         * @returns {Array.<jQuery>} elements
         * @protected
         */
        _collectElements: function() {
            var elements = [];
            var self = this;

            var shortcuts = ComponentShortcutsManager.getAll();
            var selector = _.map(shortcuts, function(shortcut) {
                return '[' + shortcut.dataAttr + ']';
            });

            this.$el.find(selector.join(',')).each(function() {
                var $elem = $(this);
                if (self._isInOwnLayout($elem)) {
                    return;
                }

                var elemData = $elem.data();
                _.each(shortcuts, function(shortcut) {
                    var dataKey = shortcut.dataKey;
                    var dataAttr = shortcut.dataAttr;
                    if (elemData[dataKey] === undefined) {
                        return;
                    }

                    var data = ComponentShortcutsManager.getComponentData(shortcut, elemData[dataKey]);
                    data.pageComponentOptions = $.extend(
                        true,
                        data.pageComponentOptions,
                        $elem.data('pageComponentOptions')
                    );
                    $elem.removeAttr(dataAttr)
                        .removeData(dataKey)
                        .data(data);

                    elements.push($elem);
                });
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
            var $separateLayout = $element.parents('[data-layout="separate"]:first');
            // collects container elements from current layout
            return $separateLayout.length && _.contains($separateLayout.parents(), this.$el[0]);
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
                var data;
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
            var data = {
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
            var initDeferred = $.Deferred();

            tools.loadModules(data.module)
                .then(this._onComponentLoaded.bind(this, initDeferred, data.options))
                .catch(this._onRequireJsError.bind(this, initDeferred));

            var initPromise = initDeferred
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

            var message;
            var name = options.name;

            if (this.components.hasOwnProperty(name)) {
                message = 'Component with the name "' + name + '" is already registered in the layout';
                this._handleError(message, new Error(message));

                // prevent interface from blocking by loader
                initDeferred.resolve();
                return;
            }

            var dependencies = this._getComponentDependencies(Component, options);

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

                $.when.apply($, _.values(dependencies)).then(function() {
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
            var name = options.name;
            var $elem = options._sourceElement;

            var component = new Component(options);
            if (component instanceof BaseComponent) {
                this.add(name, component, $elem[0]);
            }

            if (component.deferredInit) {
                component.deferredInit
                    .always(initDeferred.resolve.bind(initDeferred))
                    .fail(function(error) {
                        var moduleName = $elem.attr('data-bound-component');
                        var message = 'Initialization has failed for component "' + moduleName + '"';
                        if (name) {
                            message += ' (name "' + name + '")';
                        }
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
            var dependencies = BaseComponent.getRelatedSiblingComponentNames(Component);

            // options can only change componentName of existing dependency, can not make it falsy
            var updateFromOptions = _.result(options, BaseComponent.RELATED_SIBLING_COMPONENTS_PROPERTY_NAME);
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
            var deps = _.mapObject(dependencies, function(siblingComponentName, dependencyName) {
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
            var name;
            var checked = [];
            var queue = [siblingComponentName].concat(this.initPromises[componentName].dependsOn);

            do {
                name = queue.pop();
                if (name === componentName) {
                    return true;
                }
                checked.push(name);
                if (this.initPromises[name]) {
                    queue.push.apply(queue, _.difference(this.initPromises[name].dependsOn, checked));
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
        _onRequireJsError: function(initDeferred, error) {
            var message = 'Cannot load module "' + error.requireModules[0] + '"';
            this._handleError(message, error);
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
            if (tools.debug) {
                if (console && console.error) {
                    console.error.apply(console, _.compact([message, error]));
                } else {
                    throw error;
                }
            } else if (error) {
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
            component.once('dispose', _.bind(function() {
                delete this.components[name];
            }, this));
            return component;
        },

        /**
         * Removes the component
         *
         * @param {string} name component name to remove
         */
        remove: function(name) {
            var item = this.components[name];
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
         * Find component related to the element
         *
         * @param {HTMLElement} el
         * @returns {BaseComponent}
         */
        findComponent: function(el) {
            var item = _.find(this.components, function(item) {
                return item.el === el;
            });
            if (item) {
                return item.component;
            }
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
