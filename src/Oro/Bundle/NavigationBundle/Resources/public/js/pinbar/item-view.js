/* jshint browser:true */
/*global define*/
define(['jquery', 'underscore', 'orotranslation/js/translator', 'backbone',
        'oroui/js/app', '../navigation', 'oroui/js/mediator', 'oroui/js/error'
    ], function ($, _, __, Backbone, app, Navigation, mediator, error) {
    'use strict';

    /**
     * @export  oronavigation/js/pinbar/item-view
     * @class   oronavigation.pinbar.ItemView
     * @extends Backbone.View
     */
    return Backbone.View.extend({

        options: {
            type: 'list'
        },

        tagName:  'li',

        isRemoved: false,

        templates: {
            list: _.template($("#template-list-pin-item").html()),
            tab: _.template($("#template-tab-pin-item").html())
        },

        events: {
            'click .btn-close': 'unpin',
            'click .close': 'unpin',
            'click .pin-holder div a': 'maximize',
            'click span': 'maximize'
        },

        initialize: function() {
            this.listenTo(this.model, 'destroy', this.removeItem);
            this.listenTo(this.model, 'change:display_type', this.removeItem);
            this.listenTo(this.model, 'change:remove', this.unpin);
            /**
             * Change active pinbar item after hash navigation request is completed
             */
            mediator.bind(
                "hash_navigation_request:complete",
                function() {
                    /*if (!this.isRemoved && this.checkCurrentUrl()) {
                        this.maximize();
                    }*/
                    this.setActiveItem();
                },
                this
            );
        },

        unpin: function() {
            mediator.trigger("pinbar_item_remove_before", this.model);
            this.model.destroy({
                wait: true,
                error: _.bind(function(model, xhr) {
                    if (xhr.status == 404 && !app.debug) {
                        // Suppress error if it's 404 response and not debug mode
                        this.removeItem();
                    } else {
                        error.handle({}, xhr, {enforce: true});
                    }
                }, this)
            });
            return false;
        },

        maximize: function() {
            this.model.set('maximized', new Date().toISOString());
            return false;
        },

        removeItem: function() {
            mediator.off('content-manager:content-outdated', this.outdatedContentHandler, this);
            this.isRemoved = true;
            this.remove();
        },

        checkCurrentUrl: function() {
            var url = '',
                modelUrl = this.model.get('url'),
                navigation = Navigation.getInstance();
            if (navigation) {
                url = navigation.getHashUrl();
                url = navigation.removeGridParams(url);
                modelUrl = navigation.removeGridParams(modelUrl);
            } else {
                url = window.location.pathname;
            }
            return this.cleanupUrl(modelUrl) == this.cleanupUrl(url);
        },

        cleanupUrl: function(url) {
            if (url) {
                url = url.replace(/(\?|&)restore=1/ig, '');
            }
            return url;
        },

        setActiveItem: function() {
            if (this.checkCurrentUrl()) {
                this.$el.addClass('active');
            } else {
                this.$el.removeClass('active');
            }
        },

        render: function () {
            this.$el.html(
                this.templates[this.options.type](this.model.toJSON())
            );

            // if cache used highlight tab on content outdated event
            mediator.on('content-manager:content-outdated', this.outdatedContentHandler, this);
            this.setActiveItem();
            return this;
        },

        outdatedContentHandler: function (event) {
            var navigation = Navigation.getInstance(),
                modelUrl = navigation.removeGridParams(this.model.get('url')) ,
                $el = this.$el,
                self = this,
                refreshHandler = function (obj) {
                    if (modelUrl === obj.url) {
                        $noteEl = $el.find('.pin-status.outdated');
                        self.markNormal($noteEl);

                        mediator.off('hash_navigation_request:page_refreshed', refreshHandler);
                    }
                };

            if (!event.isCurrentPage && modelUrl == event.url) {
                var $noteEl = $el.find('.pin-status');
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
});
