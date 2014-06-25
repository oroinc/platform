/*jslint browser:true, nomen:true*/
/*global define*/
define([
    'orotranslation/js/translator',
    'oroui/js/mediator',
    '../base/item-view'
], function (__, mediator, ItemView) {
    'use strict';

    var PinItemView;

    PinItemView = ItemView.extend({
        events: {
            'click a': 'toMaximize'
        },

        toMaximize: function (e) {
            this.model.collection.trigger('toMaximize', this.model);
            e.stopPropagation();
            e.preventDefault();
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
