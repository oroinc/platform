define(['underscore', 'orotranslation/js/translator', 'jquery'
], function(_, __, $) {
    'use strict';

    const defaultParam = {
        message: 'oro.query_designer.condition_builder.filters.not_blank'
    };

    return [
        'Oro\\Bundle\\QueryDesignerBundle\\Validator\\NotBlankFilters',
        function(value, element) {
            const data = {result: false};
            $(element).trigger('query-designer:validate:not-blank-filters', data);
            return data.result;
        },
        function(param) {
            param = _.extend({}, defaultParam, param);

            return __(param.message, {});
        }
    ];
});
