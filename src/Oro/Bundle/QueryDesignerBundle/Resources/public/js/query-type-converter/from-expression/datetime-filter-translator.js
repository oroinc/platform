define(function(require) {
    'use strict';

    var _ = require('underscore');
    var DateFilterTranslator =
        require('oroquerydesigner/js/query-type-converter/from-expression/date-filter-translator');
    var datePartMap = DateFilterTranslator.prototype.partMap;

    /**
     * @inheritDoc
     */
    var DatetimeFilterTranslator = function DatetimeFilterTranslatorFromExpression() {
        DatetimeFilterTranslator.__super__.constructor.apply(this, arguments);
    };

    DatetimeFilterTranslator.prototype = Object.create(DateFilterTranslator.prototype);
    DatetimeFilterTranslator.__super__ = DateFilterTranslator.prototype;

    Object.assign(DatetimeFilterTranslator.prototype, {
        constructor: DatetimeFilterTranslator,

        /**
         * @inheritDoc
         */
        filterType: 'datetime',

        /**
         * @inheritDoc
         */
        partMap: _.defaults({
            value: _.defaults({
                valuePattern: /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/
            }, datePartMap.value)
        }, datePartMap)
    });

    return DatetimeFilterTranslator;
});
