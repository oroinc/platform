/*global define*/
define(['backbone'], function (Backbone) {
    'use strict';

    /**
     * @export  oroaddress/js/region/model
     * @class   oroaddress.region.Model
     * @extends Backbone.Model
     */
    return Backbone.Model.extend({
        defaults: {
            country: '',
            code: '',
            name: ''
        }
    });
});
