define([
    'oroui/js/mediator',
    'backbone',
    'chaplin'
], function(mediator, Backbone, Chaplin) {
    'use strict';

    describe('oroui/js/mediator', function() {
        it('compare mediator to Chaplin.mediator', function() {
            expect(mediator).toBe(Chaplin.mediator);
        });

        it('compare mediator to Backbone.Events', function() {
            var prop;
            for (prop in Backbone.Events) {
                if (Backbone.Events.hasOwnProperty(prop)) {
                    expect(mediator[prop]).toEqual(Backbone.Events[prop]);
                }
            }
        });
    });
});
