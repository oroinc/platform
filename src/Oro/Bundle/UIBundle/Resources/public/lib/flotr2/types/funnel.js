Flotr.addType('funnel', {
    options: {
        show: false,
        lineWidth: 2,
        fill: true,
        fillColor: null,
        fillOpacity: 0.5,
        fontColor: "#B2B2B2",
        explode: 5,
        extraHeight: 200,
        marginX: 200,
        marginY: 25,
        colors: ['#ACD39C', '#BE9DE2', '#6598DA', '#ECC87E', '#A4A2F6', '#6487BF', '#65BC87', '#8985C2', '#ECB574', '#84A377']
    },

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
                (Math.round(marginWidth / 2 - BC) + options.marginX),
                Math.round(funnelSumm + funnel / 2)
            );
        }

        funnelSumm += funnel;

        context.beginPath();
        context.moveTo(Math.ceil(marginWidth / 2 - BC) + options.marginX, funnelSumm);

        return {
            'prevStepWidth': (BC * 2),
            'prevStepWidthDelta': (Math.ceil(marginWidth / 2 - BC)),
            'prevStepHeight': (prevStepHeight - funnel),
            'funnelSumm': funnelSumm
        };
    },

    renderLabel: function(context, options, label, labelDistX, distY) {
        var distX = 10,
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
