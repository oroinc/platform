define(function(require) {
    'use strict';

    var $ = require('oroui/js/items-manager/editor');
    var Backbone = require('backbone');

    describe('oroui/js/items-manager/editor', function() {
        var $el;

        beforeEach(function() {
            $el = $('<div>');
            $el.append(
                '<input id="name" name="name"/>' +
                '<select id="choice" name="choice">' +
                    '<option></option>' +
                    '<option>1</option>' +
                    '<option>2</option>' +
                    '<option>3</option>' +
                '</select>' +
                '<textarea id="desc" name="desc"></textarea>' +
                '<input id="a" type="submit"/>' +
                '<input id="b" type="submit"/>' +
                '<input id="c" type="submit"/>' +
                '<button class="add-button"></button>' +
                '<button class="save-button"></button>' +
                '<button class="cancel-button"></button>');
            $('body').append($el);
        });

        afterEach(function() {
            $el.remove();
            $el = null;
        });

        it('is $ widget', function() {
            expect(function() {
                $el.itemsManagerEditor({
                    collection: new Backbone.Collection()
                });
            }).not.toThrow();
        });

        it('resets inputs', function() {
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

        it('does not reset special inputs', function() {
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

        it('sets inputs from edited model', function() {
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

        it('updates edited model on button click', function() {
            var collection = new Backbone.Collection([{
                name: 'Name',
                choice: '2',
                desc: 'Description'
            }]);

            $el.itemsManagerEditor({
                collection: collection
            });

            collection.at(0).trigger('action:edit', collection.at(0));

            $el.find('#name').val('Name2');
            $el.find('#choice').val(3);
            $el.find('#desc').val('Description2');

            $el.find('.save-button').click();

            expect(collection.at(0).get('name')).toEqual('Name2');
            expect(collection.at(0).get('choice')).toEqual('3');
            expect(collection.at(0).get('desc')).toEqual('Description2');
        });

        it('does not update edited model on button click', function() {
            var collection = new Backbone.Collection([{
                name: 'Name',
                choice: '2',
                desc: 'Description'
            }]);

            $el.itemsManagerEditor({
                collection: collection
            });

            collection.at(0).trigger('action:edit', collection.at(0));

            $el.find('#name').val('Name2');
            $el.find('#choice').val(3);
            $el.find('#desc').val('Description2');

            $el.find('.cancel-button').click();

            expect(collection.at(0).get('name')).toEqual('Name');
            expect(collection.at(0).get('choice')).toEqual('2');
            expect(collection.at(0).get('desc')).toEqual('Description');
        });

        it('creates model on button click', function() {
            var collection = new Backbone.Collection();

            $el.itemsManagerEditor({
                collection: collection
            });

            $el.find('#name').val('Name');
            $el.find('#choice').val(1);
            $el.find('#desc').val('Description');

            $el.find('.add-button').click();

            expect(collection.at(0).get('name')).toEqual('Name');
            expect(collection.at(0).get('choice')).toEqual('1');
            expect(collection.at(0).get('desc')).toEqual('Description');
        });

        it('does not create model on button click', function() {
            var collection = new Backbone.Collection();

            $el.itemsManagerEditor({
                collection: collection
            });

            $el.find('#name').val('Name');
            $el.find('#choice').val(1);
            $el.find('#desc').val('Description');

            $el.find('.cancel-button').click();

            expect(collection.length).toEqual(0);
        });

        it('resets inputs if model removed', function() {
            var collection = new Backbone.Collection([{
                name: 'Name',
                choice: '2',
                desc: 'Description'
            }]);

            $el.itemsManagerEditor({
                collection: collection
            });

            collection.at(0).trigger('action:edit', collection.at(0));
            collection.remove(collection.at(0));

            expect($el.find('#name')).toHaveValue('');
            expect($el.find('#choice')).toHaveValue('');
            expect($el.find('#desc')).toHaveValue('');
        });

        it('hides edit button if new model', function() {
            var collection = new Backbone.Collection([{
                name: 'Name',
                choice: '2',
                desc: 'Description'
            }]);

            $el.itemsManagerEditor({
                collection: collection
            });

            expect($el.find('.save-button')).toBeHidden();
        });

        it('hides add button if editing model', function() {
            var collection = new Backbone.Collection([{
                name: 'Name',
                choice: '2',
                desc: 'Description'
            }]);

            $el.itemsManagerEditor({
                collection: collection
            });

            collection.at(0).trigger('action:edit', collection.at(0));

            expect($el.find('.add-button')).toBeHidden();
        });

        it('calls setter for each input', function() {
            var collection = new Backbone.Collection([{
                name: 'Name',
                choice: '2',
                desc: 'Description'
            }]);

            $el.itemsManagerEditor({
                collection: collection,
                setter: function($el, name, value) {
                    if (name === 'name') {
                        return value + '2';
                    }
                    if (name === 'choice') {
                        return 3;
                    }
                    if (name === 'desc') {
                        return value + '2';
                    }
                    return value;
                }
            });

            collection.at(0).trigger('action:edit', collection.at(0));

            expect($el.find('#name')).toHaveValue('Name2');
            expect($el.find('#choice')).toHaveValue('3');
            expect($el.find('#desc')).toHaveValue('Description2');
        });

        it('calls getter for each input', function() {
            var collection = new Backbone.Collection([{
                name: 'Name',
                choice: '2',
                desc: 'Description'
            }]);

            $el.itemsManagerEditor({
                collection: collection,
                getter: function($el, name, value) {
                    if (name === 'name') {
                        return value + '2';
                    }
                    if (name === 'choice') {
                        return '1';
                    }
                    if (name === 'desc') {
                        return value + '2';
                    }
                    return value;
                }
            });

            collection.at(0).trigger('action:edit', collection.at(0));

            $el.find('#name').val('Name2');
            $el.find('#choice').val(3);
            $el.find('#desc').val('Description2');

            $el.find('.save-button').click();

            expect(collection.at(0).get('name')).toEqual('Name22');
            expect(collection.at(0).get('choice')).toEqual('1');
            expect(collection.at(0).get('desc')).toEqual('Description22');
        });
    });
});
