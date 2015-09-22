define([], function() {
    'use strict';

    return {
        extractPureEmailAddress: function(fullEmailAddress) {
            var atPos = fullEmailAddress.lastIndexOf('@');
            if (atPos === -1) {
                return fullEmailAddress;
            }

            var startPos = fullEmailAddress.lastIndexOf('<', atPos);
            if (startPos === -1) {
                return fullEmailAddress;
            }

            var endPos = fullEmailAddress.indexOf('>', atPos);
            if (endPos === -1) {
                return fullEmailAddress;
            }

            return fullEmailAddress.substring(startPos + 1, endPos);
        }
    };
});
