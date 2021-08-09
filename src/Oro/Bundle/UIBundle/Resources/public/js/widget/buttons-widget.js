define(['underscore', 'backbone', 'oroui/js/widget/abstract-widget'
], function(_, Backbone, AbstractWidgetView) {
    'use strict';

    const $ = Backbone.$;

    /**
     * @export  oro/buttons-widget
     * @class   oro.ButtonsWidgetView
     * @extends oroui.widget.AbstractWidgetView
     */
    const ButtonsWidgetView = AbstractWidgetView.extend({
        options: _.extend(
            _.extend({}, AbstractWidgetView.prototype.options),
            {
                cssClass: 'pull-left icons-holder',
                type: 'buttons',
                loadingMaskEnabled: false
            }
        ),

        /**
         * @inheritdoc
         */
        constructor: function ButtonsWidgetView(options) {
            ButtonsWidgetView.__super__.constructor.call(this, options);
        },

        initialize: function(options) {
            options = options || {};
            this.options = _.defaults(options, this.options);

            this.widget = this.$el;
            this.widget.addClass(this.options.cssClass);

            this.initializeWidget(options);
        },

        setTitle: function(title) {
            this.widget.attr('title', title);
        },

        getActionsElement: function() {
            return null;
        },

        show: function() {
            if (!this.$el.data('wid')) {
                if (this.$el.parent().length) {
                    this._showStatic();
                } else {
                    this._showRemote();
                }
            }
            AbstractWidgetView.prototype.show.call(this);
        },

        _showStatic: function() {
            const anchorId = '_widget_anchor-' + this.getWid();
            const anchorDiv = $('<div id="' + anchorId + '"/>');
            const parent = this.widget.parent();
            anchorDiv.insertAfter(parent);
            $('#' + anchorId).replaceWith($(this.widget));
            parent.remove();
        },

        _showRemote: function() {
            this.widget.empty();
            this.widget.append(this.$el);
            this.setElement(this.widget);
        }
    });

    return ButtonsWidgetView;
});
