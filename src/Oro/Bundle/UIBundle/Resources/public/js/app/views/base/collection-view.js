define([
    'underscore',
    'chaplin',
    './view',
    'oroui/js/app/views/loading-mask-view',
], function(_, Chaplin, View, LoadingMaskView) {
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

        initialize: function(options) {
            _.extend(this, _.pick(options, ['fallbackSelector', 'loadingSelector', 'loadingContainerSelector',
                'itemSelector', 'listSelector', 'animationDuration']));
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
        getItemView: function(model) {
            return this.subview('itemView:' + model.cid);
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
            var loadingContainer = this._getLoadingContainer();
            if (loadingContainer) {
                var loading = new LoadingMaskView({
                    container: loadingContainer
                });
                this.subview('loading', loading);
                this.loadingSelector = loading.$el;
            }
            return BaseCollectionView.__super__.initLoadingIndicator.apply(this, arguments);
        },

        /**
         * Fetches loading container element
         *
         * @returns {HTMLElement|undefined}
         * @protected
         */
        _getLoadingContainer: function() {
            var loadingContainer;
            if (this.loadingContainerSelector) {
                loadingContainer = this.$(this.loadingContainerSelector).get(0);
            }
            return loadingContainer;
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
        toggleLoadingIndicator: function() {
            var visible;

            visible = (this.collection.length === 0 || this.showLoadingForce) && this.collection.isSyncing();
            if (this.subview('loading')) {
                this.subview('loading').toggle(visible);
            } else {
                this.$loading.toggle(visible);
            }

            return this.$loading;
        },

        /**
         * Removes all elements that do not match current models from DOM
         */
        cleanup: function() {
            var $list = this.listSelector ? this.$(this.listSelector) : this.$el;
            if ($list.length === 0) {
                throw new Error('could not find list DOM element');
            }
            var list = $list[0];
            var validChildren = _.map(this.getItemViews(), function(view) {
                return view.el;
            });
            var toRemove = _.difference(list.children, validChildren);
            for (var i = 0; i < toRemove.length; i++) {
                var child = toRemove[i];
                list.removeChild(child);
            }
        }
    });

    return BaseCollectionView;
});
