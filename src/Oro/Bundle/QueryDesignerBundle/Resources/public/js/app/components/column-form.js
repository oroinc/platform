/*jslint nomen:true*/
/*global define*/
define(function (require) {
    'use strict';

    var _, $, defaults;
    _ = require('underscore');
    $ = require('oroentity/js/field-choice');
    require('oroquerydesigner/js/function-choice');

    defaults = {
        showItems: ['column', 'label', 'function', 'sorting', 'action']
    };

    return function (options) {
        var $form, $fields, $functions, $label;

        options = $.extend({}, defaults, options);

        $form = options._sourceElement;
        $fields = $form.find('[data-purpose=column-selector]');
        $functions = $form.find('[data-purpose=function-selector]');
        $label = $form.find('[data-purpose=label]');

        if (_.contains(options.showItems, 'function')) {
            $functions.functionChoice({
                fieldChoiceSelector: $fields
            });
        }

        $fields
            .on('change', function (e) {
                if (e.added) {
                    // update label input on field change
                    $label.val(e.added.text).trigger('change');
                }
            });

    };
});
