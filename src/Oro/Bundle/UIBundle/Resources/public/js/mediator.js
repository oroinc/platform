/*jslint nomen:true*/
/*global define*/
define([
    'underscore',
    'backbone',
    'chaplin'
], function (_, Backbone, Chaplin) {
    'use strict';

    _.extend(Chaplin.mediator, Backbone.Events);

    /**
     * @export oroui/js/mediator
     * @name   oro.mediator
     */
    return Chaplin.mediator;
});
