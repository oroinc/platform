define(function(require) {
    'use strict';

    var widgetManager = require('oroui/js/widget-manager');

    return {
        /**
         * Init() function is required
         */
        init: function(deferred, options) {
            deferred.resolve();
        },

        processDatagridOptions: function(deferred, options) {
            var params = options.gridBuildersOptions.rowSelectForWidget || {};

            if (params.multiSelect) {
                options.metadata.options.multiSelectRowEnabled = true;
            } else {
                var wid = params.wid;

                if (!wid) {
                    throw Error('"wid" has to be defined');
                }

                options.metadata.options.rowClickAction = function(data) {
                    return {
                        run: function() {
                            widgetManager.getWidgetInstance(wid, function(widget) {
                                widget.trigger('grid-row-select', data);
                            });
                        }
                    };
                };
            }

            deferred.resolve();

            return deferred;
        }
    };
});
