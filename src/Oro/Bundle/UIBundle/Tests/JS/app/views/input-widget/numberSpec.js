define(function(require) {
    'use strict';

    var $ = require('jquery');
    require('jasmine-jquery');
    require('oroui/js/app/modules/input-widgets');

    var $el;
    var el;

    beforeEach(function() {
        window.setFixtures(
            '<input id="input" type="number" data-precision="0"/>'
        );

        $el = $('#input');
        el = $el[0];
        $el.inputWidget('create');
        $el.data('inputWidget').allowZero = false;
    });

    var testValue = function(val, expected, cursorToStart) {
        var enteredKey = '';
        el.value = '';
        if (!cursorToStart) {
            el.value = val.length ? val.slice(0, val.length - 1) : '';
            enteredKey = val[val.length - 1];
        }
        el.selectionStart = el.selectionEnd = el.value.length;

        // simulate all events during user input
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

    describe('oroui/js/app/views/input-widget/number', function() {
        describe('check widget initialization and dispose', function() {
            it('check attributes after initialization', function() {
                expect($el.attr('type')).toEqual('text');
                expect($el.attr('pattern')).toEqual('[0-9]*');
            });

            it('check attributes dispose initialization', function() {
                $el.inputWidget('dispose');
                expect($el.attr('type')).toEqual('number');
                expect($el.attr('pattern')).toEqual(undefined);
            });
        });

        describe('check number field value normalization', function() {
            it('only numbers allowed when precision = 0', function() {
                testValue('0', '');
                testValue('00123', '123');
                testValue('a123bc', '123');
                testValue('12.3', '12');
            });

            it('numbers and separator allowed when precision > 0', function() {
                $el.data('precision', 3).inputWidget('refresh');

                testValue('.', '0.');
                testValue('.12', '0.12');
                testValue('12.', '12.000');
                testValue('12.', '12.', true);
            });
        });

        describe('check allow zero property', function() {
            it('prevent multiple zeros on beginning value', function() {
                $el.data('inputWidget').allowZero = true;

                testValue('0', '0');
                testValue('0000', '0');
                testValue('0111', '0');
                testValue('123', '123');

                $el
                    .data('precision', 3)
                    .inputWidget('refresh');
                $el.data('inputWidget').allowZero = true;

                testValue('.', '0.');
                testValue('.12', '0.12');
                testValue('12.', '12.000');
                testValue('12.', '12.', true);
            });

            it('disable allow zero', function() {
                testValue('0', '');
                testValue('000', '');
                testValue('0123', '123');
            });
        });
    });
});
