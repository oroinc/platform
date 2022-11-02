define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const $ = require('jquery');

    /**
     * @typedef MergeView
     * @export oroentitymerge/js/merge-view
     */
    const MergeView = BaseView.extend({
        events: {
            'click .entity-merge-select-all': 'onEntitySelectAll',
            'click .entity-merge-field-choice': 'onEntityValueSelect',
            'click .entity-merge-decision-container': 'onColumnClick'
        },

        listen: {
            'layout:reposition mediator': 'onFixTableWidth'
        },

        /**
         * @inheritdoc
         */
        constructor: function MergeView(options) {
            MergeView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.resetViewState();

            MergeView.__super__.initialize.call(this, options);
        },

        onFixTableWidth: function(event) {
            const columns = this.$('.entity-merge-column');
            const master = this.$('.merge-first-column');
            const firstColumnWidth = parseInt(master.css('width'));
            const tableWidth = parseInt(this.$('.entity-merge-table').css('width'));
            const columnWidth = ((tableWidth - firstColumnWidth) / columns.length);

            columns.css('width', columnWidth);
        },

        /**
         * @desc This callback change entity field values class in one of the form column
         * @desc All field values in the column set to active
         */
        onEntitySelectAll: function(event) {
            const entityId = $(event.currentTarget).data('entity-key');
            this.$('.entity-merge-field-choice[value="' + entityId + '"]').click();
        },

        /**
         * @desc This callback change entity field values class in one of the form rows
         * @desc All other then "target" value will be lighter
         */
        onEntityValueSelect: function(event) {
            event.stopImmediatePropagation();
            const $currentTarget = $(event.currentTarget);
            const fieldName = $currentTarget.attr('name');
            const entityKey = parseInt($currentTarget.val());
            const mergeSelector = '.merge-entity-representative[data-entity-field-name="' + fieldName + '"]';

            this.$(mergeSelector).each(function(index, item) {
                const $item = $(item);
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
