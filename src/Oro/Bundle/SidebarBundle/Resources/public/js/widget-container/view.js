define(['jquery', 'underscore', 'backbone', '../constants',
    'text!./templates/widget-min-template.html',
    'text!./templates/widget-max-template.html'
    ], function($, _, Backbone, constants, widgetMinTemplate, widgetMaxTemplate) {
    'use strict';

    /**
     * @export  orosidebar/js/widget-container/view
     * @class   orosidebar.widgetContainer.View
     * @extends Backbone.View
     */
    return Backbone.View.extend({
        templateMin: _.template(widgetMinTemplate),
        templateMax: _.template(widgetMaxTemplate),

        events: {
            'click .sidebar-widget-header-toggle': 'onClickToggle',
            'click .sidebar-widget-refresh': 'onClickRefresh',
            'click .sidebar-widget-settings': 'onClickSettings',
            'click .sidebar-widget-remove': 'onClickRemove',
            'click .sidebar-widget-close': 'onClickClose'
        },

        initialize: function() {
            var view = this;
            view.stopListening(view.model, 'change');
            view.listenTo(view.model, 'change', view.render);
        },

        render: function() {
            this.$el.attr('data-cid', this.model.cid);

            if (this.model.get('cssClass')) {
                this.$el.attr('class', this.model.get('cssClass'));
            }

            if (this.model.get('state') === constants.WIDGET_MAXIMIZED_HOVER) {
                this.$el.addClass('sidebar-widget-popup');
            } else {
                this.$el.addClass('sidebar-widget-embedded');
            }

            this.model.loadModule().then(_.bind(this._render, this));

            return this;
        },

        /**
         * Renders the widget content once widget module is loaded
         *
         * @param {Object} module
         * @protected
         */
        _render: function(module) {
            var template = this.templateMax;
            if (this.model.get('state') === constants.WIDGET_MINIMIZED) {
                template = this.templateMin;
            }

            var data = this.model.toJSON();
            if (module.titleTemplate) {
                data.title = module.titleTemplate(data);
            }

            this.$el.html(template(data));

            if (this.model.get('state') !== constants.WIDGET_MINIMIZED) {
                if (this.contentView) {
                    this.contentView.dispose();
                }
                this.contentView = new module.ContentView({
                    autoRender: true,
                    model: this.model,
                    el: this.$('.sidebar-widget-content')
                });
            }
        },

        setOffset: function(cord) {
            var view = this;
            view.$el.offset(cord);
        },

        onClickToggle: function(e) {
            e.stopPropagation();
            e.preventDefault();

            this.model.toggleState();
            this.model.save();
        },

        onClickRefresh: function(e) {
            e.stopPropagation();
            e.preventDefault();

            Backbone.trigger('refreshWidget', this.model.cid);
        },

        onClickSettings: function(e) {
            e.stopPropagation();
            e.preventDefault();

            Backbone.trigger('setupWidget', this.model.cid);
        },

        onClickRemove: function(e) {
            e.stopPropagation();
            e.preventDefault();
            Backbone.trigger('removeWidget', this.model.cid);
        },

        onClickClose: function(e) {
            e.stopPropagation();
            e.preventDefault();
            Backbone.trigger('closeWidget', this.model.cid);
        }
    });
});
