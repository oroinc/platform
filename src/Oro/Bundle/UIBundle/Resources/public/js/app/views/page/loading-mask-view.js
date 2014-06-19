/*jslint nomen:true*/
/*global define*/
define([
    'underscore',
    '../base/view',
    '../../../loading-mask'
], function (_, BaseView, LoadingMaskView) {
    'use strict';

    var prototype, PageLoadingMaskView;

    // copy prototype of LoadingMask and extend it with own properties
    prototype = _.extend({}, LoadingMaskView.prototype, {
        listen: {
            'page:beforeChange mediator': 'show',
            'page:afterChange mediator': 'hide'
        }
    });
    delete prototype.constructor;

    // extend new loading mask from BaseView of Chaplin
    PageLoadingMaskView = BaseView.extend(prototype);

    return PageLoadingMaskView;
});
