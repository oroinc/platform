/*jslint nomen:true*/
/*global define*/
define([
    'underscore',
    'chaplin',
    './view'
], function (_, Chaplin, View) {
    'use strict';

    var BaseCollectionView;

    BaseCollectionView = Chaplin.CollectionView.extend({
        /**
         * Show loader indicator on sync action even for not empty collections
         *
         * @property {boolean}
         * @default true
         */
        showLoadingForce: true,

        initialize: function (options) {
            _.extend(this, _.pick(options, ['fallbackSelector', 'loadingSelector', 'itemSelector', 'listSelector']));
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
         * Toggles loading indicator
         *
         *  - added extra flag showLoadingForce that shows loader event for not empty collections
         *
         * @returns {jQuery}
         * @override
         */
        toggleLoadingIndicator: function () {
            var visible;
            visible = (this.collection.length === 0 || this.showLoadingForce) && this.collection.isSyncing();
            return this.$loading.toggle(visible);
        }
    });

    return BaseCollectionView;
});
