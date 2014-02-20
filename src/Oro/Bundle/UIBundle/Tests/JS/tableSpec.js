/*global define, describe, it, expect */
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

        it('is jQueryUI widget', function () {
            expect(function () {
                $el.itemsManagerTable({
                    itemTemplate: '',
                    collection: new Backbone.Collection()
                });
            }).not.toThrow();
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
                    data.name = data.name.replace('<script>', '').replace('</script>', '');
                    return tmpl(data);
                },
                collection: collection
            });

            expect($el.find('div')).toContainText('alert(\'a\')');
        });
    });
});
