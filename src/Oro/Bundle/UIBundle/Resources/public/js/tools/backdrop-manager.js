define(function(require) {
    'use strict';
    var $ = require('jquery');
    var MultiUseResourceManager = require('./multi-use-resource-manager');
    var backdropManager = new MultiUseResourceManager({
        listen: {
            'constructResource': function() {
                $(document.body).addClass('backdrop');
            },
            'disposeResource': function() {
                $(document.body).removeClass('backdrop');
            }
        }
    });

    return backdropManager;
});
