/*jshint browser:true, devel:true*/
/*global define*/
define(['jquery', 'underscore'], function ($, _) {
    'use strict';

    return function () {
        $(function () {
            var value, valueEls,
                checkboxEls = $('.parent-scope-checkbox input');

            checkboxEls.on('change', function () {
                value = $(this).is(':checked');
                valueEls = $(this).parents('.controls').find(':input').not(checkboxEls);
                valueEls.each(function (i, el) {
                    $(el)
                        .prop('disabled', value)
                        .data('disabled', value);

                    if (!_.isUndefined($.uniform) && _.contains($.uniform.elements, el)) {
                        $(el).uniform('update');
                    }
                });
            });
        });
    };
});
