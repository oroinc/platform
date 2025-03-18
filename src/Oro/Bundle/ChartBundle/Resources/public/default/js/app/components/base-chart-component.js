import {isRTL} from 'underscore';
import BarChartComponent from 'orochart/js/app/components/bar-chart-component';
import chartTemplate from 'tpl-loader!orochart/default/templates/chart.html';

const FrontendBarChartComponent = BarChartComponent.extend({
    CHART_HEIGHT: 228,

    aspectRatio: 1,

    template: chartTemplate,

    listen: {
        'content:shown mediator': 'debouncedUpdate',
        'content:hidden mediator': 'debouncedUpdate'
    },

    /**
     * @inheritdoc
     */
    constructor: function FrontendBarChartComponent(options) {
        if (isRTL()) {
            options.data = [...options.data].reverse();
        }
        FrontendBarChartComponent.__super__.constructor.call(this, options);
    },

    update() {
        if (!this.$chart.is(':hidden')) {
            FrontendBarChartComponent.__super__.update.call(this);
        }
    },

    fixSize() {
        FrontendBarChartComponent.__super__.fixSize.call(this);

        const $labels = this.$chart.find('.flotr-grid-label-y');
        const labels = $labels.toArray();

        const maxWidth = Math.max(...labels.map(el => el.offsetWidth), 34);

        $labels.each((i, el) => {
            el.style.width = maxWidth + 'px';

            const offset = parseInt(this.getChartCSSValue('--chart-grid-label-y-margin')) * -1;

            if (isRTL()) {
                el.style.left = null;
                el.style.right = `${offset}px`;
            } else {
                el.style.left = `${offset}px`;
            }
        });
    },

    /**
     * @returns {number}
     */
    calcChartWidth() {
        const $widgetContent = this.$chart.parents('.chart-container').parent();
        const offset = parseInt(this.getChartCSSValue('--chart-grid-label-y-margin')) || 0;

        this.$chart.css(isRTL() ? 'right' : 'left', offset);
        return Math.round($widgetContent.width() - offset);
    },

    /**
     * Gets CSS property of chart element
     * @param CSSVariable
     * @returns {string}
     */
    getChartCSSValue(CSSVariable) {
        return getComputedStyle(this.$chart.get(0)).getPropertyValue(CSSVariable);
    },

    /**
     * @returns {number}
     */
    getChartHeight() {
        return parseInt(this.getChartCSSValue('--chart-height')) || this.CHART_HEIGHT;
    }
});


export default FrontendBarChartComponent;
