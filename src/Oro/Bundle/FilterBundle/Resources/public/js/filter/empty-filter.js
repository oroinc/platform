/*global define*/
define(['jquery', 'underscore', 'oroui/js/tools', './abstract-filter'
], function ($, _, tools, AbstractFilter) {
    'use strict';

    /**
     * @export  orofilter/js/filter/empty-filter
     * @class   orofilter.filter.EmptyFilter
     * @extends orofilter.filter.AbstractFilter
     */
    return AbstractFilter.extend({

        /**
         * Template selector for filter criteria
         *
         * @property
         */
        emptyOption: 'empty',

        /**
         * Stores old value for empty filter
         *
         * @property
         */
        query: null,

        /**
         * Marks value to revert
         *
         * @property
         */
        revertQuery: false,

        /**
         * @property
         */
        updateSelector: '.filter-update',

        /**
         * @property
         */
        updateSelectorEmptyClass: 'filter-update-empty',

        /**
         * Set raw value to filter
         *
         * @param value
         * @return {*}
         */
        setValue: function (value) {
            var oldValue = this.value;
            this.value = tools.deepClone(value);
            this._updateDOMValue();
            this._onValueUpdated(this.value, oldValue);

            return this;
        },

        /**
         * Open/close select dropdown
         *
         * @param {Event} e
         * @protected
         */
        _onClickChoiceValue: function (e) {
            $(e.currentTarget).parent().parent().find('li').each(function () {
                $(this).removeClass('active');
            });
            $(e.currentTarget).parent().addClass('active');

            var parentDiv = $(e.currentTarget).parent().parent().parent();
            var type = $(e.currentTarget).attr('data-value');
            var choiceName = $(e.currentTarget).html();

            parentDiv.find(this.criteriaValueSelectors.type).val(type).trigger('change');
            choiceName += '<span class="caret"></span>';
            parentDiv.find('.dropdown-toggle').html(choiceName);

            this._handleEmptyFilter(type);

            e.preventDefault();
        },

        /**
         * Handle click on criteria selector
         *
         * @param {Event} e
         * @protected
         */
        _onClickCriteriaSelector: function (e) {
            e.stopPropagation();
            $('body').trigger('click');
            if (!this.popupCriteriaShowed) {
                this._showCriteria();
            } else {
                this._hideCriteria();
            }

            this._handleEmptyFilter();
        },

        /**
         * Handle empty filter selection
         *
         * @protected
         */
        _handleEmptyFilter: function () {
            var container = this.$(this.criteriaSelector);
            var item = container.find(this.criteriaValueSelectors.value);
            var type = container.find(this.criteriaValueSelectors.type).val();
            var button = container.find(this.updateSelector);

            if (type == this.emptyOption) {
                var query = item.val();
                if (query != this.emptyOption) {
                    this.query = query;
                    this.revertQuery = true;
                }

                item.hide().val(this.emptyOption);
                button.addClass(this.updateSelectorEmptyClass);

                return;
            }

            if (this.revertQuery) {
                item.val(this.query);

                this.query = null;
                this.revertQuery = false;
            }

            button.removeClass(this.updateSelectorEmptyClass);
            item.show();
        }
    });
});
