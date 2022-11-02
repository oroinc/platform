define(function(require, exports, module) {
    'use strict';

    const BaseModel = require('oroui/js/app/models/base/model');
    const moduleConfig = require('module-config').default(module.id);

    /**
     * @export  orowindows/js/dialog/state/model
     * @class   orowindows.dialog.state.Model
     * @extends Backbone.Model
     */
    const WindowsModel = BaseModel.extend({
        urlRoot: moduleConfig.urlRoot,

        /**
         * @inheritdoc
         */
        constructor: function WindowsModel(attrs, options) {
            WindowsModel.__super__.constructor.call(this, attrs, options);
        }
    });

    return WindowsModel;
});
