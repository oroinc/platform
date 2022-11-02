define(function(require) {
    'use strict';

    const autobahn = require('autobahn');
    require('cryptojs/enc-base64');
    require('cryptojs/pbkdf2');
    require('cryptojs/hmac-sha256');

    return autobahn;
});
