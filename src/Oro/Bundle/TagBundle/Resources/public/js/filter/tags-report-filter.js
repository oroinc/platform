define(
    ['underscore', 'oro/filter/dictionary-filter'],
    function(_, DictionaryFilter) {
    'use strict';

    var TagsReportFilter;

    /**
     * Multiple select filter: filter values as multiple select options
     *
     * @export  oro/filter/tags-report-filter
     * @class   oro.filter.TagsReportFilter
     * @extends oro.filter.DictionaryFilter
     */
    TagsReportFilter = DictionaryFilter.extend({

        /**
         * @inheritDoc
         */
        criteriaValueSelectors: {
            type: 'select[name="tag_part"]'
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.entityClass = this.filterParams.entityClass.replace(/\\/g, '_');
            TagsReportFilter.__super__.initialize.apply(this, arguments);
        },

        _readDOMValue: function() {
            var value = TagsReportFilter.__super__._readDOMValue.apply(this, arguments);
            return _.extend(value, {entity_class: this.entityClass});
        }
    });

    return TagsReportFilter;
});
