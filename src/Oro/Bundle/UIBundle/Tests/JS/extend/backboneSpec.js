define(function(require) {
    'use strict';

    const Backbone = require('oroui/js/extend/backbone');

    describe('oroui/js/extend/backbone', function() {
        describe('Backbone.Events', function() {
            let obj1;
            let obj2;
            let obj3;
            let handler;

            beforeEach(function() {
                obj1 = Object.create(Backbone.Events);
                obj2 = Object.create(Backbone.Events);
                obj3 = Object.create(Backbone.Events);
                handler = jasmine.createSpy('spy');
            });

            it('check firstListenTo method', function() {
                obj2.listenTo(obj1, 'test', handler);
                obj3.firstListenTo(obj1, 'test', handler);

                obj1.trigger('test');

                expect(handler.calls.count()).toBe(2);
                expect(handler.calls.first().object).toBe(obj3);
            });

            it('check firstOn method', function() {
                obj1.on('test', handler, obj2);
                obj1.firstOn('test', handler, obj3);

                obj1.trigger('test');

                expect(handler.calls.count()).toBe(2);
                expect(handler.calls.first().object).toBe(obj3);
            });
        });
    });
});
