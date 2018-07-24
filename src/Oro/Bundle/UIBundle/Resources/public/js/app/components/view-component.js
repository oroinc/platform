define(function(require) {
    'use strict';

    var ViewComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var tools = require('oroui/js/tools');
    var errorHandler = require('oroui/js/error');

    /**
     * Creates a view passed through 'view' option and binds it with _sourceElement
     * Passes all events triggered on component to the created view.
     */
    ViewComponent = BaseComponent.extend({
        /**
         * @inheritDoc
         */
        constructor: function ViewComponent() {
            ViewComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function(options) {
            var subPromises = _.values(options._subPromises);
            var viewOptions = _.defaults(
                _.omit(options, '_sourceElement', '_subPromises', 'view'),
                {el: options._sourceElement}
            );
            var initializeView = _.bind(this._initializeView, this, viewOptions);

            // mark element
            options._sourceElement.attr('data-bound-view', options.view);

            this._deferredInit();
            if (subPromises.length) {
                // ensure that all nested components are already initialized
                $.when.apply($, subPromises).then(function() {
                    tools.loadModules(options.view, initializeView);
                });
            } else {
                tools.loadModules(options.view, initializeView);
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
            this.on('all', function() {
                // add 'component:' prefix to event name
                arguments[0] = 'component:' + arguments[0];
                this.view.trigger.apply(this.view, arguments);
            }, this);

            if (this.view.deferredRender) {
                this.view.deferredRender
                    .done(_.bind(this._resolveDeferredInit, this))
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
