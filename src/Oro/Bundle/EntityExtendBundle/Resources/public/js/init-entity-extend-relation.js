/* jshint browser:true */
/* global require */
require(['jquery', 'orotranslation/js/translator', 'routing'],
function($, __, routing) {
    'use strict';
    $(function() {
        $(document).on('change', '[id^=oro_entity_config_type_extend_relation_target_entity]', function (e) {
            var el     = $(this),
                target = el.find('option:selected').attr('value').replace(/\\/g,'_'),
                query =  routing.generate.apply(routing, ['oro_entityconfig_field_search', {id: target}]),
                fields = $('form select.extend-rel-target-field');

            $(fields).each(function(index, el){
                var is_multiple = typeof $(el).attr('multiple') !== 'undefined' && $(el).attr('multiple') !== false;
                if (is_multiple) {
                    $(el).empty().append('<option value="">' + __('Loading...') + '</option>');
                } else {
                    $(el).prev('span').text(__('Loading...'));
                }
            });

            $.getJSON(query, function(response) {
                $(fields).each(function(index, el){
                    var items = [];

                    $.each( response, function( key, val ) {
                        items.push("<option value='" + key + "'>" + val + "</option>");
                    });

                    $(el).empty().append(items.join(''));
                    $(el).prev('span').text(__('oro.entity.form.choose_entity_field'));
                });
            });

            return false;
        });
    });
});
