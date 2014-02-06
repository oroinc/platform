/*global define, require, describe, it, expect, beforeEach, afterEach, spyOn, jasmine*/
/*browser:true*/
define(function (require) {
    'use strict';

    require('jasmine-jquery');
    var $ = require('oroquerydesigner/js/condition-builder'),
        html = require('text!./Fixture/condition-builder.html');

    describe('oroquerydesigner/js/condition-builder', function () {
        var $el = null;

        beforeEach(function () {
            window.setFixtures(html);
            $el = $('#condition-builder');
        });

        afterEach(function () {
            $el = null;
        });

        it('is jQueryUI widget', function () {
            expect(function () {
                $el.conditionBuilder();
            }).not.toThrow();
            expect($el).toExist();
        });
    });
});
