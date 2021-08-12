define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const BaseComponent = require('oroui/js/app/components/base/component');
    const loadModules = require('oroui/js/app/services/load-modules');
    const errorHandler = require('oroui/js/error');

    /**
     * Creates a view passed through 'view' option and binds it with _sourceElement
     * Passes all events triggered on component to the created view.
     */
    const ViewComponent = BaseComponent.extend({
        /**
         * @inheritdoc
         */
        constructor: function ViewComponent(options) {
            ViewComponent.__super__.constructor.call(this, options);
        },

        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function(options) {
            const subPromises = _.values(options._subPromises);
            const viewOptions = _.defaults(
                _.omit(options, '_sourceElement', '_subPromises', 'view'),
                {el: options._sourceElement}
            );
            const initializeView = this._initializeView.bind(this, viewOptions);

            // mark element
            options._sourceElement.attr('data-bound-view', options.view);

            this._deferredInit();
            if (subPromises.length && !options.ignoreSubPromises) {
                // ensure that all nested components are already initialized
                $.when(...subPromises).then(function() {
                    loadModules(options.view, initializeView);
                });
            } else {
                loadModules(options.view, initializeView);
            }
        },

        /**
         *
         * @param {Object} options
         * @param {Function} View
         * @protected
         */
        _initializeView: function(options, View) {
            if (this.disposed) {
                this._resolveDeferredInit();
                return;
            }
            this.view = new View(options);

            // pass all component events to view
            this.on('all', function(eventName, ...args) {
                // add 'component:' prefix to event name
                eventName = 'component:' + eventName;
                this.view.trigger(eventName, ...args);
            }, this);

            if (this.view.deferredRender) {
                this.view.deferredRender
                    .done(this._resolveDeferredInit.bind(this))
                    .fail(function(error) {
                        errorHandler.showError(error || new Error('View rendering failed'));
                        // the error is already handled, there's no need to propagate it upper
                        this._rejectDeferredInit();
                    }.bind(this));
            } else {
                this._resolveDeferredInit();
            }
        }
    });

    return ViewComponent;
});
