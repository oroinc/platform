define(function(require) {
    'use strict';

    require('bowerassets/base64/base64');

    return {
        encode: window.btoa,
        decode: window.atob
    };
});
