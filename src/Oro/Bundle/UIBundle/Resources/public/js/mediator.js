/* global define */
define(['underscore', 'backbone'],
function(_, Backbone) {
    'use strict';

    /**
     * @export oroui/js/mediator
     * @name   oro.mediator
     */
    return _.extend({}, Backbone.Events);
});
