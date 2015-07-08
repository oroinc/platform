define([
    'jquery',
    'underscore',
    'chaplin',
    'oroui/js/app/views/base/collection-view'
], function($, _, Chaplin, BaseCollectionView) {
    'use strict';

    var DropdownCollectionView;
    var utils = Chaplin.utils;

    DropdownCollectionView = BaseCollectionView.extend({
        listen: {
            'visibilityChange': 'updateVisibility',
            'add collection': 'updateDropdown',
            'remove collection': 'updateDropdown',
            'page-rendered mediator': 'updateDropdown'
        },

        initialize: function(options) {
            _.extend(this, _.pick(options, ['position']));
            DropdownCollectionView.__super__.initialize.apply(this, arguments);
            // handle resize event once per frame (1000 ms / 25 frames)
            $(window).on('resize.' + this.cid, _.debounce(_.bind(this.updateDropdown, this), 40));
        },

        dispose: function() {
            $(window).off('.' + this.cid);
            DropdownCollectionView.__super__.dispose.call(this);
        },

        render: function() {
            DropdownCollectionView.__super__.render.call(this);
            this.positionUpdate();
            return this;
        },

        /**
         * Updates dropdown content and its position
         */
        updateDropdown: function() {
            this.recheckItems();
            this.positionUpdate();
        },

        /**
         * Updates position of root element
         */
        positionUpdate: function() {
            var pos = _.result(this, 'position');
            if (pos) {
                this.$el.css('left', pos.left);
            }
        },

        /**
         * Runs filterer and filterCallback methods for each model and its view
         */
        recheckItems: function() {
            var visibilityChanged;

            this.collection.each(function(model, index) {
                var visibleItemsIndex;
                var view = this.subview('itemView:' + model.cid);
                var included = this.filterer(model, index);
                this.filterCallback(view, included);

                visibleItemsIndex = utils.indexOf(this.visibleItems, model);
                if (included && visibleItemsIndex === -1) {
                    // included -- push model to visible items list
                    this.visibleItems.push(model);
                    visibilityChanged = true;
                } else if (!included && visibleItemsIndex !== -1) {
                    // excluded -- remove model from visible items list
                    this.visibleItems.splice(visibleItemsIndex, 1);
                    visibilityChanged = true;
                }
            }, this);

            if (visibilityChanged) {
                this.trigger('visibilityChange', this.visibleItems);
            }
        },

        /**
         * Update visibility of item-view
         *
         * @param {Chaplin.View} view
         * @param {boolean} included
         */
        filterCallback: function(view, included) {
            view.$el.css('display', included ? '' : 'none');
        },

        renderAllItems: function() {
            DropdownCollectionView.__super__.renderAllItems.apply(this, arguments);
            this.updateVisibility();
        },

        /**
         * Handles visibility change
         */
        updateVisibility: function() {
            this.$el[this.visibleItems.length > 0 ? 'show' : 'hide']();
        }
    });

    return DropdownCollectionView;
});
