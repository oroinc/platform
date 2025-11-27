import mediator from 'oroui/js/mediator';
import Backbone from 'backbone';
import Chaplin from 'chaplin';


describe('oroui/js/mediator', function() {
    it('compare mediator to Chaplin.mediator', function() {
        expect(mediator).toBe(Chaplin.mediator);
    });

    it('compare mediator to Backbone.Events', function() {
        let prop;
        for (prop in Backbone.Events) {
            if (Backbone.Events.hasOwnProperty(prop)) {
                expect(mediator[prop]).toEqual(Backbone.Events[prop]);
            }
        }
    });
});
