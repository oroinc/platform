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
        },

        trim: function(text) {
            return text.replace(/^\s*/, '').replace(/\s*$/, '');
        }
    });

    _.templateSettings.innerTempStart = '<%#';
    _.templateSettings.innerTempEnd = '#%>';

    _.template = _.wrap(_.template, function(original, text, settings, oldSettings) {
        if (!settings && oldSettings) {
            settings = oldSettings;
        }
        settings = _.defaults({}, settings, _.templateSettings);

        var regexStart = new RegExp('^' + settings.innerTempStart);
        var regexEnd = new RegExp(settings.innerTempEnd + '$');
        var evaluateStart = '(' + _.templateSettings.innerTempStart + ')';
        var evaluateEnd = '|(' + _.templateSettings.innerTempEnd + ')';

        var innerTempEvaluate = new RegExp(evaluateStart + evaluateEnd, 'g');

        text = _.trim(text).replace(regexStart, '').replace(regexEnd, '');

        var escapedText = text;

        var levelOffsets = {};
        var level = 0;
        var offsetDelta = 0;

        var escapeText = function(text) {
            return text.replace(/\&lt\;\%/g, '&amp;lt;%').replace(/<%/g, '&lt;%').replace(/%>/g, '%&gt;');
        };

        text.replace(innerTempEvaluate, function(match, open, close, offset) {
            offset += offsetDelta;
            if (open) {
                level++;
                levelOffsets[level] = offset;
            }
            if (close && level) {
                var start = escapedText.slice(0, levelOffsets[level]);
                var end = escapedText.slice(offset + close.length);
                var escape = escapedText.slice(levelOffsets[level] + settings.innerTempStart.length, offset);
                var newEscape = escapeText(escape);

                offsetDelta += newEscape.length - escape.length - (settings.innerTempEnd.length * 2);
                escapedText = start + newEscape + end;
                level--;
            }

            // Adobe VMs need the match returned to produce the correct offset.
            return match;
        });
        arguments[1] = _.trim(escapedText);

        return original.apply(this, _.rest(arguments));
    });

    _.defer = asap;

    return _;
});
