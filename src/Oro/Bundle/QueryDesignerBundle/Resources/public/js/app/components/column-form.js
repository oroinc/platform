define(function(require) {
    'use strict';

    var _ = require('underscore');
    var $ = require('oroentity/js/field-choice');
    require('oroquerydesigner/js/function-choice');

    var defaults = {
        showItems: ['column', 'label', 'function', 'sorting', 'action']
    };

    return function(options) {
        options = $.extend({}, defaults, options);

        var $form = options._sourceElement;
        var $fields = $form.find('[data-purpose=column-selector]');
        var $functions = $form.find('[data-purpose=function-selector]');
        var $label = $form.find('[data-purpose=label]');

        if (_.contains(options.showItems, 'function')) {
            $functions.functionChoice({
                fieldChoiceSelector: $fields
            });
        }

        $fields
            .on('change', function(e) {
                if (e.added) {
                    // update label input on field change
                    $label.val(e.added.text).trigger('change');
                }
            });

    };
});
