import widgetManager from 'oroui/js/widget-manager';
import BaseView from 'oroui/js/app/views/base/view';

const TransitionErrorView = BaseView.extend({
    optionNames: BaseView.prototype.optionNames.concat(['wid']),

    /**
     * @inheritdoc
     */
    constructor: function TransitionErrorView(options) {
        TransitionErrorView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        widgetManager.getWidgetInstance(this.wid, function(widget) {
            widget.trigger('formSaveError');
        });

        TransitionErrorView.__super__.initialize.call(this, options);
    }
});

export default TransitionErrorView;
