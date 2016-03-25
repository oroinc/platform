define(function() {
    'use strict';

    return {
        /**
         * Add parameter to URL
         *
         * @param {string} url
         * @param {string} parameterName
         * @param {string} parameterValue
         * @return {string}
         * @protected
         */
        addUrlParameter: function(url, parameterName, parameterValue) {
            var urlHash;
            var cl;
            var urlParts;
            var newQueryString;
            var parameters;
            var parameterParts;
            var i;
            var replaceDuplicates = true;
            if (url.indexOf('#') > 0) {
                cl = url.indexOf('#');
                urlHash = url.substring(url.indexOf('#'), url.length);
            } else {
                urlHash = '';
                cl = url.length;
            }
            var sourceUrl = url.substring(0, cl);

            urlParts = sourceUrl.split('?');
            newQueryString = '';

            if (urlParts.length > 1) {
                parameters = urlParts[1].split('&');
                for (i = 0; i < parameters.length; i += 1) {
                    parameterParts = parameters[i].split('=');
                    if (!(replaceDuplicates && parameterParts[0] === parameterName)) {
                        if (newQueryString === '') {
                            newQueryString = '?';
                        } else {
                            newQueryString += '&';
                        }
                        newQueryString += parameterParts[0] + '=' + (parameterParts[1] || '');
                    }
                }
            }
            if (newQueryString === '') {
                newQueryString = '?';
            }
            if (newQueryString !== '' && newQueryString !== '?') {
                newQueryString += '&';
            }
            newQueryString += parameterName + '=' + encodeURIComponent(parameterValue || '');
            return urlParts[0] + newQueryString + urlHash;
        }
    };
});
