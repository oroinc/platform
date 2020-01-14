define(function(require) {
    'use strict';

    // stub for vertx library to trick webpack
    return {
        runOnLoop: function(t) {
            setTimeout(t, 0);
        }
    };
});
