/*global define*/
define(['backbone'],
function (Backbone) {
    'use strict';

    /**
     * @export  oronote/js/note/model
     * @class   oronote.note.Model
     * @extends Backbone.Model
     */
    return Backbone.Model.extend({
        defaults: {
            id: '',
            message: '',
            createdAt: '',
            updatedAt: '',
            editable: false,
            removable: false,
            createdBy: null,
            createdBy_id: null,
            createdBy_viewable: false,
            createdBy_avatar: null,
            updatedBy: null,
            updatedBy_id: null,
            updatedBy_viewable: false,
            updatedBy_avatar: null
        }
    });
});
