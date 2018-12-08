define(function(require) {
    'use strict';

    var ActivityContextView;
    var _ = require('underscore');
    var $ = require('jquery');
    var ActivityContextCollection = require('oroactivity/js/app/models/activity-context-collection');
    var BaseView = require('oroui/js/app/views/base/view');
    var WidgetManager = require('oroui/js/widget-manager');

    ActivityContextView = BaseView.extend({
        /**
         * @inheritDoc
         */
        constructor: function ActivityContextView() {
            ActivityContextView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = options;
            this.template = _.template($('#activity-context-item').html());
            this.collection = new ActivityContextCollection();
            this.initEvents();

            if (this.options.items) {
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
                var gridUrl = encodeURI(self.options.params.grid_path + '/' + model.attributes.className);
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
                    dropdown.find('> .dropdown-item').each(function() {
                        $(this).removeClass('active');
                    });
                    var item = $(this);
                    firstItem.html(item.html());
                    item.addClass('active');
                    var gridWidgetName = self.options.gridWidgetName || 'activity-context-grid';
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

    return ActivityContextView;
});
