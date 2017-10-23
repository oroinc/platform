define(function(require) {
    'use strict';

    var DataGridRowSelectWidgetView;
    var BaseView = require('oroui/js/app/views/base/view');
    var widgetManager = require('oroui/js/widget-manager');

    DataGridRowSelectWidgetView = BaseView.extend({
        optionNames: BaseView.prototype.optionNames.concat(['wid', 'multiSelect', 'gridName']),

        autoRender: true,

        listen: {
            'datagrid_create_before mediator': 'onDataGridCreateBefore'
        },

        render: function() {
            DataGridRowSelectWidgetView.__super__.render.apply(this, arguments);

            this.initLayout();

            return this;
        },

        onDataGridCreateBefore: function(options) {
            var self = this;

            if (options.name !== this.gridName) {
                return;
            }

            if (this.multiSelect) {
                options.multiSelectRowEnabled = true;
            } else {
                options.rowClickAction = function(data) {
                    return {
                        run: function() {
                            widgetManager.getWidgetInstance(self.wid, function(widget) {
                                widget.trigger('grid-row-select', data);
                            });
                        }
                    };
                };
            }
        }

    });

    return DataGridRowSelectWidgetView;
});
