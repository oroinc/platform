define(function(require) {
    'use strict';
    const $ = require('jquery');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const MultiUseResourceManager = require('./multi-use-resource-manager');

    const UnloadMessagesGroup = MultiUseResourceManager.extend({
        listen: {
            constructResource: function() {
                $(window).on('beforeunload', this.onBeforeUnload);
            },
            disposeResource: function() {
                $(window).off('beforeunload', this.onBeforeUnload);
            }
        },

        single: __('oro.ui.unload_message.single'),
        group_title: __('oro.ui.unload_message.group_title'),

        /**
         * @inheritdoc
         */
        constructor: function UnloadMessagesGroup(options) {
            if (options.single) {
                this.single = options.single;
            }
            if (options.group_title) {
                this.group_title = options.group_title;
            }
            this.onBeforeUnload = this.onBeforeUnload.bind(this);
            UnloadMessagesGroup.__super__.constructor.call(this, options);
        },

        /**
         * Window unload handler
         *
         * @returns {string|undefined}
         */
        onBeforeUnload: function() {
            const subMessages = _.countBy(this.holders, function(item) {
                if (_.isString(item)) {
                    return item;
                }
                return '';
            });
            const emptyDescriptionMessagesCount = subMessages[''] ? subMessages[''].length : 0;
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
