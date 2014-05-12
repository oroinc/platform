/*global define*/
define(['../collection', './model'
    ], function (NavigationCollection, PinbarModel) {
    'use strict';

    /**
     * @export  oronavigation/js/pinbar/collection
     * @class   oronavigation.pinbar.Collection
     * @extends oronavigation.Collection
     */
    return NavigationCollection.extend({
        model: PinbarModel,

        initialize: function () {
            this.on('change:position', this.onPositionChange, this);
            this.on('change:url', this.onUrlChange, this);
            this.on('change:maximized', this.onStateChange, this);
        },

        onPositionChange: function (item) {
            this.trigger('positionChange', item);
        },

        onStateChange: function (item) {
            this.trigger('stateChange', item);
        },

        onUrlChange: function (item) {
            this.trigger('urlChange', item);
        }
    });
});
