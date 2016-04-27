define([
    'jquery',
    'underscore',
    'orotranslation/js/translator',
    'oroui/js/tools',
    'oroui/js/app/components/base/component'
], function($, _, __, tools, BaseComponent) {
    'use strict';

    var console = window.console;

    function ComponentManager($el) {
        this.$el = $el;
        this.components = {};
        this._bindContainerChangesEvents();
    }

    ComponentManager.prototype = {
        eventNamespace: '.component-manager',

        init: function(options) {
            var promises = [];
            var elements = [];
            var modules = [];

            this._analyseDom(elements, modules);

            _.each(elements, _.bind(function(element) {
                promises.push(this._initComponent(element, options));
            }, this));

            // optimize load time - preload components in separate layouts
            require(modules, _.noop);
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
                this.init();
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
         * Collect all elements that have components declaration
         *
         * @param {Array.<jQuery>} elements
         * @param {Array.<string>} modules
         * @protected
         */
        _analyseDom: function(elements, modules) {
            var el = this.$el[0];

            this.$el.find('[data-page-component-module]').each(function() {
                var $elem = $(this);

                // optimize load time - push components to preload queue
                modules.push($elem.data('pageComponentModule'));

                // find nearest marked container with separate layout
                var $separateLayout = $elem.parents('[data-layout="separate"]:first');

                // collects container elements from current layout
                if (!$separateLayout.length || !_.contains($separateLayout.parents(), el)) {
                    elements.push($elem);
                }
            });
        },

        /**
         * Read component's data attributes from the DOM element
         *
         * @param {jQuery} $elem
         * @protected
         */
        _readData: function($elem) {
            var data = {
                module: $elem.data('pageComponentModule'),
                options: $elem.data('pageComponentOptions') || {}
            };
            data.options._sourceElement = $elem;
            var name = $elem.data('pageComponentName') || $elem.attr('data-ftid');
            if (name) {
                data.options.name = name;
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
         * @param {jQuery} $elem
         * @param {Object|null} options
         * @returns {Promise}
         * @protected
         */
        _initComponent: function($elem, options) {
            var data = this._readData($elem);
            this._cleanupData($elem);

            // mark elem
            $elem.attr('data-bound-component', data.module);

            var initDeferred = $.Deferred();
            var componentOptions = $.extend(true, {}, options || {}, data.options);
            require(
                [data.module],
                _.bind(this._onComponentLoaded, this, initDeferred, componentOptions),
                _.bind(this._onRequireJsError, this, initDeferred)
            );

            return initDeferred.promise();
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

            if (name && this.components.hasOwnProperty(name)) {
                var message = 'Component with the name "' + name + '" is already registered in the layout';
                this._handleError(message, Error(message));

                // prevent interface from blocking by loader
                initDeferred.resolve();
                return;
            }

            var component = new Component(options);
            if (component instanceof BaseComponent) {
                this.add(name || component.cid, component, $elem[0]);
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
                }
                throw error;
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
