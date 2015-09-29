define(function(require) {
    'use strict';

    var $ = require('jquery');
    var PageableCollection = require('orodatagrid/js/pageable-collection');

    describe('orodatagrid/js/pageable-collection', function() {
        describe('check state encoding/decoding', function() {
            beforeEach(function() {
                var state = {
                    columns: {
                        a: {order: 0, renderable: true},
                        b: {order: 1, renderable: true},
                        c: {order: 2, renderable: true},
                        d: {order: 3, renderable: true},
                        e: {order: 4, renderable: true}
                    }
                };
                this.collection = new PageableCollection([], {
                    initialState: state,
                    state: $.extend(true, {}, state)
                });
            });

            it('have to pack state into hash value', function() {
                expect(this.collection.stateHashValue()).toBe('i=1&p=25&c=a1.b1.c1.d1.e1');
                expect(this.collection.stateHashValue(true)).toBe(null);
            });

            it('have to pack changed state into hash value', function() {
                this.collection.updateState({
                    columns: {
                        a: {order: 2, renderable: true},
                        b: {order: 1, renderable: false},
                        c: {order: 3, renderable: true},
                        d: {order: 0, renderable: false},
                        e: {order: 4, renderable: true}
                    }
                });
                expect(this.collection.stateHashValue()).toBe('i=1&p=25&c=d0.b0.a1.c1.e1');
            });

            it('have to extract state from hash value', function() {
                var state = PageableCollection.decodeStateData('i=1&p=25&c=d0.b0.a1.c1.e1');
                expect(state).toEqual({
                    currentPage: '1',
                    pageSize: '25',
                    columns: 'd0.b0.a1.c1.e1'
                });

                this.collection._unpackStateData(state);
                expect(state).toEqual({
                    currentPage: '1',
                    pageSize: '25',
                    columns: {
                        a: {order: 2, renderable: true},
                        b: {order: 1, renderable: false},
                        c: {order: 3, renderable: true},
                        d: {order: 0, renderable: false},
                        e: {order: 4, renderable: true}
                    }
                });
            });
        });
    });
});
