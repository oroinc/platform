define([
    'jquery',
    'underscore',
    'orotranslation/js/translator',
    'oroui/js/mediator',
    'oroui/js/messenger',
    'oroui/js/app/views/base/view'
], function($, _, __, mediator, messenger, BaseView) {
    'use strict';

    var ChangeStatusView = BaseView.extend({
        /**
         * @constructor
         */
        initialize: function() {
            this.$el.on('click.' + this.cid, _.bind(function(e) {
                e.preventDefault();
                this.sendUpdate();
                return false;
            }, this));
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (!this.disposed && this.$el) {
                this.$el.off('.' + this.cid);
            }
            ChangeStatusView.__super__.dispose.call(this);
        },

        sendUpdate: function() {
            $.ajax({
                url: this.$el.attr('href'),
                type: 'GET',
                success: function() {
                    mediator.execute('refreshPage');
                },
                error: function(jqXHR) {
                    messenger.showErrorMessage(__('Sorry, unexpected error was occurred'), jqXHR.responseJSON);
                }
            });
        }
    });

    return ChangeStatusView;
});
