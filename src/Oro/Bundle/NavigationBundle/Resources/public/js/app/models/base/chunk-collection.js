/*jslint nomen:true*/
/*global define*/
define([
    'oroui/js/mediator',
    'oroui/js/app/models/base/collection'
], function (mediator, BaseCollection) {
    'use strict';

    var Collection;

    Collection = BaseCollection.extend({
        loadChunk: function () {

        }
    });

    return Collection;
});
