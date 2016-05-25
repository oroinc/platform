define(['ororequirejs/lib/asap/asap'], function(asap) {
    'use strict';

    requirejs.nextTick = asap;
    for (var contextName in requirejs.s.contexts) {
        if (requirejs.s.contexts.hasOwnProperty(contextName)) {
            require.s.contexts[contextName].nextTick = asap;
        }
    }

    return asap;
});
