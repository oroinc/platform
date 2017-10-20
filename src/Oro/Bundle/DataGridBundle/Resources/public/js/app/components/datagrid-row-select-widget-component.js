define(function(require) {
    'use strict';

    var DataGridRowSelectWidgetComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var widgetManager = require('oroui/js/widget-manager');

    DataGridRowSelectWidgetComponent = BaseComponent.extend({
        optionNames: BaseComponent.prototype.optionNames.concat(['wid', 'multiSelect', 'gridName']),

        listen: {
            'datagrid_create_before mediator': 'onDataGridCreateBefore'
        },

        initialize: function(options) {
            DataGridRowSelectWidgetComponent.__super__.initialize.apply(this, arguments);
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

    return DataGridRowSelectWidgetComponent;
});
