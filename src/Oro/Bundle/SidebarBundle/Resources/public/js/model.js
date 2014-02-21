/*global define*/
define(['backbone', 'routing', './constants'
    ], function (Backbone, routing, constants) {
    'use strict';

    /**
     * @export  orosidebar/js/model
     * @class   orosidebar.Model
     * @extends Backbone.Model
     */
    return Backbone.Model.extend({
        defaults: {
            position: constants.SIDEBAR_LEFT,
            state: constants.SIDEBAR_MINIMIZED
        },

        /**
         * Toggles state of sidebar between minimized and maximized
         */
        toggleState: function () {
            switch (this.get('state')) {
            case constants.SIDEBAR_MINIMIZED:
                this.set('state', constants.SIDEBAR_MAXIMIZED);
                break;

            case constants.SIDEBAR_MAXIMIZED:
                this.set('state', constants.SIDEBAR_MINIMIZED);
                break;
            }
        }
    });
});
