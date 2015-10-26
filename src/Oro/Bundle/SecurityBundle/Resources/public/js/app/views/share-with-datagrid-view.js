define(function(require) {
    'use strict';

    var ShareWithDatagridView;
    var _ = require('underscore');
    var $ = require('jquery');
    var Routing = require('routing');
    var ShareWithDatagridCollection = require('orosecurity/js/app/models/share-with-datagrid-collection');
    var BaseView = require('oroui/js/app/views/base/view');
    var WidgetManager = require('oroui/js/widget-manager');

    ShareWithDatagridView = BaseView.extend({
        initialize: function(options) {
            this.options = options;
            this.template = _.template($('#share-with-entity-dropdown-item').html());
            this.collection = new ShareWithDatagridCollection();
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
            var dropdown = this.$('.share-with-entity-dropdown');
            var firstItem = this.$('.share-with-entity-current-item');
            this.collection.on('add', function(model) {
                if (!model.attributes.isGranted) {
                    return false;
                }
                var gridUrl = Routing.generate(
                    'oro_share_with_datagrid',
                    {
                        entityClass: model.attributes.className
                    }
                );
                var view = self.template({
                    entity: model
                });
                var $view = $(view);

                if (model.attributes.first) {
                    firstItem.html(model.attributes.label);
                    self.currentTargetClass(model.attributes.className);
                    self.currentGridName(model.attributes.gridName);
                }

                dropdown.append($view);
                dropdown.find('.share-with-entity-item:last').click(function() {
                    self.currentTargetClass(model.attributes.className);
                    self.currentGridName(model.attributes.gridName);
                    dropdown.find('> .share-with-entity-item').each(function() {
                        $(this).removeClass('active');
                    });
                    var item = $(this);
                    firstItem.html(item.html());
                    item.addClass('active');

                    WidgetManager.getWidgetInstanceByAlias('share-with-datagrid', function(widget) {
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
        },

        /**
         * Getter/Setter for current grid name
         *
         * @param {string=} value
         */
        currentGridName: function(value) {
            if (_.isUndefined(value)) {
                value = this._currentGridName;
            } else {
                this._currentGridName = value;
            }
            return value;
        }
    });

    return ShareWithDatagridView;
});
