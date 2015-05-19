/*global define*/
define(function (require) {
    'use strict';

    var EmailContextView,
        _ = require('underscore'),
        $ = require('jquery'),
        EmailContextCollection = require('oroemail/js/app/models/email-context-collection'),
        BaseView = require('oroui/js/app/views/base/view'),
        WidgetManager = require('oroui/js/widget-manager');

    EmailContextView = BaseView.extend({
        initialize: function(options) {
            this.options = options;
            this.template = _.template($('#email-context-item').html());
            this.collection = new EmailContextCollection();
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
            var dropdown = this.$('#context-items-dropdown');
            var firstItem = this.$('#email-context-current-item');

            this.collection.on('add', function(model) {
                var gridUrl = self.options.params.grid_path + '/' + model.attributes.className,
                    $contextCurrentTargetClass = self.$('[id^=context-current-target-class]'),
                    $contextCurrentTargetGrid = self.$('[id^=context-current-target-grid]'),
                    view = self.template({
                        entity: model
                    }),
                    $view = $(view);

                if (model.attributes.first) {
                    firstItem.html(model.attributes.label);
                    $contextCurrentTargetClass.data('value', model.attributes.className);
                    $contextCurrentTargetGrid.data('value', model.attributes.gridName);
                }

                dropdown.append($view);
                dropdown.find('.context-item:last').click(function() {
                    $contextCurrentTargetClass.data('value', model.attributes.className);
                    $contextCurrentTargetGrid.data('value', model.attributes.gridName);
                    dropdown.find('> .context-item').each(function() {
                        $(this).removeClass('active');
                    });
                    var item = $(this);
                    firstItem.html(item.html());
                    item.addClass('active');

                    WidgetManager.getWidgetInstanceByAlias('email-context-grid', function(widget) {
                        widget.setUrl(gridUrl);
                        widget.render();
                    });
                });
            });
        }
    });

    return EmailContextView;
});
