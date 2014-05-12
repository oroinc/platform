/*global define*/
define(['underscore', 'backbone', 'oroui/js/widget/abstract'
    ], function (_, Backbone, AbstractWidget) {
    'use strict';

    var $ = Backbone.$;

    /**
     * @export  oro/buttons-widget
     * @class   oro.ButtonsWidget
     * @extends oro.AbstractWidget
     */
    return AbstractWidget.extend({
        options: _.extend(
            _.extend({}, AbstractWidget.prototype.options),
            {
                cssClass: 'pull-left icons-holder',
                type: 'buttons',
                loadingMaskEnabled: false
            }
        ),

        initialize: function(options) {
            options = options || {};

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
            AbstractWidget.prototype.show.apply(this);
        },

        _showStatic: function() {
            var anchorId = '_widget_anchor-' + this.getWid();
            var anchorDiv = $('<div id="' + anchorId + '"/>');
            var parent = this.widget.parent();
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
});
