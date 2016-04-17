define(['underscore', 'backbone', 'oroui/js/widget/abstract-widget'
    ], function(_, Backbone, AbstractWidget) {
    'use strict';

    var $ = Backbone.$;

    /**
     * @extends oroui.widget.AbstractWidget
     */
    return AbstractWidget.extend({
        options: _.extend(
            _.extend({}, AbstractWidget.prototype.options),
            {
                type: 'layout',
                rid: null
            }
        ),

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            options = options || {};
            this.options = _.defaults(options, this.options);

            this.widget = this.$el;

            this.initializeWidget(options);
        },

        getActionsElement: function() {
            return null;
        },

        show: function() {
            if (!this.$el.parent().length) {
                this._showRemote();
            }

            AbstractWidget.prototype.show.apply(this);
        },

        _showRemote: function() {
            this.widget.empty();
            this.widget.append(this.$el);
            this.setElement(this.widget);
        },

        loadContent: function() {
            var options = {
                url: this.options.url || window.location.href,
                type: 'get',
                data: '_rid=' + this.options.rid
            };

            this.trigger('beforeContentLoad', this);
            this.loading = $.ajax(options)
                .done(_.bind(this._onContentLoad, this))
                .fail(_.bind(this._onContentLoadFail, this));
        },

        /**
         * @param {String} content
         */
        setContent: function(content) {
            var widgetContent = $(content).find('.widget-content:first').clone();

            AbstractWidget.prototype.setContent.apply(this, widgetContent);
        }
    });
});
