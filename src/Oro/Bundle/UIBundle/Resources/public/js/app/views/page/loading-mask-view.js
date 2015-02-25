/*jslint nomen:true*/
/*global define*/
define([
    'jquery',
    '../loading-mask-view'
], function ($, LoadingMaskView) {
    'use strict';

    var PageLoadingMaskView;

    PageLoadingMaskView = LoadingMaskView.extend({
        listen: {
            'page:beforeChange mediator': 'show',
            'page:afterChange mediator': 'hide'
        },

        show: function () {
            $.isActive(true);
            PageLoadingMaskView.__super__.show.call(this);
        },

        hide: function () {
            PageLoadingMaskView.__super__.hide.call(this);
            $.isActive(false);
        },

        attach: function () {
            PageLoadingMaskView.__super__.attach.apply(this, arguments);
            // shows loader once it is attached to the page
            this.show();
        }
    });

    return PageLoadingMaskView;
});
