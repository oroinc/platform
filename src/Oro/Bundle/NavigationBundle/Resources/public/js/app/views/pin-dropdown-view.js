define(function(require) {
    'use strict';

    const _ = require('underscore');
    const $ = require('jquery');
    const Chaplin = require('chaplin');
    const BaseCollectionView = require('oroui/js/app/views/base/collection-view');
    const utils = Chaplin.utils;

    const DropdownCollectionView = BaseCollectionView.extend({
        listen: {
            'visibilityChange': 'updateVisibility',
            'add collection': 'updateDropdown',
            'remove collection': 'updateDropdown',
            'page:afterChange mediator': 'updateDropdown',
            'layout:reposition mediator': 'updateDropdown'
        },

        events: {
            'shown.bs.dropdown': 'updateDropdownMaxHeight',
            'hidden.bs.dropdown': 'updateDropdownMaxHeight'
        },

        /**
         * @inheritdoc
         */
        constructor: function DropdownCollectionView(options) {
            DropdownCollectionView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            _.extend(this, _.pick(options, ['position']));
            DropdownCollectionView.__super__.initialize.call(this, options);
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
            this.updateDropdownMaxHeight();
        },

        updateDropdownMaxHeight: function() {
            const $list = this.$(this.listSelector);

            if ($list.is(':visible')) {
                $list.css('max-height', $(window).height() - $list.offset().top);
            } else {
                $list.css('max-height', '');
            }
        },

        /**
         * Updates position of root element
         */
        positionUpdate: function() {
            const pos = _.result(this, 'position');
            if (pos) {
                this.$el.css('left', pos.left);
            }
        },

        /**
         * Runs filterer and filterCallback methods for each model and its view
         */
        recheckItems: function() {
            let visibilityChanged;

            this.collection.each(function(model, index) {
                const view = this.subview('itemView:' + model.cid);
                const included = this.filterer(model, index);
                this.filterCallback(view, included);

                const visibleItemsIndex = utils.indexOf(this.visibleItems, model);
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
            DropdownCollectionView.__super__.renderAllItems.call(this);
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
