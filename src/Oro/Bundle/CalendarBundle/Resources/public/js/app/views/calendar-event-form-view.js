/*jslint nomen: true*/
/*global define*/
define([
    'jquery',
    'oroui/js/mediator',
    'oroui/js/app/views/base/view',
    'oroform/js/app/views/datepair-view'
], function ($, mediator, BaseView, DatepairView) {
    'use strict';

    var CalendarEventFormView;
    CalendarEventFormView = BaseView.extend({

        /**
         * Options
         */
        options: {},

        /**
         * @constructor
         *
         * @param {Object} options
         */
        initialize: function (options) {
            this.options = options || {};
            this.render();
        },

        render: function () {
            var self = this;
            mediator.execute('layout:init', this.$el, this).done(function () {
                self.handleLayoutInit();
            });
        },

        handleLayoutInit: function () {
            var opts, datepair;
            opts = this.options;
            opts.container = this.$el;
            datepair = new DatepairView(opts);
            this.subview('datepair', datepair);
        }
    });

    return CalendarEventFormView;
});
