define(function(require) {
    'use strict';

    var ReportChartWidgetView;
    var BaseView = require('oroui/js/app/views/base/view');
    var ChartForm = require('orochart/js/chart_form');

    ReportChartWidgetView = BaseView.extend({
        initialize: function() {
            ChartForm.initialize('#' + this.el.id);

            ReportChartWidgetView.__super__.initialize.apply(this, arguments);
        }
    });

    return ReportChartWidgetView;
});
