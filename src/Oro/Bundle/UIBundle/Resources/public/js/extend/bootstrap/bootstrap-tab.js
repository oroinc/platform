define(function(require) {
    'use strict';

    var _ = require('underscore');
    var $ = require('jquery');
    require('bootstrap');

    var Tab = $.fn.tab.Constructor;

    Tab.prototype.openByHash = function ($element) {
        var hashTab = window.location.hash;
        if (hashTab !== '') {
            $element.filter(function () {
                var $filteredElement = $(this);
                if ($filteredElement.attr('href') === hashTab) {
                    var options = $filteredElement.data('options');
                    _.defaults(options, {useHash: false});

                    if (options.useHash) {
                        return true;
                    }
                }

                return false;
            }).tab('show');
        }
    };

    (function() {
        // Open Tab By Hash when document is ready
        var $tabElement = $('[data-toggle="tab"]');
        Tab.prototype.openByHash($tabElement);
    })();
    
});
