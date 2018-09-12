define(function(require) {
    'use strict';

    var Popper = require('components/bootstrap/assets/js/vendor/popper.min');
    window.Popper = Popper; // bootstrap requires Popper in global scope

    return Popper;
});
