define(function(require) {
    'use strict';

    var $ = require('oroquerydesigner/js/condition-builder');
    var _ = require('underscore');
    var defaults = {
        criteriaListSelector: '',
        entityChoiceSelector: '',
        onFieldsUpdate: {
            toggleCriteria: []
        }
    };

    return function(options) {
        options = $.extend(true, {}, defaults, options);
        var $conditions = options._sourceElement;
        var $entityChoice = $(options.entityChoiceSelector);
        var $criteriaList = $(options.criteriaListSelector);

        var toggleCriteria = options.onFieldsUpdate.toggleCriteria;
        var $criteria = $criteriaList.find('[data-criteria]').filter(function() {
            return _.contains(toggleCriteria, $(this).data('criteria'));
        });

        $entityChoice
            .on('fieldsloaderupdate', function(e, fields) {
                $conditions.conditionBuilder('setValue', []);
                $criteria.toggleClass('disabled', $.isEmptyObject(fields));
            });

        $conditions.conditionBuilder({
            criteriaListSelector: $criteriaList
        });
    };
});
