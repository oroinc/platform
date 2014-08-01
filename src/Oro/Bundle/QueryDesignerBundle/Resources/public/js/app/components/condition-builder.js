/*jslint nomen:true*/
/*global define*/
define(function (require) {
    'use strict';

    var $, _, defaults;
    $ = require('oroquerydesigner/js/condition-builder');
    _ = require('underscore');
    defaults = {
        eriaListSelector: '',
        entityChoiceSelector: '',
        onFieldsUpdate: {
            toggleCriteria: []
        }
    };

    return function (options) {
        var $conditions, $entityChoice, $criteriaList, $criteria, toggleCriteria;

        options = $.extend(true, {}, defaults, options);
        $conditions = options._sourceElement;
        $entityChoice = $(options.entityChoiceSelector);
        $criteriaList = $(options.criteriaListSelector);

        toggleCriteria = options.onFieldsUpdate.toggleCriteria;
        $criteria = $criteriaList.find('[data-criteria]').filter(function () {
            return _.contains(toggleCriteria, $(this).data('criteria'));
        });

        $entityChoice
            .on('fieldsloaderupdate', function (e, fields) {
                $conditions.conditionBuilder('setValue', []);
                $criteria.toggleClass('disabled', $.isEmptyObject(fields));
            });

        $conditions.conditionBuilder({
            criteriaListSelector: $criteriaList
        });
    };
});
