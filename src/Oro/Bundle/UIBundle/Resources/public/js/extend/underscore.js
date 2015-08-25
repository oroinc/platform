define(['underscore'], function(_) {
    'use strict';

    var IE = (function() {
        var rv;
        var userAgent = window.navigator.userAgent;
        var msie = userAgent.indexOf('MSIE ');
        var edge = userAgent.indexOf('Edge/');
        if (msie > 0) {
            // IE 10 or older
            return parseInt(userAgent.substring(msie + 5, userAgent.indexOf('.', msie)), 10);
        }

        if (userAgent.indexOf('Trident/') > 0) {
            // IE 11
            rv = userAgent.indexOf('rv:');
            return parseInt(userAgent.substring(rv + 3, userAgent.indexOf('.', rv)), 10);
        }

        if (edge > 0) {
            // IE 12
            return parseInt(userAgent.substring(edge + 5, userAgent.indexOf('.', edge)), 10);
        }

        // other browser
        return false;
    })();

    _.mixin({
        nl2br: function(str) {
            var breakTag = '<br />';
            return String(str).replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + breakTag + '$2');
        },

        trunc: function(str, maxLength, useWordBoundary) {
            var toLong = str.length > maxLength;
            str = toLong ? str.substr(0, maxLength - 1) : str;
            var lastSpace = str.lastIndexOf(' ');
            str = useWordBoundary && toLong && lastSpace > 0 ? str.substr(0, lastSpace) : str;
            return toLong ? str + '&hellip;' : str;
        },

        isIE: function() {
            return IE !== false;
        }
    });

    return _;
});
