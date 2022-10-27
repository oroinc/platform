define(function(require) {
    'use strict';

    const _ = require('underscore');

    return {
        extractPureEmailAddress: function(fullEmailAddress) {
            let atPos;
            const words = fullEmailAddress.split(' ').reverse();
            const rawEmail = _.find(words, function(word) {
                atPos = word.lastIndexOf('@');
                return atPos !== -1;
            });

            if (!rawEmail) {
                return fullEmailAddress;
            }

            const startPos = rawEmail.lastIndexOf('<', atPos);
            if (startPos === -1) {
                return rawEmail;
            }

            const endPos = rawEmail.indexOf('>', atPos);
            if (endPos === -1) {
                return rawEmail;
            }

            return rawEmail.substring(startPos + 1, endPos);
        }
    };
});
