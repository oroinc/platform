/*global define*/
define(['backbone'
    ], function (Backbone) {
    'use strict';

    /**
     * @export  oroemail/js/email/template/model
     * @class   oroemail.email.template.Model
     * @extends Backbone.Model
     */
    return Backbone.Model.extend({
        defaults: {
            entity: '',
            id: '',
            name: ''
        }
    });
});
