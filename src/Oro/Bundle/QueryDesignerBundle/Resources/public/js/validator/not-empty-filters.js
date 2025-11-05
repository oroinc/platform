import _ from 'underscore';
import __ from 'orotranslation/js/translator';
import $ from 'jquery';

const defaultParam = {
    message: 'oro.query_designer.condition_builder.filters.not_empty'
};

export default [
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
