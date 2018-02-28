define(function(require) {
    'use strict';

    var MergeView;
    var BaseView = require('oroui/js/app/views/base/view');
    var $ = require('jquery');

    /**
     * @typedef MergeView
     * @export oroentitymerge/js/merge-view
     */
    MergeView = BaseView.extend({
        events: {
            'click .entity-merge-select-all': 'onEntitySelectAll',
            'click .entity-merge-field-choice': 'onEntityValueSelect',
            'click .entity-merge-decision-container': 'onColumnClick'
        },

        listen: {
            'layout:reposition mediator': 'onFixTableWidth'
        },

        /**
         * @inheritDoc
         */
        constructor: function MergeView() {
            MergeView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function() {
            this.resetViewState();

            MergeView.__super__.initialize.apply(this, arguments);
        },

        onFixTableWidth: function(event) {
            var columns = this.$('.entity-merge-column');
            var master = this.$('.merge-first-column');
            var firstColumnWidth = parseInt(master.css('width'));
            var tableWidth = parseInt(this.$('.entity-merge-table').css('width'));
            var columnWidth = ((tableWidth - firstColumnWidth) / columns.length);

            columns.css('width', columnWidth);
        },

        /**
         * @desc This callback change entity field values class in one of the form column
         * @desc All field values in the column set to active
         */
        onEntitySelectAll: function(event) {
            var entityId = $(event.currentTarget).data('entity-key');
            this.$('.entity-merge-field-choice[value="' + entityId + '"]').click();
        },

        /**
         * @desc This callback change entity field values class in one of the form rows
         * @desc All other then "target" value will be lighter
         */
        onEntityValueSelect: function(event) {
            event.stopImmediatePropagation();
            var $currentTarget = $(event.currentTarget);
            var fieldName = $currentTarget.attr('name');
            var entityKey = parseInt($currentTarget.val());
            var mergeSelector = '.merge-entity-representative[data-entity-field-name="' + fieldName + '"]';

            this.$(mergeSelector).each(function(index, item) {
                var $item = $(item);
                if ($item.data('entity-key') !== entityKey) {
                    $item.addClass('entity-merge-not-selected');
                } else {
                    $item.removeClass('entity-merge-not-selected');
                }
            });
        },

        /**
         * @desc select radio button if column clicked
         */
        onColumnClick: function(event) {
            $(event.currentTarget).find('.entity-merge-field-choice').click();
        },

        /**
         * @desc reset entity values class states
         * @desc All selected classes will have larger weight then not selected
         */
        resetViewState: function() {
            this.$('input[type="radio"]:checked').click();
        }
    });

    return MergeView;
});
