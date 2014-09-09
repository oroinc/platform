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
                    mediator.off('page:afterRefresh', refreshHandler);
                }
            };
            if (!event.isCurrentPage && mediator.execute('compareUrl', url, event.path)) {
                $noteEl = $el.find('.pin-status');
                if (!$noteEl.is('.outdated')) {
                    this.markOutdated($noteEl);
                    mediator.on('page:afterRefresh', refreshHandler);
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
