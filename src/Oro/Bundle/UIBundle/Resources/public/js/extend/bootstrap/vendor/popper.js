define(function(require) {
    'use strict';

    require('oroui/js/extend/polyfill');
    var Popper = require('bowerassets/bootstrap/assets/js/vendor/popper.min');
    window.Popper = Popper; // bootstrap requires Popper in global scope

    return Popper;
});
