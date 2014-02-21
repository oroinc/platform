/*global define, require, describe, it, expect, beforeEach, afterEach, spyOn, jasmine*/
define(['jquery', 'oroquerydesigner/js/field-condition'], function ($) {
    'use strict';

    describe('oroquerydesigner/js/field-condition', function () {
        var $el = null;

        beforeEach(function () {
            $el = $('<div>');
            $('body').append($el);
        });

        afterEach(function () {
            $el.remove();
            $el = null;
        });

        function waitForFilter(cb) {
            var html = $el.find('.active-filter').html();
            function wait() {
                var current = $el.find('.active-filter').html();
                if (current !== html) {
                    cb();
                } else {
                    setTimeout(wait, 5);
                }
            }
            setTimeout(wait, 5);
        }

        it('is $ widget', function () {
            expect(function () {
                $el.fieldCondition();
            }).not.toThrow();
        });
    });
});
