import _ from 'underscore';
import $ from 'jquery';
import AbstractWidgetView from 'oroui/js/widget/abstract-widget';

const InlineWidgetView = AbstractWidgetView.extend({
    /**
     * @inheritDoc
     */
    options: _.extend(
        _.extend({}, AbstractWidgetView.prototype.options),
        {
            type: 'inline',
            loadingMaskEnabled: false
        }
    ),

    /**
     * @inheritDoc
     */
    constructor: function InlineWidgetView(options) {
        InlineWidgetView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritDoc
     */
    initialize(options) {
        options = options || {};
        this.options = _.defaults(options, this.options);

        this.widget = this.$el;
        this.widget.addClass(this.options.cssClass);

        this.initializeWidget(options);
    },

    /**
     * @inheritDoc
     */
    setTitle(title) {
        this.widget.attr('title', title);
    },

    /**
     * @inheritDoc
     */
    getActionsElement() {
        return null;
    },

    /**
     * @inheritDoc
     */
    show() {
        if (!this.$el.data('wid')) {
            if (this.$el.parent().length) {
                this._showStatic();
            } else {
                this._showRemote();
            }
        }
        AbstractWidgetView.prototype.show.call(this);
    },

    _showStatic() {
        const anchorId = '_widget_anchor-' + this.getWid();
        const anchorDiv = $('<div id="' + anchorId + '"/>');
        const parent = this.widget.parent();
        anchorDiv.insertAfter(parent);
        $('#' + anchorId).replaceWith($(this.widget));
        parent.remove();
    },

    _showRemote() {
        this.widget.empty();
        this.widget.append(this.$el.children());
        this.setElement(this.widget);
    }
});

export default InlineWidgetView;
