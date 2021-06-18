define(['backbone'], function(Backbone) {
    'use strict';

    /**
     * @export  orowindows/js/dialog/state/model
     * @class   orowindows.dialog.state.Model
     * @extends Backbone.Model
     */
    const WindowsModel = Backbone.Model.extend({
        /**
         * @inheritdoc
         */
        constructor: function WindowsModel(attrs, options) {
            WindowsModel.__super__.constructor.call(this, attrs, options);
        }
    });

    return WindowsModel;
});
