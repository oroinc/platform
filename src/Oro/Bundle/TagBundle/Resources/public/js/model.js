/*global define*/
define(['backbone'], function (Backbone) {
    'use strict';

    /**
     * @export  orotag/js/model
     * @class   orotag.Model
     * @extends Backbone.Model
     */
    return Backbone.Model.extend({
        defaults: {
            owner     : false,
            notSaved  : false,
            moreOwners: false,
            url       : '',
            name      : ''
        }
    });
});
