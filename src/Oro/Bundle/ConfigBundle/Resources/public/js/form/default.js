define([
    'jquery',
    'underscore'
], function($, _) {
    'use strict';

    return function() {
        $(function() {
            function prepareTinymce(textareas) {
                if (textareas.length > 0) {
                    $(textareas).each(function(i, el) {
                        if ($(el).tinymce) {
                            var settings;
                            var tinymceInstance = $(el).tinymce();
                            if (tinymceInstance) {
                                if ($(el).prop('disabled')) {
                                    settings = tinymceInstance.editorManager.activeEditor.settings;
                                    settings.readonly = true;
                                    tinymceInstance.editorManager.activeEditor.remove();
                                    $(el).tinymce(settings);
                                } else {
                                    settings = tinymceInstance.editorManager.activeEditor.settings;
                                    settings.readonly = false;
                                    tinymceInstance.editorManager.activeEditor.remove();
                                    $(el).tinymce(settings);
                                }
                            }
                        }
                    });
                }
            }
            prepareTinymce($.find('textarea'));
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
