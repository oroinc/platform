define(function(require) {
    'use strict';

    var ExtendFieldFormComponent;
    var $ = require('jquery');
    var BaseComponent = require('oroui/js/app/components/base/component');

    ExtendFieldFormComponent = BaseComponent.extend({
        initialize: function(options) {
            $('form select#' + options.typeId).on('change', function() {
                var selectedOption = $(this).find('option:selected');
                var relationFieldName = selectedOption.data('fieldname');
                var $fieldName = $('input#' + options.fieldId);

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
