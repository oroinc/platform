/*global define*/
define([
    'oroui/js/loading-mask'
], function (LoadingMaskView) {
    'use strict';

    var PageLoadingMaskView;

    PageLoadingMaskView = LoadingMaskView.extend({
        listen: {
            'page:beforeChange mediator': 'show',
            'page:afterChange mediator': 'hide'
        }
    });

    return PageLoadingMaskView;
});
