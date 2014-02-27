/* global define, describe, it, expect */
define(['oroui/js/mediator', 'backbone'],
function(mediator, Backbone) {
    'use strict';

    describe('oroui/js/mediator', function () {
        it("compare mediator to Backbone.Events", function() {
            expect(mediator).toEqual(Backbone.Events);
            expect(mediator).not.toBe(Backbone.Events);
        });
    });
});
