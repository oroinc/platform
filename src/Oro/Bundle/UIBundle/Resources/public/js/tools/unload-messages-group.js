define(function(require) {
    'use strict';
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var MultiUseResourceManager = require('./multi-use-resource-manager');
    var UnloadMessagesGroup = MultiUseResourceManager.extend({
        listen: {
            'construct': function() {
                $(window).on('beforeunload', this.onBeforeUnload);
            },
            'dispose': function() {
                $(window).off('beforeunload', this.onBeforeUnload);
            }
        },

        single: __('oro.ui.unload_message.single'),
        group_title: __('oro.ui.unload_message.group_title'),

        /**
         * @inheritDoc
         */
        constructor: function(options) {
            if (options.single) {
                this.single = options.single;
            }
            if (options.group_title) {
                this.group_title = options.group_title;
            }
            this.onBeforeUnload = _.bind(this.onBeforeUnload, this);
            UnloadMessagesGroup.__super__.constructor.call(this, options);
        },

        /**
         * Window unload handler
         *
         * @returns {string|undefined}
         */
        onBeforeUnload: function() {
            var subMessages = _.countBy(this.holders, function(item) {
                if (_.isString(item)) {
                    return item;
                }
                return '';
            });
            var emptyDescriptionMessagesCount = subMessages[''] ? subMessages[''].length : 0;
            if (emptyDescriptionMessagesCount !== this.holders.length) {
                return this.group_title + ':\n' + _.map(subMessages, function(count, message) {
                    return '  - ' + (message !== '' ? message : __('oro.ui.unload_message.other')) +
                        (count > 1 ? (' (' + count + ')') : '');
                }).join(':\n');
            } else {
                return this.single;
            }
        }
    });

    return UnloadMessagesGroup;
});
