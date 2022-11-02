define(function(require) {
    'use strict';

    const _ = require('underscore');
    const $ = require('jquery');
    const ActivityContextCollection = require('oroactivity/js/app/models/activity-context-collection');
    const BaseView = require('oroui/js/app/views/base/view');
    const WidgetManager = require('oroui/js/widget-manager');

    const ActivityContextView = BaseView.extend({
        /**
         * @inheritdoc
         */
        constructor: function ActivityContextView(options) {
            ActivityContextView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
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
            const self = this;
            const dropdown = this.$('.context-items-dropdown');
            const firstItem = this.$('.activity-context-current-item');
            this.collection.on('add', function(model) {
                const gridUrl = encodeURI(self.options.params.grid_path + '/' + model.attributes.className);
                const view = self.template({
                    entity: model
                });
                const $view = $(view);

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
                    const item = $(this);
                    firstItem.html(item.html());
                    item.addClass('active');
                    const gridWidgetName = self.options.gridWidgetName || 'activity-context-grid';
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
