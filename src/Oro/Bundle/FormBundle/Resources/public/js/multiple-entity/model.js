/*global define*/
define(['backbone'],
    function (Backbone) {
        'use strict';

        /**
         * @export  oroform/js/multiple-entity/model
         * @class   oroform.MultipleEntity.Model
         * @extends Backbone.Model
         */
        return Backbone.Model.extend({
            defaults: {
                id: null,
                link: null,
                label: null,
                isDefault: false,
                extraData: []
            }
        });
    });
