define(function(require) {
    'use strict';

    var DraggableSortingView;
    var $ = require('jquery');
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');
    require('jquery-ui');

    DraggableSortingView = BaseView.extend({
        /**
         * @inheritDoc
         */
        constructor: function DraggableSortingView() {
            DraggableSortingView.__super__.constructor.apply(this, arguments);
        },

        render: function() {
            this.initSortable();
            this.reindexValues();
            return this;
        },

        reindexValues: function() {
            var index = 1;
            this.$('[name$="[_position]"]').each(function() {
                $(this).val(index++);
            });
        },

        initSortable: function() {
            this.$('.sortable-wrapper').sortable({
                tolerance: 'pointer',
                delay: 100,
                containment: 'parent',
                stop: _.bind(this.reindexValues, this)
            });
        }
    });

    return DraggableSortingView;
});
