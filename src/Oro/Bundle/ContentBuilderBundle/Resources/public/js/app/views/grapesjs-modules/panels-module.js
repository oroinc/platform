define(function() {
    'use strict';

    var PanelManagerModule = function(options) {
        _.extend(this, _.pick(options, ['builder']));

        this.init();
    };

    PanelManagerModule.prototype = {
        builder: null,

        init: function() {
            var $ = this.builder.$;

            var Panels = this.builder.Panels;

            var openTmBtn = Panels.getButton('views', 'open-tm');
            openTmBtn && openTmBtn.set('active', 1);
            var openSm = Panels.getButton('views', 'open-sm');
            openSm && openSm.set('active', 1);

            var traitsSector = $('<div class="gjs-sm-sector no-select">'+
                '<div class="gjs-sm-title"><span class="fa fa-cog"></span> Settings</div>' +
                '<div class="gjs-sm-properties" style="display: none;"></div></div>');
            var traitsProps = traitsSector.find('.gjs-sm-properties');
            traitsProps.append($('.gjs-trt-traits'));
            $('.gjs-sm-sectors').before(traitsSector);

            traitsSector.find('.gjs-sm-title').on('click', function() {
                var traitStyle = traitsProps.get(0).style;

                var hidden = traitStyle.display === 'none';
                if (hidden) {
                    traitStyle.display = 'block';
                } else {
                    traitStyle.display = 'none';
                }
            });

            Panels.removeButton('views', 'open-tm');
        }
    };

    return PanelManagerModule;
});

