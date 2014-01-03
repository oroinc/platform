Flotr.addType('funnel', {
    options: {
        show: false,
        lineWidth: 2,
        fill: true,
        fillColor: null,
        fillOpacity: 0.5,
        fontColor: "#B2B2B2",
        explode: 2,
        extraHeight: true,
        marginX: 50,
        marginY: 50,
        colors: ['#ACD39C', '#BE9DE2', '#6598DA', '#ECC87E', '#A4A2F6', '#6487BF', '#65BC87', '#8985C2', '#ECB574', '#84A377']
    },

    draw: function (options) {
        var context = options.context;

        context.save();
        context.lineJoin = 'miter';
        context.lineWidth = options.lineWidth;
        this.plot(options);

        context.restore();
    },

    plot: function (options) {
        var marginHeight = options.marginY ? options.height - options.marginY * 2 : options.height;
        var marginWidth = options.marginX ? options.width - options.marginX : options.width;
        var
            extraHeight = options.extraHeight ? marginHeight/4 : 0,
            data = options.data,
            context = options.context,
            summ = 0,
            labels = Object.keys(options.data);

        Flotr._.each(data, function (funnel) {
            summ += funnel;
        });

        var funnel_data = new Array();
        Flotr._.each(data, function (funnel) {
            funnel_data.push(Math.ceil(funnel * marginHeight / summ));
        });
        context.lineJoin = 'round';
        context.translate(0.5, 0.5);
        context.beginPath();
        context.moveTo(options.marginX, options.marginY);

        var prevStepWidth = marginWidth;
        var prevStepHeight = marginHeight + extraHeight;
        var funnelSumm = options.marginY;
        var prevStepWidthDelta = 0;

        Flotr._.each(funnel_data, function (funnel, iterator) {
            context.lineWidth = options.lineWidth;
            context.strokeStyle = options.colors[iterator];
            context.fillStyle = Flotr.Color.parse(options.colors[iterator]).alpha(options.fillOpacity).toString() ;
            var
                AD = Math.ceil(prevStepWidth / 2),
                AP = prevStepHeight,
                funnelExplode = funnel - options. explode,
                BC = Math.ceil((AD * (AP - funnelExplode)) / AP);

            context.lineTo(prevStepWidth + prevStepWidthDelta, funnelSumm);

            context.lineTo(Math.round(marginWidth / 2 + BC), funnelSumm + funnelExplode);
            context.lineTo(Math.round(marginWidth / 2 - BC) + options.marginX, funnelSumm + funnelExplode);

            context.closePath();
            context.stroke();
            context.fill();

            // label
            var distX = 10,
                distY = Math.round(funnelSumm + funnelExplode / 2);
            style = {
                size : options.fontSize * 1.2,
                color : options.fontColor,
                weight : 1.5
            };
            options.htmlText = true;
            var textAlign     = distX < 0 ? 'right' : 'left',
                textBaseline  = distY > 0 ? 'top' : 'bottom';
            style.textAlign = textAlign;
            style.textBaseline = textBaseline;
            Flotr.drawText(context, labels[iterator] + ': ' + data[labels[iterator]], distX, distY - style.size - 5, style);
            // label line
            context.beginPath();
            context.lineWidth = 1;
            context.strokeStyle = options.fontColor;
            context.moveTo(distX, distY);
            context.lineTo(Math.round(marginWidth / 2 - BC) + options.marginX, distY);
            context.stroke();
            //end of label

            funnelSumm += funnel + options.explode;
            context.beginPath();
            context.moveTo(Math.ceil(marginWidth / 2 - BC) + options.marginX, funnelSumm);
            prevStepWidth = BC * 2;
            prevStepWidthDelta = Math.ceil(marginWidth / 2 - BC);
            prevStepHeight = AP - funnel - options.explode;
        });
    }
});
