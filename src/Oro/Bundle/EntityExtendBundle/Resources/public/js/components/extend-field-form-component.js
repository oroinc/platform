define(function(require) {
    'use strict';

    const $ = require('jquery');
    const BaseComponent = require('oroui/js/app/components/base/component');

    const ExtendFieldFormComponent = BaseComponent.extend({
        /**
         * @inheritdoc
         */
        constructor: function ExtendFieldFormComponent(options) {
            ExtendFieldFormComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            $('form select#' + options.typeId).on('change', function() {
                const selectedOption = $(this).find('option:selected');
                const relationFieldName = selectedOption.data('fieldname');
                const $fieldName = $('input#' + options.fieldId);

                if (relationFieldName) {
                    $fieldName.val(relationFieldName).prop('readonly', true);
                } else if ($fieldName.prop('readonly')) {
                    $fieldName.val('').prop('readonly', false);
                }
            });
        }
    });

    return ExtendFieldFormComponent;
});
