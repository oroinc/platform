define(function(require) {
    'use strict';

    var $ = require('jquery');
    var tinyMCE = require('tinymce/tinymce');

    return function() {
        $(function() {
            function prepareTinymce(textareas) {
                if (textareas.length > 0) {
                    $(textareas).each(function(i, el) {
                        var editor = tinyMCE.get(el.id);
                        if (editor) {
                            editor.setMode($(el).prop('disabled') ? 'readonly' : 'code');
                        }
                    });
                }
            }
            var value;
            var valueEls;
            var checkboxEls = $('.parent-scope-checkbox input');
            checkboxEls.on('change', function() {
                value = $(this).is(':checked');
                valueEls = $(this).parents('.controls').find(':input, a.btn, button').not(checkboxEls)
                    .not('[readonly]');
                valueEls.each(function(i, el) {
                    $(el)
                        .prop('disabled', value)
                        .data('disabled', value)
                        .attr('disabled', value)
                        .trigger(value ? 'disable' : 'enable');

                    $(el).inputWidget('refresh');
                });

                prepareTinymce($(this).parents('.controls').find('textarea'));
            });
        });
    };
});
