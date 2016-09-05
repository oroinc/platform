define(function(require) {
    'use strict';

    var FieldsGroupsCollectionView;
    var BaseView = require('oroui/js/app/views/base/view');

    FieldsGroupsCollectionView = BaseView.extend({
        PRIMARY_FILED_SELECTOR: '[name$="[primary]"]',

        events: {
            'click [name$="[primary]"]': 'onPrimaryClick',
            'change >*': 'onChangeInFiledGroup'
        },

        /**
         * Allows only 1 primary checkbox|radiobutton to be checked.
         * This logic convert checkbox logic to logic used in radiobutton
         *
         * @param {jQuery.Event} e
         */
        onPrimaryClick: function(e) {
            this.$(this.PRIMARY_FILED_SELECTOR).each(function() {
                this.checked = false;
            });
            e.target.checked = true;
        },

        /**
         * Handles changes in a group of fields and marks this group as primary
         * if there's no other primary group in collection
         *
         * @param {jQuery.Event} e
         */
        onChangeInFiledGroup: function(e) {
            var $fieldsGroup = this.$(e.currentTarget);
            if (!this.$(e.target).is(this.PRIMARY_FILED_SELECTOR) &&
                !this.$(this.PRIMARY_FILED_SELECTOR + ':checked').length) {
                $fieldsGroup.find(this.PRIMARY_FILED_SELECTOR).prop('checked', true);
            }
        }
    });

    return FieldsGroupsCollectionView;
});
