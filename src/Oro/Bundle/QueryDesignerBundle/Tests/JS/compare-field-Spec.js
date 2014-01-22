/* global define, require, describe, it, expect, beforeEach, afterEach, spyOn, jasmine */
define(['jquery', 'requirejs-exposure', 'oroquerydesigner/js/compare-field'], function ($, requirejsExposure) {
    'use strict';

    describe('oroquerydesigner/js/compare-field', function () {
        var $div = null;

        beforeEach(function() {
            $div = $('div');
            $('body').append($div);
        });

        afterEach(function() {
            $div.remove();
            $div = null;
        });

        it('is jQueryUI widget', function () {
            expect(function () {
                $div.compareField();
            }).not.toThrow();
        });
    });
});
