/*global define*/
define(['underscore', 'orotranslation/js/translator', 'jquery'
], function(_, __, $) {
    'use strict';

    var defaultParam = {
        message: 'oro.query_designer.condition_builder.filters.not_blank'
    };

    return [
        'Oro\\Bundle\\QueryDesignerBundle\\Validator\\NotBlankFilters',
        function(value, element) {
            var $conditionBuilder = $(element)
                .closest('[data-role="query-designer-container"]')
                .find('.ui-widget-oroquerydesigner-condition-builder');

            if ($conditionBuilder.length === 0) {
                return true;
            }

            var filters = $conditionBuilder.conditionBuilder('getValue');

            return filters && _.isArray(filters) && filters.length > 0;
        },
        function(param) {
            param = _.extend({}, defaultParam, param);

            return __(param.message, {});
        }
    ];
});
