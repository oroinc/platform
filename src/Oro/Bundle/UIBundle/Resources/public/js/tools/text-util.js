define(['underscore', 'orotranslation/js/translator'], function(_, __) {
    'use strict';

    // matches "A. L. Price", "In progress", "one of all"
    var shortWordsAtStartRegExp = /^((\w{1,3}|\w\.)\s+(\w{1,3}|\w\.)\s+|(\w{1,3}|\w\.?)\s+)(\w+|$)/;

    // matches "A. L. Price", "In progress", "one of all"
    var shortWordsAtEndRegExp = /(\w+|^)(\s+(\w{1,3}|\w\.)\s+(\w{1,3}|\w\.)|\s+(\w{1,3}|\w\.?))$/;

    var postpositionsRegExp = new RegExp('\\s+(' + __('postpositions') + ')(\\W|$)', 'gi');
    var prepositionsRegExp = new RegExp('(\\W|^)(' + __('prepositions') + ')((\\s+)(' +
        __('articles') + ')|)\\s+', 'gi');

    var abbreviateIgnoreList = __('abbreviate_ignore_list').split('|');

    /**
     * @export oroui/js/tools/text-util
     */
    return {
        /**
         * Prepares text for output
         *
         * @param {string} text
         * @returns {string}
         */
        prepareText: function(text) {
            if (!_.isString(text)) {
                return text;
            }
            // disallow line breaks at start of string if there are short words at the start
            text = text.replace(shortWordsAtStartRegExp, function() {
                // can be one of two following cases
                // ["A. L. Price",   "A. L. ", "A.",      "L.",            undefined, "Price",     0, "A. L. Price"]
                // ["Roy Greenwell", "Roy ",   undefined, undefined,       "Roy",     "Greenwell", 0, "Roy Greenwell"]
                if (arguments[4]) {
                    return arguments[4] + /* nbsp */'\u00A0' + arguments[5];
                } else {
                    return arguments[2] + /* nbsp */'\u00A0' + arguments[3] + /* nbsp */'\u00A0' + arguments[5];
                }
            });
            // disallow line breaks at end of string if there are short words at the end
            text = text.replace(shortWordsAtEndRegExp, function() {
                // can be one of two following cases
                // ["Body big",       "Body",   " big",     undefined, undefined, "big",     0, "Body big"]
                // ["Boston Sea tea", "Boston", " Sea tea", "Sea",     "tea",     undefined, 0, "Boston Sea tea"]
                if (arguments[5]) {
                    return arguments[1] + /* nbsp */'\u00A0' + arguments[5];
                } else {
                    return arguments[1] + /* nbsp */'\u00A0' + arguments[3] + /* nbsp */'\u00A0' + arguments[4];
                }
            });
            // process postpositions
            text = text.replace(postpositionsRegExp, '\u00A0$1$2');
            // console.log(text.replace(/\u00A0/g, '^'));

            // process prepositions
            text = text.replace(prepositionsRegExp, function() {
                if (arguments[5]) {
                    // with article
                    return arguments[1] + arguments[2] + '\u00A0' + arguments[5] + '\u00A0';
                } else {
                    // without article
                    return arguments[1] + arguments[2] + '\u00A0';
                }
            });
            // console.log(text.replace(/\u00A0/g, '^'));

            return text;
        },

        /**
         * Abbreviates text if it has more than `minWordsCount`
         *
         * @param {string} text
         * @param {number} minWordsCount
         * @return {string}
         */
        abbreviate: function(text, minWordsCount) {
            if (!_.isString(text)) {
                return text;
            }
            var words = text.split(/\s+/g) // split text by whitespaces
                .map(function(word) {
                    // trims punctuation for each word
                    return word.replace(/^[!-#%-*,-/:;?@\[-\]_{}"']+|[!-#%-*,-/:;?@\[-\]_{}'"]+$/g, '');
                });
            words = _.compact(words);
            if (words.length < minWordsCount) {
                return text;
            }
            return words.map(function(word) {
                if (abbreviateIgnoreList.indexOf(word.toLowerCase()) !== -1) {
                    return '';
                }
                return word[0].toUpperCase();
            }).join('');
        }
    };
});
