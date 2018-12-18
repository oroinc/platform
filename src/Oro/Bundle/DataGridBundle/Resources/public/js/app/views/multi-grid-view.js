define(function(require) {
    'use strict';

    var _ = require('underscore');
    var $ = require('jquery');
    var ActivityContextCollection = require('oroactivity/js/app/models/activity-context-collection');
    var BaseView = require('oroui/js/app/views/base/view');
    var WidgetManager = require('oroui/js/widget-manager');
    var routing = require('routing');

    /**
     * @exports MultiGridView
     */
    var MultiGridView = BaseView.extend({
        /**
         * @inheritDoc
         */
        constructor: function MultiGridView() {
            MultiGridView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = options;
            this.template = _.template($('#multi-grid-item').html());
            this.collection = new ActivityContextCollection();
            this.initEvents();

            if (this.options.items) {
                _.first(this.options.items).first = true;
                _.each(_.rest(this.options.items), function(item) {
                    item.first = false;
                });
                this.collection.add(this.options.items);
            }
        },

        add: function(model) {
            this.collection.add(model);
        },

        render: function() {
            if (this.collection.models.length === 0) {
                this.$el.hide();
            } else {
                this.$el.show();
            }
        },

        initEvents: function() {
            var self = this;
            var dropdown = this.$('.context-items-dropdown');
            var firstItem = this.$('.activity-context-current-item');
            this.collection.on('add', function(model) {
                var routeParams = self.options.params.grid_query || {params: {}};
                routeParams.params.class_name = model.get('className');
                routeParams.gridName = model.get('gridName');
                if (!_.isUndefined(self.options.params.routeParams)) {
                    routeParams = _.extend(
                        {},
                        routeParams,
                        {params: self.options.params.routeParams}
                    );
                }

                var gridUrl = routing.generate('oro_datagrid_widget', routeParams);

                var view = self.template({
                    entity: model
                });
                var $view = $(view);

                if (model.attributes.first) {
                    firstItem.html(model.attributes.label);
                    self.currentTargetClass(model.attributes.className);
                }

                dropdown.append($view);
                dropdown.find('.dropdown-item:last').click(function() {
                    self.currentTargetClass(model.attributes.className);
                    dropdown.find('.dropdown-item').each(function() {
                        $(this).removeClass('active');
                    });
                    var item = $(this);
                    firstItem.html(item.html());
                    item.addClass('active');
                    var gridWidgetName = self.options.gridWidgetName;
                    WidgetManager.getWidgetInstanceByAlias(gridWidgetName, function(widget) {
                        widget.setUrl(gridUrl);
                        widget.render();
                    });
                });
            });
        },

        /**
         * Getter/Setter for current target className
         *
         * @param {string=} value
         */
        currentTargetClass: function(value) {
            if (_.isUndefined(value)) {
                value = this._currentTargetClass;
            } else {
                this._currentTargetClass = value;
            }
            return value;
        }
    });

    return MultiGridView;
});
