/*global define, describe, it, expect, spyOn, beforeEach, afterEach*/
define(function (require) {
    'use strict';

    var $ = require('oroui/js/items-manager/table');
    var Backbone = require('backbone');

    describe('oroui/js/items-manager/table', function () {
        var $el;

        beforeEach(function () {
            $el = $('<div>');
            $('body').append($el);
        });

        afterEach(function () {
            $el.remove();
            $el = null;
        });

        it('is $ widget', function () {
            expect(function () {
                $el.itemsManagerTable({
                    itemTemplate: '',
                    collection: new Backbone.Collection()
                });
            }).not.toThrow();
        });

        it('throws exception if itemTemplate not provided', function () {
            expect(function () {
                $el.itemsManagerTable({
                    itemTemplate: undefined,
                    collection: new Backbone.Collection()
                });
            }).toThrow(new Error('itemTemplate option required'));
        });

        it('throws exception if collection not provided', function () {
            expect(function () {
                $el.itemsManagerTable({
                    itemTemplate: '',
                    collection: undefined
                });
            }).toThrow(new Error('collection option required'));
        });

        it('renders each item in collection', function () {
            $el.itemsManagerTable({
                itemTemplate: '<div></div>',
                collection: new Backbone.Collection([
                    { name: 'a' }, { name: 'b' }, { name: 'c' }
                ])
            });

            expect($el.find('div')).toHaveLength(3);
        });

        it('renders attribute of model', function () {
            $el.itemsManagerTable({
                itemTemplate: '<div><%= name %></div>',
                collection: new Backbone.Collection([
                    { name: 'a' }
                ])
            });

            expect($el.find('div')).toContainText('a');
        });

        it('handles adding model to the collection', function () {
            var collection = new Backbone.Collection();

            $el.itemsManagerTable({
                itemTemplate: '<div></div>',
                collection: collection
            });

            collection.add(new Backbone.Model({ name: 'a' }));

            expect($el.find('div')).toHaveLength(1);
        });

        it('handles removing model from the collection', function () {
            var collection = new Backbone.Collection([
                { name: 'a' }, { name: 'b' }
            ]);

            $el.itemsManagerTable({
                itemTemplate: '<div data-cid="<%= cid %>"></div>',
                collection: collection
            });

            collection.remove(collection.at(1));

            expect($el.find('div')).toHaveLength(1);
        });

        it('handles model changing', function () {
            var collection = new Backbone.Collection([
                { name: 'a' }
            ]);

            $el.itemsManagerTable({
                itemTemplate: '<div data-cid="<%= cid %>"><%= name %></div>',
                collection: collection
            });

            collection.at(0).set('name', 'b');

            expect($el.find('div')).toContainText('b');
        });

        it('handles collection reset', function () {
            var collection = new Backbone.Collection([
                { name: 'a' }, { name: 'b' }, { name: 'c' }
            ]);

            $el.itemsManagerTable({
                itemTemplate: '<div><%= name %></div>',
                collection: collection
            });

            collection.reset();

            expect($el).toBeEmpty();
        });

        it('provides render hook', function () {
            var collection = new Backbone.Collection([
                { name: '<script>alert(\'a\')</script>' }
            ]);

            $el.itemsManagerTable({
                itemTemplate: '<div><%- name %></div>',
                itemRender: function (tmpl, data) {
                    data.name = data.name.replace('<script>', '...').replace('</script>', '...');
                    return tmpl(data);
                },
                collection: collection
            });

            expect($el.find('div')).toContainText('...alert(\'a\')...');
        });

        it('translates action click event into model event with params: model, data-attributes', function () {
            var collection = new Backbone.Collection([
                { name: 'a' }
            ]);

            $el.itemsManagerTable({
                itemTemplate: '<div data-cid="<%= cid %>" data-collection-action="foo"></div>',
                collection: collection
            });

            collection.onActionFoo = function () {};
            spyOn(collection, 'onActionFoo');
            collection.on('action:foo', collection.onActionFoo, collection);

            $el.find('div').click();

            expect(collection.onActionFoo).toHaveBeenCalledWith(collection.at(0), $el.find('div').data());
        });

        it('does not translate action click event into model event if corresponding handler provided', function () {
            var collection = new Backbone.Collection([
                { name: 'a' }
            ]);

            $el.itemsManagerTable({
                itemTemplate: '<div data-cid="<%= cid %>" data-collection-action="foo"></div>',
                collection: collection,
                fooHandler: function () {}
            });

            collection.onActionFoo = function () {};
            spyOn(collection, 'onActionFoo');
            collection.on('action:foo', collection.onActionFoo, collection);

            $el.find('div').click();

            expect(collection.onActionFoo).not.toHaveBeenCalled();
        });

        it('handles action click event with params: model, data-attributes, if corresponding handler provided', function () {
            var collection = new Backbone.Collection([
                { name: 'a' }
            ]);

            var options = {
                itemTemplate: '<div data-cid="<%= cid %>" data-collection-action="bar"></div>',
                collection: collection,
                barHandler: function () {}
            };

            spyOn(options, 'barHandler');

            $el.itemsManagerTable(options);

            $el.find('div').click();

            expect(options.barHandler).toHaveBeenCalledWith(collection.at(0), $el.find('div').data());
        });
    });
});
