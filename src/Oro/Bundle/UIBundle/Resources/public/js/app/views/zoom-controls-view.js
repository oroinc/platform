// scale(0.8,0.8) translate(10px,20px)

define(function(require) {
    'use strict';

    var ZoomControlsView;
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');

    ZoomControlsView = BaseView.extend({
        autoRender: true,

        events: {
            'click .nav-tabs a': 'onTabSwitch'
        },
    });

    return ZoomControlsView;
});
