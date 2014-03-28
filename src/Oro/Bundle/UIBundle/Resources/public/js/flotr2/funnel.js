/*global define*/
define(['jquery', 'flotr2'], function ($, Flotr) {

Flotr.addType('funnel', {
    options: {
        show: false,
        lineWidth: 2,
        fill: true,
        fillColor: null,
        fillOpacity: 0.5,
        fontColor: "#B2B2B2",
        explode: 5,
        marginX: 450,
        marginY: 20,
        leftMargin: 160,
        colors: ['#ACD39C', '#BE9DE2', '#6598DA', '#ECC87E', '#A4A2F6', '#6487BF', '#65BC87', '#8985C2', '#ECB574', '#84A377'],
        nozzleSteps: [],
        tickFormatter: function(label, value) {
            return label + ': ' + value;
        },
        nozzleFormatter: function(label, value) {
            return label + ': ' + value;
        }
    },
    stack: [],
    stacked: false,
    shiftLabels: false,
    originData: [],

    plot: function (options) {
        var
            self = this,
            marginWidth = options.marginX ? options.width - options.marginX : options.width,
            marginHeight = options.marginY || options.explode ? options.height - options.marginY * 2 - options.explode : options.height,
            extraHeight = options.extraHeight || 0,
            data = options.data,
            context = options.context,
            summ = 0,
            reSumm = 0,
            i = 0;

        Flotr._.each(data, function (funnel, label) {
            if (options.nozzleSteps.indexOf(label) === -1) {
                summ += funnel;
            }
        });

        Flotr._.each(data, function (funnel, iterator) {
            if (funnel == 0 && options.nozzleSteps.indexOf(iterator) === -1) {
                reSumm += summ / 100 * 2;
                data[iterator] = summ / 100 * 2;
                self.shiftLabels = true;
            } else if (options.nozzleSteps.indexOf(iterator) !== -1) {
                reSumm += summ / 100 * 10;
                data[iterator] = summ / 100 * 10;
                self.shiftLabels = true;
            } else {
                reSumm += funnel;
            }
        });
        summ = reSumm;

        context.lineJoin = 'round';
        context.translate(0.5, 0.5);
        context.beginPath();
        context.moveTo(options.leftMargin || 0, options.marginY);

        self.stack[0]    = {};
        self.stack[0].x1 = options.leftMargin || 0;
        self.stack[0].y1 = options.marginY;

        var segmentData = {
            'prevStepWidth': marginWidth,
            'prevStepWidthDelta': 0,
            'prevStepHeight': marginHeight + extraHeight,
            'funnelSumm': options.marginY
        };

        Flotr._.each(data, function (funnel) {
            var funnelSize = marginHeight / summ * funnel;
            if (options.explode > 0 && funnelSize > options.explode) {
                funnelSize -= options.explode;
            }

            segmentData = self.calculateSegment(options, funnelSize, i, marginWidth, segmentData, true);
            if (options.explode > 0 && Object.keys(data).length != i+1) {
                segmentData = self.calculateSegment(options, options.explode, i, marginWidth, segmentData, false);
            }
            i++;
        });
    },

    hit: function (options) {
        var
            self   = this,
            args   = options.args,
            mouse  = args[0],
            x      = mouse.relX,
            y      = mouse.relY;

            for (var i in self.stack) {
                var
                    belongSide = true,
                    seg        = self.stack[i]; //Current funnel's segment

                /**
                 *  left/right rectangle side case
                 *  detect mouse is in figure
                 *
                 *  (x1 - x0) * (y2 - y1) - (x2 - x1) * (y1 - y0)
                 *  (x2 - x0) * (y3 - y2) - (x3 - x2) * (y2 - y0)
                 *  (x3 - x0) * (y1 - y3) - (x1 - x3) * (y3 - y0)
                 */

                if (x >= seg.x1 && x <= seg.x4 && y >= seg.y1 && y <= seg.y4) {
                    var
                        s1 = (seg.x1 - x) * (seg.y4 - seg.y1) - (seg.x1 - seg.x1) * (seg.y1 - y),
                        s2 = (seg.x1 - x) * (seg.y4 - seg.y4) - (seg.x4 - seg.x1) * (seg.y4 - y),
                        s3 = (seg.x4 - x) * (seg.y1 - seg.y4) - (seg.x1 - seg.x4) * (seg.y4 - y);
                    if (s1 == 0 || s2 == 0 || s3 == 0 || (s1 > 0 && s2 > 0 && s3 > 0) || (s1 < 0 && s2 < 0 && s3 < 0)){
                        belongSide = false;
                    }
                }

                // right rectangle side case
                if (x >= seg.x3 && x <= seg.x2 && y >= seg.y2 && y <= seg.y3) {
                    var
                        s1 = (seg.x3 - x) * (seg.y2 - seg.y3) - (seg.x2 - seg.x3) * (seg.y3 - y),
                        s2 = (seg.x2 - x) * (seg.y3 - seg.y2) - (seg.x2 - seg.x2) * (seg.y2 - y),
                        s3 = (seg.x2 - x) * (seg.y3 - seg.y3) - (seg.x3 - seg.x2) * (seg.y3 - y);
                    if (s1 == 0 || s2 == 0 || s3 == 0 || (s1 > 0 && s2 > 0 && s3 > 0) || (s1 < 0 && s2 < 0 && s3 < 0)){
                        belongSide = false;
                    }
                }

                // full rectangle case
                if (y >= seg.y1 && y <= seg.y3 && x >= seg.x1 && x <= seg.x2 && belongSide != false) {
                    if (self.stacked === i) return;

                    self.stacked = i;
                    self.clearHit(options);
                    self.drawHit(options, i);

                    return;
                }
            }

            self.stacked = false;
            self.clearHit(options);
    },

    drawHit: function (options, i) {
        var self = this, context = options.context;
        context.save();
        context.lineJoin    = 'round';
        context.strokeStyle = '#FF3F19';
        context.lineWidth   = options.lineWidth;
        context.beginPath();
        context.moveTo(self.stack[i].x1 + options.lineWidth * 2, self.stack[i].y1);
        context.lineTo(self.stack[i].x2 + options.lineWidth * 2, self.stack[i].y2);
        context.lineTo(self.stack[i].x3 + options.lineWidth * 2, self.stack[i].y3);
        context.lineTo(self.stack[i].x4 + options.lineWidth * 2, self.stack[i].y4);
        context.closePath();
        context.stroke();
        context.restore();
    },

    clearHit: function (options) {
        var
            self = this,
            context = options.context;

        context.save();
        for (var i in self.stack) {
            context.clearRect(
                self.stack[i].x1 - options.lineWidth * 2,
                self.stack[i].y1 - options.lineWidth,
                self.stack[i].x2 - self.stack[i].x1 + options.lineWidth * 5,
                self.stack[i].y4 - self.stack[i].y1 + options.lineWidth * 5
            );
        }
        context.restore();
    },

    draw: function (options) {
        var context = options.context;

        context.save();
        context.lineJoin = 'miter';
        context.lineWidth = options.lineWidth;

        this.originData = _.clone(options.data);

        this.plot(options);

        context.restore();
    },

    calculateSegment: function(options, funnel, iterator, marginWidth, segmentData, renderable) {
        var
            context = options.context,
            prevStepWidth      = segmentData.prevStepWidth,
            prevStepWidthDelta = segmentData.prevStepWidthDelta,
            prevStepHeight     = segmentData.prevStepHeight,
            funnelSumm         = segmentData.funnelSumm,
            isNozzleStep       = true,
            label              = Object.keys(options.data)[iterator];

        if (options.nozzleSteps.indexOf(label) === -1) {
            isNozzleStep = false;
        }

        context.lineWidth   = options.lineWidth;
        context.strokeStyle = options.colors[iterator];
        context.fillStyle   = Flotr.Color.parse(options.colors[iterator]).alpha(options.fillOpacity).toString();

        /**
         * Each segment calculate
         *
         *    D    D0            A                 D1
         *     ------------------------------------  AB  - funnel segment size (height) OR height of previous segment -> "prevStepHeight"
         *     \   |            |                /   AP  - full height (with margins) -> "marginHeight"
         *      \  |            |               /    DD1 - full width (OR full width of previous segment) "prevStepWidth"
         *       \ |            |              /     DD0 - "prevStepWidthDelta"
         *        \|            |             /
         *         \            | B          /       On any calculation step we should know BC
         *        C -------------------------          to correctly render segment of funnel
         *           \          |          /
         *            \         |         /
         *             \        |        /
         *              \       |       /
         *               \      |      /
         *                \     |     /
         *                 \    |    /
         *                  \   |   /
         *                   \  |  /
         *                    \ | /
         *                     \|/
         *                      | P
         */

        var
            self = this,
            AD = Math.ceil(prevStepWidth / 2),
            BC = Math.ceil((AD * (prevStepHeight - funnel)) / prevStepHeight);

        if (renderable) {
            this.stack[iterator].x2 = prevStepWidth + prevStepWidthDelta + (options.leftMargin || 0);
            this.stack[iterator].y2 = funnelSumm;

            var x3 = Math.round(marginWidth / 2 + BC) + (options.leftMargin || 0);
            var x4 = Math.round(marginWidth / 2 - BC) + (options.leftMargin || 0);

            if (isNozzleStep) {
                x3 = this.stack[iterator].x2;
                x4 = Math.ceil(marginWidth / 2 - AD) + (options.leftMargin || 0);
            }

            this.stack[iterator].x3 = x3;
            this.stack[iterator].x4 = x4;

            this.stack[iterator].y3 =
            this.stack[iterator].y4 = (funnelSumm + funnel);

            context.lineTo(this.stack[iterator].x2, this.stack[iterator].y2); // lineTo x2y2
            context.lineTo(this.stack[iterator].x3, this.stack[iterator].y3); // lineTo x3y3
            if (this.stack[iterator].x3 != this.stack[iterator].x4) {
                context.lineTo(this.stack[iterator].x4, this.stack[iterator].y4); // lineTo x4y4
            }

            context.closePath();
            context.stroke();
            context.fill();

            self.renderLabel(context, options, label, funnelSumm, iterator, isNozzleStep);
        }

        funnelSumm += funnel;

        context.beginPath();

        var nextX = Math.ceil(marginWidth / 2 - BC) + (options.leftMargin || 0);
        if (isNozzleStep) {
            nextX = Math.ceil(marginWidth / 2 - AD) + (options.leftMargin || 0);
        }

        context.moveTo(nextX , funnelSumm);

        self.stack[iterator + 1] = {};
        self.stack[iterator + 1].x1 = nextX;
        self.stack[iterator + 1].y1 = funnelSumm;

        return {
            'prevStepWidth'     : isNozzleStep ? AD * 2 : BC * 2,
            'prevStepWidthDelta': isNozzleStep ? marginWidth / 2 - AD: marginWidth / 2 - BC,
            'prevStepHeight'    : prevStepHeight - funnel,
            'funnelSumm'        : funnelSumm
        };
    },

    renderLabel: function(context, options, label, startY, iterator, isNozzleStep) {
        var $prev = $(options.element).find('.flotr-grid-label').last(),
            distX = (options.width - options.marginX) / 2,
            distY = !$prev[0]
                ? options.marginY
                : ! this.shiftLabels
                    ? startY
                    : $prev.position().top + $prev.height() + options.fontSize * 1.2,
            style = {
                size : options.fontSize * 1.2,
                color : options.fontColor,
                weight : 1.5
            };
        options.htmlText   = true;
        style.textAlign    = 'left';
        style.textBaseline = 'top';
        style.wordWrap     = 'break-word';

        var html = [],
            divStyle =
                'position:absolute;' +
                style.textBaseline + ':' + (distY - style.size)  + 'px;' +
                style.textAlign + ':' + (distX + (options.marginX + options.width)/3 + 10) + 'px;',
            labelString = isNozzleStep
                ? options.nozzleFormatter(label, this.originData[label])
                : options.tickFormatter(label, this.originData[label]);

        html.push('<div style="', divStyle, '" class="flotr-grid-label">', labelString, '</div>');

        var div = Flotr.DOM.node('<div style="color:#454545" class="flotr-labels"></div>');
        Flotr.DOM.insert(div, html.join(''));
        Flotr.DOM.insert(options.element, div);

        // label line
        context.beginPath();
        context.lineWidth = 1;
        context.strokeStyle = options.fontColor;
        context.moveTo(this.stack[iterator].x2, this.stack[iterator].y2);
        context.lineTo(distX + (options.marginX + options.width)/3, distY);
        context.stroke();
    }
});

});
