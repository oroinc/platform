define(function(require) {
    'use strict';

    var $ = require('jquery');
    require('jasmine-jquery');
    require('oroui/js/app/modules/input-widgets');

    var $el;
    var el;

    beforeEach(function() {
        window.setFixtures('<input id="input" type="number" data-precision="0"/>');

        $el = $('#input');
        el = $el[0];
        $el.inputWidget('create');
    });

    var testValue = function(val, expected, cursorToStart) {
        var enteredKey = '';
        el.value = '';
        if (!cursorToStart) {
            el.value = val.length ? val.slice(0, val.length - 1) : '';
            enteredKey = val[val.length - 1];
        }
        el.selectionStart = el.selectionEnd = el.value.length;

        //simulate all events during user input
        var e;
        e = $.Event('keydown', {key: enteredKey});
        $el.trigger(e);
        if (!e.isDefaultPrevented()) {
            e = $.Event('keypress', {key: enteredKey});
            $el.trigger(e);
        }
        if (!e.isDefaultPrevented()) {
            el.value = val;
        }
        if (!e.isDefaultPrevented()) {
            e = $.Event('input');
            $el.trigger(e);
        }
        if (!e.isDefaultPrevented()) {
            e = $.Event('keyup', {key: enteredKey});
            $el.trigger(e);
        }
        if (!e.isDefaultPrevented()) {
            e = $.Event('change');
            $el.trigger(e);
        }

        expect(el.value).toEqual(expected);
    };

    describe('oroproduct/js/app/product-helper', function() {
        describe('check number field value normalization', function() {
            it('only numbers allowed when precision = 0', function() {
                testValue('0', '');
                testValue('00123', '123');
                testValue('a123bc', '123');
                testValue('12.3', '12');
            });

            it('numbers and separator allowed when precision > 0', function() {
                $el.data('precision', 3);

                testValue('.', '0.');
                testValue('.12', '0.12');
                testValue('12.', '12.000');
                testValue('12.', '12.', true);
            });
        });
    });
});
