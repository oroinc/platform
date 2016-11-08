define(function(require) {
    'use strict';

    var WeekDaySelectComponent;
    var DAYS_OF_WEEK = [void 0, 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
    var $ = require('jquery');
    var _ = require('underscore');
    var localeSettings = require('orolocale/js/locale-settings');
    var MultiCheckboxView = require('oroform/js/app/views/multi-checkbox-view');
    var BaseComponent = require('oroui/js/app/components/base/component');

    WeekDaySelectComponent = BaseComponent.extend({
        view: null,

        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function(options) {
            var container = $('<div>');
            $(options._sourceElement).after(container);
            this.view = new MultiCheckboxView({
                el: container,
                items: this.createItems(),
                boundInput: options._sourceElement
            })
        },

        createItems: function() {
            var names = localeSettings.getCalendarDayOfWeekNames('narrow');
            return _.map(localeSettings.getSortedDayOfWeekNumbers(), function(index) {
                return {
                    value: DAYS_OF_WEEK[index],
                    text: names[index]
                }
            });
        }
    });

    return WeekDaySelectComponent;
});
