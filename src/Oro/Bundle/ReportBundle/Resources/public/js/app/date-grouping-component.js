require([
    'jquery',
    'oroui/js/mediator',
    'oroui/js/input-widget-manager'
], function($, mediator) {
    'use strict';

    $(function() {
        function handleDateGroupVisibility() {
            var $enableCheckbox = $('input[name="oro_report_form[dateGrouping][useDateGroupFilter]"]');
            var $emptyPeriodsCheckbox = $('input[name="oro_report_form[dateGrouping][useSkipEmptyPeriodsFilter]"]');
            var $groupFieldInput = $('input[name="oro_report_form[dateGrouping][fieldName]"]');

            function showHideInputs($enableCheckbox) {
                if ($enableCheckbox.is(':checked')) {
                    $emptyPeriodsCheckbox.removeAttr('disabled');
                    $groupFieldInput.inputWidget('disable', true);
                } else {
                    $emptyPeriodsCheckbox.attr('disabled', true);
                    $groupFieldInput.inputWidget('disable', false);
                }
            }

            showHideInputs($enableCheckbox);
            $enableCheckbox.on('change', function() {
                showHideInputs($(this));
            });
        }

        handleDateGroupVisibility();
        mediator.on('page:afterChange', function() {
            handleDateGroupVisibility();
        });
    });
});
