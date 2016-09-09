define(function(require) {
    'use strict';

    var Select2InputWidget;
    var AbstractInputWidget = require('oroui/js/app/views/input-widget/abstract');
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    // current version: http://select2.github.io/select2/
    // last version: http://select2.github.io/examples.html
    require('jquery.select2');

    Select2InputWidget = AbstractInputWidget.extend({
        initializeOptions: {
            containerCssClass: 'oro-select2',
            dropdownCssClass: 'oro-select2__dropdown',
            placeholder: __('Please select'),
            dropdownAutoWidth: true,
            minimumInputLength: 0,
            minimumResultsForSearch: 7,
            adaptContainerCssClass: function(className) {
                var containerCssClass = this.initializeOptions.containerCssClass;
                if (!containerCssClass) {
                    return false;
                }
                return className.indexOf(containerCssClass) === 0;
            }
        },

        widgetFunctionName: 'select2',

        destroyOptions: 'destroy',

        initialize: function(options) {
            //fix select2.each2 bug, when empty string is FALSE
            this.$el.attr('class', $.trim(this.$el.attr('class')));
            Select2InputWidget.__super__.initialize.apply(this, arguments);

            if (this.isInitialized()) {
                var data = this.$el.data(this.widgetFunctionName);
                data.container.data('inputWidget', this);
                data.dropdown.data('inputWidget', this);
            }
        },

        resolveOptions: function(options) {
            Select2InputWidget.__super__.resolveOptions.apply(this, arguments);
            if (this.initializeOptions.adaptContainerCssClass) {
                this.initializeOptions.adaptContainerCssClass = _.bind(
                    this.initializeOptions.adaptContainerCssClass,
                    this
                );
            }
        },

        isInitialized: function() {
            return Boolean(this.$el.data(this.widgetFunctionName));
        },

        disposeWidget: function() {
            this.close();
            return Select2InputWidget.__super__.disposeWidget.apply(this, arguments);
        },

        findContainer: function() {
            return this.$el.data(this.widgetFunctionName).container;
        },

        open: function() {
            return this.applyWidgetFunction('open', arguments);
        },

        close: function() {
            return this.applyWidgetFunction('close', arguments);
        },

        val: function() {
            var result = this.applyWidgetFunction('val', arguments);

            if (!arguments.length) {
                return result;
            }
            return this.$el;
        },

        data: function() {
            return this.applyWidgetFunction('data', arguments);
        },

        updatePosition: function() {
            return this.applyWidgetFunction('positionDropdown', arguments);
        },

        focus: function() {
            return this.applyWidgetFunction('focus', arguments);
        },

        search: function() {
            return this.applyWidgetFunction('search', arguments);
        }
    });

    return Select2InputWidget;
});
