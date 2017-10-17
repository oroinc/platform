define(function(require) {
    'use strict';

    var DataGridRowSelectWidgetComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var widgetManager = require('oroui/js/widget-manager');
    var _ = require('underscore');

    DataGridRowSelectWidgetComponent = BaseComponent.extend({
        listen: {
            'datagrid_create_before mediator': 'onDataGridCreateBefore'
        },

        initialize: function(options) {
            _.extend(this, _.pick(options, ['wid', 'multiSelect', 'gridName']));

            DataGridRowSelectWidgetComponent.__super__.initialize.apply(this, arguments);
        },

        onDataGridCreateBefore: function(options) {
            if (options.name !== this.gridName) {
                return;
            }

            if (this.multiSelect) {
                options.multiSelectRowEnabled = true;
            } else {
                options.rowClickAction = function(data) {
                    return {
                        run: function() {
                            widgetManager.getWidgetInstance(this.wid, function(widget) {
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
