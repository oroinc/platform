define(function(require) {
    'use strict';
    var $ = require('jquery');
    var MultiUseResourceManager = require('./multi-use-resource-manager');
    var backdropManager = new MultiUseResourceManager({
        listen: {
            'construct': function() {
                $(document.body).addClass('backdrop');
            },
            'dispose': function() {
                $(document.body).removeClass('backdrop');
            }
        }
    });

    return backdropManager;
});
