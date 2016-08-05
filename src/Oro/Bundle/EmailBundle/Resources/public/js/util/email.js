define(function(require) {
    'use strict';

    var _ = require('underscore');

    return {
        extractPureEmailAddress: function(fullEmailAddress) {
            var atPos;
            var words = fullEmailAddress.split(' ').reverse();
            var rawEmail = _.find(words, function(word) {
                atPos = word.lastIndexOf('@');
                return atPos !== -1;
            });

            if (!rawEmail) {
                return fullEmailAddress;
            }

            var startPos = rawEmail.lastIndexOf('<', atPos);
            if (startPos === -1) {
                return rawEmail;
            }

            var endPos = rawEmail.indexOf('>', atPos);
            if (endPos === -1) {
                return rawEmail;
            }

            return rawEmail.substring(startPos + 1, endPos);
        }
    };
});
