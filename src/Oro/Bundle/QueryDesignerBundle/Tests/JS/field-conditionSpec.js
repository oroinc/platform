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

        it('is jQueryUI widget', function () {
            expect(function () {
                $el.fieldCondition();
            }).not.toThrow();
        });
    });
});
