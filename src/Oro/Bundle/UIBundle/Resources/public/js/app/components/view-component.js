/*jslint nomen: true*/
/*global define*/
define(['underscore', 'oroui/js/app/components/base/component', 'oroui/js/tools'
    ], function (_, BaseComponent, tools) {
    'use strict';

    /**
     * Creates a view passed through 'view' option and binds it with _sourceElement
     */
    var ViewComponent = BaseComponent.extend({
        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function (options) {
            this._deferredInit();
            tools.loadModules(options.view, function initializeView(viewConstructor) {
                var viewOptions = _.extend(
                        _.omit(options, ['_sourceElement', 'view']),
                        { el: options._sourceElement }
                    );
                this.view = new viewConstructor(viewOptions);
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
