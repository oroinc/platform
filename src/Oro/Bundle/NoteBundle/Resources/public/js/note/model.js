/*global define*/
define(['backbone'], function (Backbone) {
    'use strict';

    /**
     * @export  oronote/js/note/model
     * @class   oronote.note.Model
     * @extends Backbone.Model
     */
    return Backbone.Model.extend({
        defaults: {
            message: '',
            createdAt: '',
            updatedAt: ''
        }
    });
});
