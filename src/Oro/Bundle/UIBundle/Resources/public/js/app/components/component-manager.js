define([
    'jquery',
    'underscore',
    'orotranslation/js/translator',
    'oroui/js/tools',
    'oroui/js/app/components/base/component',
    'oroui/js/app/modules/components-shortcuts'
], function($, _, __, tools, BaseComponent, componentsShortcuts) {
    'use strict';

    var console = window.console;

    function ComponentManager($el) {
        this.$el = $el;
        this.components = {};
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
            var items = this._readElementsData(elements, this.initOptions);

            // collect nested elements' data
            _.each(items, function(item) {
                item.subItems = _.filter(items, function(subItem) {
                    return item.options._sourceElement.has(subItem.options._sourceElement).length;
                });
            });

            // initialize components in order reversed to what jQuery returns
            // (nested items have to be initialized first)
            items.reverse();

            var promises = _.map(items, function(item) {
                // collect promises of dependencies
                item.options._subPromises = _.object(_.map(item.subItems, function(subItem) {
                    return [subItem.options.name, subItem.promise];
                }));
                return (item.promise = this._initComponent(item));
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

            this.$el.find('[data-page-component-module]').each(function() {
                var $elem = $(this);

                if (self._isInOwnLayout($elem)) {
                    elements.push($elem);
                }
            });

            _.each(componentsShortcuts.getAll(), function(shortcut, shortcutName) {
                this.$el.find('[data-page-component-shortcut-' + shortcutName + ']').each(function() {
                    var $elem = $(this);

                    if (self._isInOwnLayout($elem)) {
                        var dataAttribute = $.camelCase('page-component-shortcut-' + shortcutName);

                        $elem.data({
                            pageComponentModule: shortcut.moduleName,
                            pageComponentOptions: _.defaults($elem.data(dataAttribute), shortcut.options)
                        });

                        $elem.removeData(dataAttribute);

                        elements.push($elem);
                    }
                });
            }, this);

            return elements;
        },

        _isInOwnLayout: function($element) {
            // find nearest marked container with separate layout
            var $separateLayout = $element.parents('[data-layout="separate"]:first');
            // collects container elements from current layout
            return !$separateLayout.length || !_.contains($separateLayout.parents(), this.$el[0]);
        },

        /**
         * Reads initialization data from element, throws error if data is invalid
         *
         * @param {Array.<jQuery>} elements
         * @param {Object?} options extra options
         * @returns {Array.<{element: jQuery, module: string, options: Object}>}
         * @protected
         */
        _readElementsData: function(elements, options) {
            return _.compact(_.map(elements, function($elem) {
                var item;
                try {
                    item = this._readData($elem, options);
                    item.element = $elem;
                } catch (e) {
                    this._handleError(e.message, e);
                }
                return item;
            }, this));
        },

        /**
         * Read component's data attributes from the DOM element
         *
         * @param {jQuery} $elem
         * @param {Object?} options extra options
         * @throws {Error}
         * @protected
         */
        _readData: function($elem, options) {
            var data = {
                module: $elem.data('pageComponentModule'),
                options: $.extend(true, {}, options, $elem.data('pageComponentOptions'))
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
        _initComponent: function(data) {
            var initDeferred = $.Deferred();

            this._cleanupData(data.element);
            data.element.attr('data-bound-component', data.module);

            require(
                [data.module],
                _.bind(this._onComponentLoaded, this, initDeferred, data.options),
                _.bind(this._onRequireJsError, this, initDeferred)
            );

            return initDeferred.promise(Object.create({targetData: data}));
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

            var $elem = options._sourceElement;
            var name = options.name;

            if (this.components.hasOwnProperty(name)) {
                var message = 'Component with the name "' + name + '" is already registered in the layout';
                this._handleError(message, Error(message));

                // prevent interface from blocking by loader
                initDeferred.resolve();
                return;
            }

            var component = new Component(options);
            if (component instanceof BaseComponent) {
                this.add(name, component, $elem[0]);
            }

            if (component.deferredInit) {
                component.deferredInit
                    .always(_.bind(initDeferred.resolve, initDeferred))
                    .fail(_.bind(function(error) {
                        var moduleName = $elem.attr('data-bound-component');
                        var message = 'Initialization has failed for component "' + moduleName + '"';
                        if (name) {
                            message += ' (name "' + name + '")';
                        }
                        this._handleError(message, error);
                    }, this));
            } else {
                initDeferred.resolve(component);
            }
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
         * @param {string} message
         * @param {Error} error
         * @protected
         */
        _handleError: function(message, error) {
            if (tools.debug) {
                if (console && console.error) {
                    console.error(message);
                } else {
                    throw error;
                }
            } else {
                require('oroui/js/mediator')
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
            var item =  _.find(this.components, function(item) {
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

    return ComponentManager;
});
