/* jshint browser:true */
/* global define, require */
define(['jquery', 'underscore', 'oro/tools', 'oro/mediator'],
function($, _, tools,  mediator) {
    'use strict';
    var initialized = false,
        initHandler = function (collection, $el, resp) {
            collection.state.totals = resp.options.totals;;
            collection.on('beforeReset', function (collection, resp){
                collection.state.totals = resp.options.totals;
            });
            initialized = true;
        };

    return {
        init: function () {
            initialized = false;
            mediator.once('datagrid_collection_set_after', initHandler);
            mediator.once('hash_navigation_request:start', function() {
                if (!initialized) {
                    mediator.off('datagrid_collection_set_after', initHandler);
                }
            });
        }
    };
});
