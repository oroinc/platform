Flotr.addType('funnel', {
    options: {
        show: false,
        lineWidth: 2,
        fill: true,
        fillColor: null,
        fillOpacity: 0.5,
        fontColor: "#B2B2B2",
        explode: 5,
        extraHeight: 150,
        marginX: 200,
        marginY: 25,
        colors: ['#ACD39C', '#BE9DE2', '#6598DA', '#ECC87E', '#A4A2F6', '#6487BF', '#65BC87', '#8985C2', '#ECB574', '#84A377'],
    },

    stack: [],
    stacked: false,

    plot: function (options) {
        var self = this;
        var marginHeight = options.marginY ? options.height - options.marginY * 2 : options.height;
        var marginWidth = options.marginX ? options.width - options.marginX : options.width;
        var
            extraHeight = options.extraHeight || 0,
            data = options.data,
            context = options.context,
            summ = 0,
            i = 0;

        Flotr._.each(data, function (funnel) {
            summ += funnel;
        });

        context.lineJoin = 'round';
        context.translate(0.5, 0.5);
        context.beginPath();
        context.moveTo(options.marginX, options.marginY);

        self.stack[0] = {};
        self.stack[0].x1 = options.marginX;
        self.stack[0].y1 = options.marginY;

        var segmentData = {
            'prevStepWidth': marginWidth,
            'prevStepWidthDelta': 0,
            'prevStepHeight': (marginHeight + extraHeight),
            'funnelSumm': options.marginY
        };

        Flotr._.each(data, function (funnel) {
            var funnelSize = Math.ceil(funnel * marginHeight / summ);
            segmentData = self.calculateSegment(options, funnelSize, i, marginWidth, segmentData, true);

            if (options.explode > 0) {
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
                var belongSide = true;
                /**
                 *  left rectangle side case
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
                    y >= self.stack[i].y1
                    && y <= self.stack[i].y3
                    && x >= self.stack[i].x1
                    && x <= self.stack[i].x2
                    && belongSide != false
                ) {
                    if (self.stacked === i) {
                        return;
                    }
                    self.stacked = i;
                    self.clearHit(options);
                    self.drawHit(options, i);
                    self.drawTooltip('Test', x+10 , y);
                    return;
                }
            }

            self.stacked = false;
            self.clearHit(options);
    },

    drawTooltip: function (content, x, y, options){
        //console.log('tooltip');
    },

    drawHit: function (options, i) {
        var
            self = this,
            context = options.context;

        context.save();

        context.lineJoin    = 'round';
        context.strokeStyle = '#FF3F19';
        context.lineWidth   = options.lineWidth;

        context.beginPath();

        context.moveTo(self.stack[i].x1 + options.lineWidth, self.stack[i].y1);
        context.lineTo(self.stack[i].x2 + options.lineWidth, self.stack[i].y2);
        context.lineTo(self.stack[i].x3 + options.lineWidth, self.stack[i].y3);
        context.lineTo(self.stack[i].x4 + options.lineWidth, self.stack[i].y4);
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
                self.stack[i].x1,
                self.stack[i].y1 - options.lineWidth,
                self.stack[i].x2 + options.lineWidth - self.stack[i].x1 + options.lineWidth,
                self.stack[i].y4 + options.lineWidth - self.stack[i].y1 + options.lineWidth
            );
        }
        context.restore();
    },

    draw: function (options) {
        var context = options.context;

        context.save();
        context.lineJoin = 'miter';
        context.lineWidth = options.lineWidth;
        this.plot(options);

        context.restore();
    },

    calculateSegment: function(options, funnel, iterator, marginWidth, segmentData, renderable) {
        var
            context = options.context,
            prevStepWidth = segmentData.prevStepWidth,
            prevStepWidthDelta = segmentData.prevStepWidthDelta,
            prevStepHeight= segmentData.prevStepHeight,
            funnelSumm = segmentData.funnelSumm;

        context.lineWidth = options.lineWidth;
        context.strokeStyle = options.colors[iterator];
        context.fillStyle = Flotr.Color.parse(options.colors[iterator]).alpha(options.fillOpacity).toString() ;
        var
            self = this,
            AD = Math.ceil(prevStepWidth / 2),
            BC = Math.ceil((AD * (prevStepHeight - funnel)) / prevStepHeight);

        if (renderable) {
            this.stack[iterator].x2 = prevStepWidth + prevStepWidthDelta;
            this.stack[iterator].y2 = funnelSumm;
            this.stack[iterator].x3 = Math.round(marginWidth / 2 + BC);
            this.stack[iterator].y3 = funnelSumm + funnel;
            this.stack[iterator].x4 = Math.round(marginWidth / 2 - BC) + options.marginX;
            this.stack[iterator].y4 = funnelSumm + funnel;

            context.lineTo(prevStepWidth + prevStepWidthDelta, funnelSumm);
            context.lineTo(Math.round(marginWidth / 2 + BC), funnelSumm + funnel);
            context.lineTo(Math.round(marginWidth / 2 - BC) + options.marginX, funnelSumm + funnel);
            context.closePath();
            context.stroke();
            context.fill();

            self.renderLabel(
                context,
                options,
                Object.keys(options.data)[iterator],
                (Math.round(options.width / 2)),
                Math.round(funnelSumm + funnel / 2)
            );
        }

        funnelSumm += funnel;

        context.beginPath();
        context.moveTo(Math.ceil(marginWidth / 2 - BC) + options.marginX, funnelSumm);

        self.stack[iterator + 1] = {};
        self.stack[iterator + 1].x1 = Math.ceil(marginWidth / 2 - BC) + options.marginX;
        self.stack[iterator + 1].y1 = funnelSumm;

        return {
            'prevStepWidth': (BC * 2),
            'prevStepWidthDelta': (Math.ceil(marginWidth / 2 - BC)),
            'prevStepHeight': (prevStepHeight - funnel),
            'funnelSumm': funnelSumm
        };
    },

    renderLabel: function(context, options, label, labelDistX, distY) {
        var distX = 0,
            style = {
                size : options.fontSize * 1.2,
                color : options.fontColor,
                weight : 1.5
            };
        options.htmlText = true;
        style.textAlign = distX < 0 ? 'right' : 'left';
        style.textBaseline = 'bottom';
        Flotr.drawText(context, label + ': ' + options.data[label], distX, distY, style);

        // label line
        context.beginPath();
        context.lineWidth = 1;
        context.strokeStyle = options.fontColor;
        context.moveTo(distX, distY);
        context.lineTo(labelDistX, distY);
        context.stroke();
    }
});
