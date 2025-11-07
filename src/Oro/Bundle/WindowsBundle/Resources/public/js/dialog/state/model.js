import BaseModel from 'oroui/js/app/models/base/model';
import moduleConfig from 'module-config';
const config = moduleConfig(module.id);

/**
 * @export  orowindows/js/dialog/state/model
 * @class   orowindows.dialog.state.Model
 * @extends Backbone.Model
 */
const WindowsModel = BaseModel.extend({
    urlRoot: config.urlRoot,

    /**
     * @inheritdoc
     */
    constructor: function WindowsModel(attrs, options) {
        WindowsModel.__super__.constructor.call(this, attrs, options);
    }
});

export default WindowsModel;
