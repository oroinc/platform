define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const BaseView = require('oroui/js/app/views/base/view');
    require('jquery-ui/widgets/sortable');

    const DraggableSortingView = BaseView.extend({
        /**
         * @inheritdoc
         */
        constructor: function DraggableSortingView(options) {
            DraggableSortingView.__super__.constructor.call(this, options);
        },

        render: function() {
            this.initSortable();
            this.reindexValues();
            return this;
        },

        reindexValues: function() {
            let index = 1;
            this.$('[name$="[_position]"]').each(function() {
                $(this).val(index++);
            });
        },

        initSortable: function() {
            this.$('.sortable-wrapper').sortable({
                tolerance: 'pointer',
                delay: 100,
                containment: 'parent',
                handle: '[data-name="sortable-handle"]',
                stop: _.bind(this.reindexValues, this)
            });
        }
    });

    return DraggableSortingView;
});
