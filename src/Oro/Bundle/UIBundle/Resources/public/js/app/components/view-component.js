/*jslint nomen: true*/
/*global define*/
define(['underscore', 'oroui/js/app/components/base/component', 'oroui/js/tools'
    ], function (_, BaseComponent, tools) {
    'use strict';

    /**
     * Creates a view passed through 'view' option and binds it with _sourceElement
     * Passes all events triggered on component to the created view.
     */
    var ViewComponent = BaseComponent.extend({
        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function (options) {
            this._deferredInit();
            // mark element
            options._sourceElement.attr('data-bound-view', options.view);
            tools.loadModules(options.view, function initializeView(viewConstructor) {
                var viewOptions = _.extend(
                        _.omit(options, ['_sourceElement', 'view']),
                        { el: options._sourceElement }
                    );

                this.view = new viewConstructor(viewOptions);

                // pass all component events to view
                this.on('all', function () {
                    // add 'component:' prefix to event name
                    arguments[0] = 'component:' + arguments[0];
                    this.view.trigger.apply(this.view, arguments);
                }, this);

                if (this.view.renderDeferred) {
                    this.view.renderDeferred
                        .done(_.bind(this._resolveDeferredInit, this))
                        .fail(function () {
                            throw new Error("View rendering failed");
                        });
                } else {
                    this._resolveDeferredInit();
                }
            }, this);
        }
    });

    return ViewComponent;
});
