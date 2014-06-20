/*jslint browser:true, nomen:true*/
/*global define*/
define([
    'jquery',
    'underscore',
    'orotranslation/js/translator',
    'oroui/js/app/views/base/view',
    'oroui/js/mediator'
], function ($, _, __, BaseView, mediator) {
    'use strict';

    var PinItemView;

    PinItemView = BaseView.extend({
        tagName:  'li',

        events: {
            'click .btn-close': 'toRemove',
            'click .close': 'toRemove',
            'click .pin-holder div a': 'toMaximize',
            'click span': 'toMaximize'
        },

        listen: {
            'page:afterChange mediator': 'onPageUpdated'
        },

        /**
         * Change active pinbar item after hash navigation request is completed
         */
        onPageUpdated: function () {
            this.setActiveItem();
        },

        toRemove: function () {
            this.model.collection.trigger('toRemove', this.model);
        },

        toMaximize: function () {
            this.model.collection.trigger('toMaximize', this.model);
        },

        /**
         * Compares current url with model's url
         *
         * @returns {boolean}
         */
        checkCurrentUrl: function () {
            var url;
            url = this.model.get('url');
            return mediator.execute('compareUrl', url);
        },

        setActiveItem: function () {
            this.$el.toggleClass('active', this.checkCurrentUrl());
        },

        remove: function () {
            mediator.off('content-manager:content-outdated', this.outdatedContentHandler, this);
            PinItemView.__super__.remove.call(this);
        },

        render: function () {
            PinItemView.__super__.render.call(this);

            // if cache used highlight tab on content outdated event
            mediator.on('content-manager:content-outdated', this.outdatedContentHandler, this);
            this.setActiveItem();
            return this;
        },

        outdatedContentHandler: function (event) {
            var $el, self, url, refreshHandler, $noteEl;
            self = this;
            $el = this.$el;
            url = this.model.get('url');
            refreshHandler = function () {
                if (self.checkCurrentUrl()) {
                    $noteEl = $el.find('.pin-status.outdated');
                    self.markNormal($noteEl);
                    mediator.off('hash_navigation_request:page_refreshed', refreshHandler);
                }
            };
            if (!event.isCurrentPage && mediator.execute('compareUrl', url, event.path)) {
                $noteEl = $el.find('.pin-status');
                if (!$noteEl.is('.outdated')) {
                    this.markOutdated($noteEl);
                    mediator.on('hash_navigation_request:page_refreshed', refreshHandler);
                }
            }
        },

        markOutdated: function ($el) {
            $el.addClass('outdated').attr('title', __('Content of pinned page is outdated'));
        },

        markNormal: function ($el) {
            $el.removeClass('outdated').removeAttr('title');
        }
    });

    return PinItemView;
});
