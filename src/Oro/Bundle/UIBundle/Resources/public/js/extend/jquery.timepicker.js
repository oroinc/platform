define(function(require) {
    'use strict';

    var $ = require('jquery');
    var mask = require('oroui/js/dropdown-mask');
    require('oroui/js/jquery-timepicker-l10n');
    require('oroui/lib/jquery.timepicker-1.4.13/jquery.timepicker');

    $(document)
        .on('showTimepicker', function(e) {
            var $input = $(e.target);
            var zIndex = $input.data('timepicker-list').css('zIndex');
            mask.show(zIndex - 1)
                .onhide(function() {
                    $input.timepicker('hide');
                });
        })
        .on('hideTimepicker', function(e) {
            mask.hide();
        });
});
