/*jslint nomen:true*/
/*global define*/
define([
    'underscore',
    'chaplin',
    './view',
    'oroui/js/app/views/loading-mask-view',
], function (_, Chaplin, View, LoadingMaskView) {
    'use strict';

    var BaseCollectionView;

    BaseCollectionView = Chaplin.CollectionView.extend({
        /**
         * Selector of the element that should be covered with loading mask
         *
         * @property {null|string|jQuery}
         * @default null
         */
        loadingContainerSelector: null,

        /**
         * Show loader indicator on sync action even for not empty collections
         *
         * @property {boolean}
         * @default true
         */
        showLoadingForce: true,

        initialize: function (options) {
            _.extend(this, _.pick(options, ['fallbackSelector', 'loadingSelector', 'loadingContainerSelector',
                'itemSelector', 'listSelector']));
            BaseCollectionView.__super__.initialize.apply(this, arguments);
        },

        // This class doesn’t inherit from the application-specific View class,
        // so we need to borrow the method from the View prototype:
        getTemplateFunction: View.prototype.getTemplateFunction,
        _ensureElement: View.prototype._ensureElement,
        _findRegionElem: View.prototype._findRegionElem,

        /**
         * Fetches model related view
         *
         * @param {Chaplin.Model} model
         * @returns {Chaplin.View}
         */
        getItemView: function (model) {
            return this.subview("itemView:" + model.cid);
        },

        /**
         * Initializes loading indicator
         *
         *  - added support loadingMask subview
         *
         * @returns {jQuery}
         * @override
         */
        initLoadingIndicator: function() {
            var loading;
            if (this.loadingContainerSelector) {
                loading = new LoadingMaskView({
                    autoRender: true,
                    container: this.$(this.loadingContainerSelector).first()
                });
                this.subview('loading', loading);
                this.loadingSelector = loading.$el;
            }
            return BaseCollectionView.__super__.initLoadingIndicator.apply(this, arguments);
        },


        /**
         * Toggles loading indicator
         *
         *  - added extra flag showLoadingForce that shows loader event for not empty collections
         *  - added support loadingMask subview
         *
         * @returns {jQuery}
         * @override
         */
        toggleLoadingIndicator: function () {
            var visible;

            visible = (this.collection.length === 0 || this.showLoadingForce) && this.collection.isSyncing();
            if (this.subview('loading')) {
                this.subview('loading').toggle(visible);
            } else {
                this.$loading.toggle(visible);
            }

            return this.$loading;
        }
    });

    return BaseCollectionView;
});
