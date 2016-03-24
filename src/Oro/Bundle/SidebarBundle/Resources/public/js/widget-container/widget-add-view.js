
define(function(require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');

    var widgetAddTemplate = require('text!./templates/widget-add-template.html');
    var WidgetContainerModel = require('./model');

    var Modal = require('oroui/js/modal');
    var constants = require('../constants');

    var __ = require('orotranslation/js/translator');

    /**
     * @export  orosidebar/js/widget-container/widget-add-view
     * @class   orosidebar.widgetContainer.WidgetAddView
     * @extends oro.Modal
     */
    return Modal.extend({
        /** @property {String} */
        className: 'modal oro-modal-normal sidebar-widgets-wrapper',

        options: {
            sidebar: null
        },

        events: function() {
            var events = _.defaults({
                'click .sidebar-picker-collapse': '_toggleWidget',
                'click .add-widget-button': '_onClickAddToSidebar'
            }, _.result(Modal.__super__, 'events'));
            return events;
        },

        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            options.content = _.template(widgetAddTemplate)({
                'availableWidgets': options.sidebar.getAvailableWidgets()
            });
            options.title = __('oro.sidebar.widget.add.dialog.title');

            Modal.prototype.initialize.apply(this, arguments);
        },

        /**
         * @param {Event} e
         * @protected
         */
        _toggleWidget: function(e) {
            e.preventDefault();
            var $toggler = $(e.currentTarget);
            var $container = $toggler.closest('.sidebar-widget-container');
            $toggler.toggleClass('collapsed-state');
            $container.find('.sidebar-widgets-description').fadeToggle();
        },

        /**
         *
         * @param $control
         * @returns {Function}
         * @protected
         */
        _startLoading: function($control) {
            $control.addClass('disabled');
            var sidebarContainer = $control.parents('.sidebar-widget-container');
            sidebarContainer.addClass('loading-widget-content');
            return function (){
                $control.removeClass('disabled');
                sidebarContainer.removeClass('loading-widget-content');
            }
        },

        /**
         * @param {Event} e
         * @private
         */
        _onClickAddToSidebar: function(e) {
            e.preventDefault();
            var $control = $(e.target);
            var position = this.options.sidebar.getPosition();
            var availableWidgets = this.options.sidebar.getAvailableWidgets();
            var widgets = this.options.sidebar.getWidgets();
            var widgetName = $control.data('widget-name');
            var widgetData = availableWidgets[widgetName];
            var placement = null;
            if (position === constants.SIDEBAR_LEFT) {
                placement = 'left';
            } else if (position === constants.SIDEBAR_RIGHT) {
                placement = 'right';
            }
            var widget = new WidgetContainerModel(_.extend({}, widgetData, {
                widgetName: widgetName,
                position: widgets.length,
                placement: placement
            }));
            widgets.push(widget);
            var endLoadingFunction = this._startLoading($control);
            widget.save().then(endLoadingFunction);
        }
    });
});
