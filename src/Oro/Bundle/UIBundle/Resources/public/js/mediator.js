define([
    'underscore',
    'backbone',
    'chaplin'
], function(_, Backbone, Chaplin) {
    'use strict';

    var mediator = Backbone.mediator = Chaplin.mediator;

    _.extend(mediator, Backbone.Events);
    /**
     * Listen Id should be defined before Chaplin.mediator get sealed
     * on application start
     */
    if (!mediator._listenId) {
        mediator._listenId = _.uniqueId('l');
    }

    /**
     * @export oroui/js/mediator
     * @name   oro.mediator
     */
    return mediator;
});
