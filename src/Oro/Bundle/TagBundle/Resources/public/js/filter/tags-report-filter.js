define(
    ['underscore', 'oro/filter/dictionary-filter'],
    function(_, DictionaryFilter) {
        'use strict';

        /**
         * Multiple select filter: filter values as multiple select options
         *
         * @export  oro/filter/tags-report-filter
         * @class   oro.filter.TagsReportFilter
         * @extends oro.filter.DictionaryFilter
         */
        const TagsReportFilter = DictionaryFilter.extend({

            /**
             * @inheritdoc
             */
            criteriaValueSelectors: {
                type: 'select[name="tag_part"]'
            },

            /**
             * @inheritdoc
             */
            constructor: function TagsReportFilter(options) {
                TagsReportFilter.__super__.constructor.call(this, options);
            },

            /**
             * @inheritdoc
             */
            initialize: function(options) {
                this.entityClass = this.filterParams.entityClass.replace(/\\/g, '_');
                TagsReportFilter.__super__.initialize.call(this, options);
            },

            _readDOMValue: function() {
                const value = TagsReportFilter.__super__._readDOMValue.call(this);
                return _.extend(value, {entity_class: this.entityClass});
            }
        });

        return TagsReportFilter;
    });
