define(function(require) {
    'use strict';

    var ViewComponent;
    var _ = require('underscore');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var tools = require('oroui/js/tools');

    /**
     * Creates a view passed through 'view' option and binds it with _sourceElement
     * Passes all events triggered on component to the created view.
     */
    ViewComponent = BaseComponent.extend({
        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function(options) {
            var viewOptions = _.extend(
                _.omit(options, ['_sourceElement', 'view']),
                {el: options._sourceElement}
            );
            this._deferredInit();
            // mark element
            options._sourceElement.attr('data-bound-view', options.view);
            tools.loadModules(options.view, _.partial(_.bind(this._initializeView, this), viewOptions));
        },

        /**
         *
         * @param {Object} options
         * @param {Function} View
         * @protected
         */
        _initializeView: function(options, View) {
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
                    .fail(function() {
                        throw new Error('View rendering failed');
                    });
            } else {
                this._resolveDeferredInit();
            }
        }
    });

    return ViewComponent;
});
