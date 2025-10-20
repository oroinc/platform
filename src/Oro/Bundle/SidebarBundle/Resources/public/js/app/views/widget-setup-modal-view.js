import _ from 'underscore';
import __ from 'orotranslation/js/translator';
import ModalView from 'oroui/js/modal';

const WidgetSetupModalView = ModalView.extend({
    /** @property {String} */
    className: 'modal oro-modal-normal widget-setup',

    /**
     * @inheritdoc
     */
    constructor: function WidgetSetupModalView(options) {
        WidgetSetupModalView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        options.snapshot = options.snapshot || {};

        options.content = new options.contentView({
            className: 'sidebar-widget-setup form-horizontal',
            model: this.model
        });

        options.title = _.result(options.content, 'widgetTitle') || __('oro.sidebar.widget.setup.dialog.title');

        WidgetSetupModalView.__super__.initialize.call(this, options);

        this._bindEventHandlers();
    },

    _bindEventHandlers: function() {
        this.listenTo(this.model, 'change:settings', function() {
            this.model.save();
        });

        this.options.content.once('close', function() {
            this.close();
        }, this);
    }
});

export default WidgetSetupModalView;
