/*global define*/
define(['underscore'], function (_) {
    'use strict';

    _.mixin({
        nl2br : function(str){
            var breakTag = '<br />';
            return String(str).replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + breakTag + '$2');
        }
    });

    return _;
});
