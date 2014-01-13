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
        formatter: '',
        nozzleSteps: []
    },

    stack: [],
    stacked: false,
    shiftLabels: false,
    originData: [],

    in_array: function(what, where) {
        for(var i=0; i<where.length; i++) {
            if(what == where[i]) {
                return true;
            }
        }

        return false;
    },

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
            if (!self.in_array(label, options.nozzleSteps)) {
                summ += funnel;
            }
        });
        Flotr._.each(data, function (funnel, iterator) {
            if (funnel == 0 && !self.in_array(iterator, options.nozzleSteps)) {
                reSumm += summ / 100 * 2;
                data[iterator] = summ / 100 * 2;
                self.shiftLabels = true;
            } else if (self.in_array(iterator, options.nozzleSteps)) {
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

        self.stack[0] = {};
        self.stack[0].x1 = options.leftMargin || 0;
        self.stack[0].y1 = options.marginY;

        var segmentData = {
            'prevStepWidth': marginWidth,
            'prevStepWidthDelta': 0,
            'prevStepHeight': marginHeight + extraHeight,
            'funnelSumm': options.marginY
        };

        Flotr._.each(data, function (funnel, iterator) {
            if (self.in_array(iterator, options.nozzleSteps)) {
                var isNozzleStep = true;
            } else {
                var isNozzleStep = false;
            }
            var funnelSize = marginHeight / summ * funnel;
            if (options.explode > 0 /*&& Object.keys(data).length > i+1*/) {
                funnelSize -= options.explode;
            }

            segmentData = self.calculateSegment(options, funnelSize, i, marginWidth, segmentData, true, isNozzleStep);
            if (options.explode > 0 && Object.keys(data).length != i+1) {
                segmentData = self.calculateSegment(options, options.explode, i, marginWidth, segmentData, false, isNozzleStep);
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
                var belongSide = true;
                /**
                 *  left/right rectangle side case
                 *  detect mouse is in figure
                 *
                 *  (x1 - x0) * (y2 - y1) - (x2 - x1) * (y1 - y0)
                 *  (x2 - x0) * (y3 - y2) - (x3 - x2) * (y2 - y0)
                 *  (x3 - x0) * (y1 - y3) - (x1 - x3) * (y3 - y0)
                 */

                if (
                    x >= self.stack[i].x1 && x <= self.stack[i].x4 &&
                    y >= self.stack[i].y1 && y <= self.stack[i].y4
                ){
                    var
                        s1 = (self.stack[i].x1 - x) * (self.stack[i].y4 - self.stack[i].y1) - (self.stack[i].x1 - self.stack[i].x1) * (self.stack[i].y1 - y),
                        s2 = (self.stack[i].x1 - x) * (self.stack[i].y4 - self.stack[i].y4) - (self.stack[i].x4 - self.stack[i].x1) * (self.stack[i].y4 - y),
                        s3 = (self.stack[i].x4 - x) * (self.stack[i].y1 - self.stack[i].y4) - (self.stack[i].x1 - self.stack[i].x4) * (self.stack[i].y4 - y);
                    if (s1 == 0 || s2 == 0 || s3 == 0 || (s1 > 0 && s2 > 0 && s3 > 0) || (s1 < 0 && s2 < 0 && s3 < 0)){
                        belongSide = false;
                    }
                }

                // right rectangle side case
                if (
                    x >= self.stack[i].x3 && x <= self.stack[i].x2 &&
                    y >= self.stack[i].y2 && y <= self.stack[i].y3
                ){
                    var
                        s1 = (self.stack[i].x3 - x) * (self.stack[i].y2 - self.stack[i].y3) - (self.stack[i].x2 - self.stack[i].x3) * (self.stack[i].y3 - y),
                        s2 = (self.stack[i].x2 - x) * (self.stack[i].y3 - self.stack[i].y2) - (self.stack[i].x2 - self.stack[i].x2) * (self.stack[i].y2 - y),
                        s3 = (self.stack[i].x2 - x) * (self.stack[i].y3 - self.stack[i].y3) - (self.stack[i].x3 - self.stack[i].x2) * (self.stack[i].y3 - y);
                    if (s1 == 0 || s2 == 0 || s3 == 0 || (s1 > 0 && s2 > 0 && s3 > 0) || (s1 < 0 && s2 < 0 && s3 < 0)){
                        belongSide = false;
                    }
                }

                // full rectangle case
                if (
                    y >= self.stack[i].y1 && y <= self.stack[i].y3
                    && x >= self.stack[i].x1 && x <= self.stack[i].x2
                    && belongSide != false
                ) {
                    if (self.stacked === i) return;

                    self.stacked = i;
                    self.clearHit(options);
                    self.drawHit(options, i);

                    //self.drawTooltip('Test', x+10 , y);
                    return;
                }
            }

            self.stacked = false;
            self.clearHit(options);
    },

    drawTooltip: function (content, x, y, options){
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

    calculateSegment: function(options, funnel, iterator, marginWidth, segmentData, renderable, isNozzleStep) {
        var
            context = options.context,
            prevStepWidth      = segmentData.prevStepWidth,
            prevStepWidthDelta = segmentData.prevStepWidthDelta,
            prevStepHeight     = segmentData.prevStepHeight,
            funnelSumm         = segmentData.funnelSumm;

        context.lineWidth = options.lineWidth;
        context.strokeStyle = options.colors[iterator];
        context.fillStyle = Flotr.Color.parse(options.colors[iterator]).alpha(options.fillOpacity).toString();

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

            if (isNozzleStep) {
                var x3 = this.stack[iterator].x2;
                var x4 = Math.ceil(marginWidth / 2 - AD) + (options.leftMargin || 0);
            } else {
                var x3 = Math.round(marginWidth / 2 + BC) + (options.leftMargin || 0);
                var x4 = Math.round(marginWidth / 2 - BC) + (options.leftMargin || 0);
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

            self.renderLabel(context, options, Object.keys(options.data)[iterator], funnelSumm, iterator);
        }

        funnelSumm += funnel;

        context.beginPath();
        if (isNozzleStep) {
            var nextX = Math.ceil(marginWidth / 2 - AD) + (options.leftMargin || 0);
        } else {
            var nextX = Math.ceil(marginWidth / 2 - BC) + (options.leftMargin || 0);
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

    renderLabel: function(context, options, label, startY, iterator) {
        var distX = (options.width - options.marginX) / 2,
            count = Object.keys(options.data).length,
            distY = (iterator == 0)
                ? options.marginY
                : this.shiftLabels
                    ? (options.height - options.marginY * 2) / (count + 1) * iterator
                    : startY,
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
            formattedValue = (options.formatter)
                ? options.formatter(this.originData[label])
                : this.originData[label],
            divStyle =
                'position:absolute;' +
                style.textBaseline + ':' + (distY - style.size)  + 'px;' +
                style.textAlign + ':' + (distX + (options.marginX + options.width)/3 + 10) + 'px;';

        html.push('<div style="', divStyle, '" class="flotr-grid-label">', label + ': ' + formattedValue, '</div>');

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
