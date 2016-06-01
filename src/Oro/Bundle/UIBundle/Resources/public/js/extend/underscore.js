define(['underscore', 'asap'], function(_, asap) {
    'use strict';

    _.mixin({
        nl2br: function(str) {
            var breakTag = '<br />';
            return String(str).replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + breakTag + '$2');
        },

        trunc: function(str, maxLength, useWordBoundary, hellip) {
            hellip = hellip || '&hellip;';
            var toLong = str.length > maxLength;
            str = toLong ? str.substr(0, maxLength - 1) : str;
            var lastSpace = str.lastIndexOf(' ');
            str = useWordBoundary && toLong && lastSpace > 0 ? str.substr(0, lastSpace) : str;
            return toLong ? str + hellip : str;
        },

        isMobile: function() {
            var elem = document.getElementsByTagName('body')[0];
            return elem && (' ' + elem.className + ' ')
                .replace(/[\t\r\n\f]/g, ' ')
                .indexOf(' mobile-version ') !== -1;
        },

        /* This function is available in newer underscore/lodash versions */
        findIndex: function(collection, predicate) {
            for (var i = 0; i < collection.length; i++) {
                var item = collection[i];
                if (predicate(item)) {
                    return i;
                }
            }
        },

        /* This function is available in newer underscore/lodash versions */
        findLastIndex: function(collection, predicate) {
            for (var i = collection.length - 1; i >= 0; i--) {
                var item = collection[i];
                if (predicate(item)) {
                    return i;
                }
            }
        }
    });

    _.defer = asap;

    return _;
});
