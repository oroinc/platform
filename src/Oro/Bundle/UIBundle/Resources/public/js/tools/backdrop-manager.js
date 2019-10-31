define(function(require) {
    'use strict';

    const $ = require('jquery');
    const MultiUseResourceManager = require('./multi-use-resource-manager');
    const backdropManager = new MultiUseResourceManager({
        listen: {
            constructResource: function() {
                $(document.body).addClass('backdrop');
            },
            disposeResource: function() {
                $(document.body).removeClass('backdrop');
            }
        }
    });

    return backdropManager;
});
