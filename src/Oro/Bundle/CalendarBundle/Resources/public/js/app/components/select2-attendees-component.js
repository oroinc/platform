define(function(require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');
    var routing = require('routing');
    var Select2AutocompleteComponent = require('oro/select2-autocomplete-component');

    var Select2AttendeesComponent = Select2AutocompleteComponent.extend({
        setConfig: function(config) {
            config.selected = config.selected || {};
            config = Select2AttendeesComponent.__super__.setConfig.apply(this, arguments);

            config.ajax.results = _.wrap(config.ajax.results, function(func, data, page) {
                var response = func.call(this, data, page);
                _.each(response.results, function(item) {
                    if (config.selected[item.id]) {
                        item.id = config.selected[item.id];
                    }
                });

                return response;
            });

            if (config.needsInit) {
                config.initSelection = function(element, callback) {
                    $.ajax({
                        url: routing.generate(
                            'oro_calendar_event_attendees_autocomplete_data',
                            {id: element.val()}
                        ),
                        type: 'GET',
                        success:  $.proxy(function(data) {
                            config.selected = data.excluded;
                            callback(data.result);
                            element.trigger('select2-data-loaded');
                        }, this)
                    });
                };
            }

            return config;
        }
    });

    return Select2AttendeesComponent;
});
