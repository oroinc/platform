/*jslint nomen:true*/
/*global define*/
define([
    'jquery',
    'backbone',
    'underscore',
    'orotranslation/js/translator',
    'oroui/js/mediator',
    'oroui/js/delete-confirmation'
], function ($, Backbone, _, __, mediator, DeleteConfirmation) {
    'use strict';

    /**
     * @extends Backbone.View
     */
    return Backbone.View.extend({
        /**
         * @const
         */
        RELOAD_MARKER: '_reloadForm',

        events: {
            'change [name*="processorType"]': 'changeHandler'
        },

        /**
         * @param options Object
         */
        initialize: function (options) {
            this.options = _.defaults(options || {}, this.options);
        },

        changeHandler: function (event) {
            var data = this.$el.serializeArray();
            var url = this.$el.attr('action');
            var method = this.$el.attr('method');

            data.push({name: this.RELOAD_MARKER, value: true});
            mediator.execute('submitPage', {url: url, type: method, data: $.param(data)});
        }
    });
});
