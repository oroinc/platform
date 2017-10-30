define(function(require) {
    'use strict';

    var ReportChartDataSchemaView;
    var BaseView = require('oroui/js/app/views/base/view');
    var ChartOptions = require('ororeport/js/chart-options');

    ReportChartDataSchemaView = BaseView.extend({
        initialize: function(options) {
            ChartOptions.initialize(this.el.id, {});

            ReportChartDataSchemaView.__super__.initialize.apply(this, arguments);
        }
    });

    return ReportChartDataSchemaView;
});
