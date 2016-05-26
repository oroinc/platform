define(function(require) {
    'use strict';

    var $ = require('jquery');
    var routing = require('routing');
    var Select2AutocompleteComponent = require('oro/select2-autocomplete-component');

    var Select2AttendeesComponent = Select2AutocompleteComponent.extend({
        setConfig: function(config) {
            config = Select2AttendeesComponent.__super__.setConfig.apply(this, arguments);

            var that = this;
            config.initSelection = function(element, callback) {
                $.ajax({
                    url: routing.generate(
                        'oro_calendar_event_attendees_autocomplete_data',
                        {id: element.val()}
                    ),
                    type: 'GET',
                    success:  $.proxy(function(data) {
                        that.excluded = data.excluded;
                        callback(data.result);
                        element.trigger('select2-data-loaded');
                    }, this)
                });
            };

            return config;
        }
    });

    return Select2AttendeesComponent;
});
