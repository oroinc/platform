/*global define, describe, it, expect, spyOn, beforeEach, afterEach*/
/*jshint multistr: true*/
define(function (require) {
    'use strict';

    var $ = require('oroui/js/items-manager/editor');
    var Backbone = require('backbone');

    describe('oroui/js/items-manager/editor', function () {
        var $el;

        beforeEach(function () {
            $el = $('<div>');
            $el.append('\
                <input id="name" name="name"></input>\
                <select id="choice" name="choice">\
                    <option></option>\
                    <option>1</option>\
                    <option>2</option>\
                    <option>3</option>\
                </select>\
                <textarea id="desc" name="desc"></textarea>\
                <input id="a" type="submit"></input>\
                <input id="b" type="submit"></input>\
                <input id="c" type="submit"></input>');
            $('body').append($el);
        });

        afterEach(function () {
            $el.remove();
            $el = null;
        });

        it('is $ widget', function () {
            expect(function () {
                $el.itemsManagerEditor({
                    collection: new Backbone.Collection()
                });
            }).not.toThrow();
        });

        it('resets inputs', function () {
            $el.find('#name').val(1);
            $el.find('#choice').val(2);
            $el.find('#desc').val(3);

            $el.itemsManagerEditor({
                collection: new Backbone.Collection()
            });

            expect($el.find('#name')).toHaveValue('');
            expect($el.find('#choice')).toHaveValue('');
            expect($el.find('#desc')).toHaveValue('');
        });

        it('does not reset special inputs', function () {
            $el.find('#a').val(1);
            $el.find('#b').val(2);
            $el.find('#c').val(3);

            $el.itemsManagerEditor({
                collection: new Backbone.Collection()
            });

            expect($el.find('#a')).toHaveValue('1');
            expect($el.find('#b')).toHaveValue('2');
            expect($el.find('#c')).toHaveValue('3');
        });

        it('sets inputs from edited model', function () {
            var collection = new Backbone.Collection([{
                name: 'Name',
                choice: '2',
                desc: 'Description'
            }]);

            $el.itemsManagerEditor({
                collection: collection
            });

            collection.at(0).trigger('action:edit', collection.at(0));

            expect($el.find('#name')).toHaveValue('Name');
            expect($el.find('#choice')).toHaveValue('2');
            expect($el.find('#desc')).toHaveValue('Description');
        });
    });
});
