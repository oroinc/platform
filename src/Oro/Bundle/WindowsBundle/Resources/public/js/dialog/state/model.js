define(['backbone'], function(Backbone) {
    'use strict';

    var WindowsModel;

    /**
     * @export  orowindows/js/dialog/state/model
     * @class   orowindows.dialog.state.Model
     * @extends Backbone.Model
     */
    WindowsModel = Backbone.Model.extend({
        /**
         * @inheritDoc
         */
        constructor: function WindowsModel() {
            WindowsModel.__super__.constructor.apply(this, arguments);
        }
    });

    return WindowsModel;
});
