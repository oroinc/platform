/*global define, require, describe, it, expect, beforeEach, afterEach, spyOn, jasmine*/
define(['jquery', 'oroquerydesigner/js/field-condition'], function ($) {
    'use strict';

    describe('oroquerydesigner/js/field-condition', function () {
        var $div = null;

        beforeEach(function () {
            $div = $('div');
            $('body').append($div);
        });

        afterEach(function () {
            $div.remove();
            $div = null;
        });

        it('is jQueryUI widget', function () {
            expect(function () {
                $div.fieldCondition();
            }).not.toThrow();
        });
    });
});
