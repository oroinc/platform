define(function(require) {
    'use strict';

    var WidgetPickerModal;
    var $ = require('jquery');
    var _ = require('underscore');
    var Modal = require('oroui/js/modal');

    WidgetPickerModal = Modal.extend({
        events: function() {
            var events = _.defaults({
                'click .dashboard-picker-collapse': '_toggleWidget',
                'click .add-widget-button': '_onClickAddToDashboard'
            }, _.result(WidgetPickerModal.__super__, 'events'));
            return events;
        },

        initialize: function(options) {
            if (!options.loadWidget) {
                throw new Error('Missing required "loadWidget" option');
            }
            _.extend(this, _.pick(options, ['loadWidget']));
            WidgetPickerModal.__super__.initialize.call(this, options);
        },

        /**
         * @protected
         */
        _toggleWidget: function(e) {
            var $toggler = this.$(e.currentTarget);
            var $container = $toggler.closest('.dashboard-widget-container');
            $toggler.toggleClass('collapsed-state');
            $container.find('.dashboard-widgets-description').fadeToggle();
        },

        /**
         * @protected
         */
        _onClickAddToDashboard: function(e) {
            var $control = $(e.currentTarget);
            if ($control.hasClass('disabled')) {
                return;
            }
            var widgetName = $control.data('widget-name');
            $control.closest('.dashboard-widget-container')
                .addClass('loading-widget-content');

            this._startLoading();
            this.loadWidget(widgetName)
                .then(_.bind(this._endLoading, this));
        },

        /**
         * @protected
         */
        _startLoading: function() {
            this.$('.add-widget-button').addClass('disabled');
        },

        /**
         * @protected
         */
        _endLoading: function() {
            this.$('.add-widget-button').removeClass('disabled');
            var $widgetContainer = this.$('.dashboard-widget-container.loading-widget-content')
                .removeClass('loading-widget-content');
            var originalColor = $widgetContainer.css('background-color');
            $widgetContainer.animate({backgroundColor: '#F5F55B'}, 50, function() {
                $widgetContainer.animate({backgroundColor: originalColor}, 50);
            });
        }
    });

    return WidgetPickerModal;
});
