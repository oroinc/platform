define(['underscore', 'orotranslation/js/translator', 'jquery'
], function(_, __, $) {
    'use strict';

    const defaultParam = {
        message: 'oro.query_designer.condition_builder.filters.not_empty'
    };

    return [
        'Oro\\Bundle\\QueryDesignerBundle\\Validator\\Constraints\\NotEmptyFilters',
        function(value, element) {
            const data = {result: false};
            $(element).trigger('query-designer:validate:not-empty-filters', data);
            return data.result;
        },
        function(param) {
            param = _.extend({}, defaultParam, param);

            return __(param.message, {});
        }
    ];
});
