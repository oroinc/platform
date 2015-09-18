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
                expect(this.collection.stateHashValue()).toBe('i=1&p=25&c=01.11.21.31.41');
                expect(this.collection.stateHashValue(true)).toBe(null);
            });

            it('have to pack changed state into hash value', function() {
                this.collection.updateState({
                    columns: {
                        a: {order: 2, renderable: true},  // a -> 0
                        b: {order: 1, renderable: false}, // b -> 1
                        c: {order: 3, renderable: true},  // c -> 2
                        d: {order: 0, renderable: false}, // d -> 3
                        e: {order: 4, renderable: true}   // e -> 4
                    }
                });
                expect(this.collection.stateHashValue()).toBe('i=1&p=25&c=21.10.31.00.41');
            });

            it('have to extract state from hash value', function() {
                var state = PageableCollection.decodeStateData('i=1&p=25&c=21.10.31.00.41');
                expect(state).toEqual({
                    currentPage: '1',
                    pageSize: '25',
                    columns: '21.10.31.00.41'
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
