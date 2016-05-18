define(function(require) {
    'use strict';

    var $ = require('jquery');
    var routing = require('routing');
    var Select2AutocompleteComponent = require('oro/select2-autocomplete-component');

    var Select2AttendeesComponent = Select2AutocompleteComponent.extend({
        setConfig: function(config) {
            config = Select2AttendeesComponent.__super__.setConfig.apply(this, arguments);
            config.initSelection = function(element, callback) {
                $.ajax({
                    url: routing.generate(
                        'oro_calendar_event_attendees_autocomplete_data',
                        {id: element.val()}
                    ),
                    type: 'GET',
                    success: function(data) {
                        callback(data);
                        element.trigger('select2-data-loaded');
                    }
                });
            };

            return config;
        }
    });

    return Select2AttendeesComponent;
});
