/*global base64_encode, base64_decode*/
define(['base64'], function() {
    'use strict';

    return {
        // jscs:disable
        encode: base64_encode,
        decode: base64_decode
        // jscs:enable
    };
});
