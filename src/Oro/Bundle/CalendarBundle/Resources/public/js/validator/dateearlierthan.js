define([
    'underscore',
    'jquery',
    'orotranslation/js/translator'
], function(_, $, __) {
    'use strict';

    var defaultParam = {
        message: 'This date should be earlier than End date'
    };

    /**
     * @export orocalendar/js/validator/dateearlierthan
     */
    return [
        'Oro\\Bundle\\CalendarBundle\\Validator\\Constraints\\DateEarlierThan',
        function(value, element, options) {
            /**
             * For example if elementId == date_selector_orocrm_campaign_form_startDate and options.field == endDate
             * then comparedElId will be date_selector_orocrm_campaign_form_endDate
             */
            var elementId = $(element).attr('id');
            var strToReplace = elementId.substr(elementId.lastIndexOf('_') + 1);
            var comparedElId = elementId.replace(strToReplace, options.field);
            var comparedValue = $('#' + comparedElId).val();

            if (!value || !comparedValue) {
                return true;
            }

            var firstDate = new Date(value);
            var secondDate = new Date(comparedValue);

            return secondDate >= firstDate;
        },
        function(param, element) {
            var value = String(this.elementValue(element));
            var placeholders = {};
            param = _.extend({}, defaultParam, param);
            placeholders.field = value;
            return __(param.message, placeholders);
        }
    ];
});
