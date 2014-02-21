/*global define*/
define(['../model'
    ], function (NavigationModel) {
    'use strict';

    /**
     * @export  oronavigation/js/pinbar/model
     * @class   oronavigation.pinbar.Model
     * @extends oronavigation.Model
     */
    return NavigationModel.extend({
        defaults: {
            title: '',
            url: null,
            position: null,
            type: 'pinbar',
            display_type: null,
            maximized: false,
            remove: false
        }
    });
});
