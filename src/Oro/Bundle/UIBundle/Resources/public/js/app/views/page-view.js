/*global define*/
define([
    './base/view'
], function (BaseView) {
    'use strict';

    var PageView;

    PageView = BaseView.extend({
        listen: {
            'page:beforeChange mediator': 'removeErrorClass',
            'page:error mediator': 'addErrorClass'
        },

        removeErrorClass: function () {
            this.$el.removeClass('error-page');
        },

        addErrorClass: function () {
            this.$el.addClass('error-page');
        }
    });

    return PageView;
});
