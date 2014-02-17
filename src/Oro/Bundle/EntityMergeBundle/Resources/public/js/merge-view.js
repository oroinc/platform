define(['jquery'],

    /**
     * @param {jquery} $
     * @return mergeView
     */
    function ($) {
    /**
     * @typedef mergeView
     * @function entitySelectAllHandler
     * @function entityValueSelectHandler
     * @function resetViewState
     * @function init
     * @export oro/entity-merge/merge-view
     * @type {object}
     */
    /**
     * @type mergeView
     */
    return {
        /**
         * @desc This callback change entity field values class in one of the form column
         * @desc All field values in the column set to active
         * @callback
         * @desc {HTMLElement} this
         */
        entitySelectAllHandler: function () {
            var entityId = $(this).data('entity-key');
            $('.entity-merge-field-choice[value="' + entityId + '"]').click();
        },

        /**
         * @desc This callback change entity field values class in one of the form rows
         * @desc All other then "target" value will be lighter
         * @callback
         * @desc {HTMLElement} this
         */
        entityValueSelectHandler: function () {
            var $this = $(this);
            var fieldName = $this.attr('name');
            var entityKey = $this.val();
            $('.merge-entity-representative[data-entity-field-name="' + fieldName + '"]').each(function (index, item) {
                var $this = $(item);
                if ($this.data('entity-key') != entityKey) {
                    $this.addClass('entity-merge-not-selected');
                } else {
                    $this.removeClass('entity-merge-not-selected');
                }
            });
        },

        /**
         * @desc reset entity values class states
         * @desc All selected classes will be darker then not selected
         */
        resetViewState: function () {
            $('input[type="radio"]:checked').click();
        },
        /**
         * @constructs
         */
        init: function () {
            $('.entity-merge-select-all').click(this.entitySelectAllHandler);
            $('.entity-merge-field-choice').click(this.entityValueSelectHandler);
            this.resetViewState();
        }
    };
});
