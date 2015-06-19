/*jslint nomen: true*/
/*global define*/
define([
    'jquery',
    'underscore',
    'oroui/js/app/views/base/view'
], function ($, _, BaseView) {
    'use strict';

    var Select2View;
    Select2View = BaseView.extend({

        /**
         * Use for jQuery select2 plugin initialization
         */
        select2Config: {},

        autoRender: true,

        /**
         * @constructor
         *
         * @param {Object} options
         */
        initialize: function (options) {
            this.select2Config = _.result(options, 'select2Config') || this.select2Config;
        },

        render: function () {
            this.undelegateEvents();
            this.$el.select2(this.select2Config).trigger('select2-init');
            this.delegateEvents();
        }
    });

    return Select2View;
});
