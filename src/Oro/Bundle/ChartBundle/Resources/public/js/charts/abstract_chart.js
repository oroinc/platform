define([
    'jquery',
    'oroui/js/layout',
    'flotr2',
    'orolocale/js/formatter/number',
    'jquery-ui'
], function($, layout, Flotr, numberFormatter) {
    $.widget('orochart.abstractChart', {
        options: {
            containerId: null,
            data: {},
            colors: [],
            fontColors: [],
            fontSize: 10,
            formatter: null,
            noTicks: null
        },

        _resize: function() {
            this._setChartSize();
            this._draw();
            this._fixChartSize();
            this._setChartContainerSize();

        },

        _setChartSize: function () {
            var $chart = this.element;
            var $widgetContent = $chart.parents('.chart-container').parent();
            var chartWidth = Math.round($widgetContent.width() * 0.9);
            if (chartWidth != $chart.width()) {
                $chart.width(chartWidth);
                $chart.height(Math.min(Math.round(chartWidth * 0.4), 350));

                return true;
            }
            return false;
        },

        _fixChartSize: function() {
            var $chart = this.element;
            var $lables = $chart.find(".flotr-grid-label");
            var labelMaxHeight = $lables.height();
            var labelMinHeight = $lables.height();

            $lables.each(function(index, element) {
                var height = $(element).height();
                if(height > labelMaxHeight) {
                    labelMaxHeight = height;
                } else if(height < labelMinHeight) {
                    labelMinHeight = height;
                }
            });

            $chart.height($chart.height() + labelMaxHeight - labelMinHeight);
        },

        _setChartContainerSize: function () {
            this.element.closest('.clearfix').width(this.element.width());
        },

        _draw: function () {
            this.element.html("abstract chart");
        },

        _init: function () {
            layout.onPageRendered(function () {
                this._resize();
            }.bind(this));

            $(window).resize(function () {
                this._resize();

                // update if something was broken again
                // yeah, man, it works
                setTimeout(function() {
                    this._resize();
                }.bind(this), 500);
            }.bind(this));
        }
    });
});