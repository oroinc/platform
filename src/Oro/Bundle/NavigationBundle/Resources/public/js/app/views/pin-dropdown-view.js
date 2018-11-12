define(function(require) {
    'use strict';

    var DropdownCollectionView;
    var _ = require('underscore');
    var Chaplin = require('chaplin');
    var BaseCollectionView = require('oroui/js/app/views/base/collection-view');
    var utils = Chaplin.utils;

    DropdownCollectionView = BaseCollectionView.extend({
        listen: {
            'visibilityChange': 'updateVisibility',
            'add collection': 'updateDropdown',
            'remove collection': 'updateDropdown',
            'page:afterChange mediator': 'updateDropdown',
            'layout:reposition mediator': 'updateDropdown'
        },

        /**
         * @inheritDoc
         */
        constructor: function DropdownCollectionView() {
            DropdownCollectionView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            _.extend(this, _.pick(options, ['position']));
            DropdownCollectionView.__super__.initialize.apply(this, arguments);
            // handle resize event once per frame (1000 ms / 25 frames)
            this.updateDropdown = _.debounce(this.updateDropdown.bind(this), 40);
        },


        render: function() {
            DropdownCollectionView.__super__.render.call(this);
            this.updateDropdown();
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
