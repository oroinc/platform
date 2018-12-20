define(function(require) {
    'use strict';

    /**
     * Displays header of board column
     * @augments BaseView
     */
    var ColumnHeaderView;
    var BaseView = require('oroui/js/app/views/base/view');

    ColumnHeaderView = BaseView.extend({
        /**
         * @inheritDoc
         */
        className: 'board-column-header',

        /**
         * @inheritDoc
         */
        template: require('tpl!../../../../templates/board/column-header-view.html'),

        /**
         * @inheritDoc
         */
        constructor: function ColumnHeaderView() {
            ColumnHeaderView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.boardCollection = options.boardCollection;
            this.listenTo(this.boardCollection, 'add remove reset sort', this.markIfEmpty);
            ColumnHeaderView.__super__.initialize.call(this, options);
            this.markIfEmpty();
        },

        /**
         * Add empty css class to root element if modal is empty
         */
        markIfEmpty: function() {
            this.$el.toggleClass('empty', this.model.get('items').length === 0);
        }
    });

    return ColumnHeaderView;
});
