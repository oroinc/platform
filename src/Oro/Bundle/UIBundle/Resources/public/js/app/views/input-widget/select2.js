define(function(require) {
    'use strict';

    const AbstractInputWidgetView = require('oroui/js/app/views/input-widget/abstract');
    const $ = require('jquery');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const tools = require('oroui/js/tools');
    // current version: http://select2.github.io/select2/
    // last version: http://select2.github.io/examples.html
    require('jquery.select2');

    const Select2InputWidgetView = AbstractInputWidgetView.extend({
        initializeOptions: {
            containerCssClass: 'oro-select2',
            dropdownCssClass: 'oro-select2__dropdown',
            placeholder: __('Please select'),
            dropdownAutoWidth: !tools.isMobile(),
            minimumInputLength: 0,
            minimumResultsForSearch: 7,
            adaptContainerCssClass: function(className) {
                const containerCssClass = this.initializeOptions.containerCssClass;
                if (!containerCssClass) {
                    return false;
                }
                return className.indexOf(containerCssClass) === 0;
            }
        },

        closeOnOverlap: false,

        events: {
            'select2-opening': 'disableKeyboard'
        },

        widgetFunctionName: 'select2',

        destroyOptions: 'destroy',

        /**
         * @inheritdoc
         */
        constructor: function Select2InputWidgetView(options) {
            Select2InputWidgetView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            // fix select2.each2 bug, when empty string is FALSE
            const elCases = this.$el.attr('class');

            if (elCases) {
                this.$el.attr('class', elCases.trim());
            }
            Select2InputWidgetView.__super__.initialize.call(this, options);

            if (this.isInitialized()) {
                const data = this.$el.data(this.widgetFunctionName);
                data.container.data('inputWidget', this);
                data.dropdown.data('inputWidget', this);
            }

            this.updateFixedMode();
        },

        resolveOptions: function(options) {
            Select2InputWidgetView.__super__.resolveOptions.call(this, options);
            if (this.initializeOptions.adaptContainerCssClass) {
                this.initializeOptions.adaptContainerCssClass =
                    this.initializeOptions.adaptContainerCssClass.bind(this);
            }

            this.initializeOptions.closeOnOverlap = this.closeOnOverlap;
        },

        isInitialized: function() {
            return Boolean(this.$el.data(this.widgetFunctionName));
        },

        /**
         * Detects if the widget has a parent with fixed position, sets Select2 prop, and updates Select2 dropdown
         * position if it's needed
         *
         * @param {boolean} [updatePosition]
         */
        updateFixedMode: function(updatePosition) {
            const select2Inst = this.$el.data(this.widgetFunctionName);
            const hasFixedParent = _.some(this.$el.parents(), function(el) {
                return $(el).css('position') === 'fixed';
            });

            if (hasFixedParent !== select2Inst.dropdownFixedMode) {
                select2Inst.dropdownFixedMode = hasFixedParent;

                if (updatePosition) {
                    // use defer to avoid blinking of dropdown
                    _.defer(select2Inst.positionDropdown.bind(select2Inst));
                }
            }
        },

        disposeWidget: function() {
            this.close();
            return Select2InputWidgetView.__super__.disposeWidget.call(this);
        },

        findContainer: function() {
            return this.$el.data(this.widgetFunctionName).container;
        },

        open: function(...args) {
            return this.applyWidgetFunction('open', args);
        },

        close: function(...args) {
            return this.applyWidgetFunction('close', args);
        },

        val: function(...args) {
            const result = this.applyWidgetFunction('val', args);

            if (!args.length) {
                return result;
            }
            return this.$el;
        },

        data: function(...args) {
            return this.applyWidgetFunction('data', args);
        },

        updatePosition: function(...args) {
            return this.applyWidgetFunction('positionDropdown', args);
        },

        focus: function(...args) {
            return this.applyWidgetFunction('focus', args);
        },

        search: function(...args) {
            return this.applyWidgetFunction('search', args);
        },

        disable: function(disable) {
            return this.applyWidgetFunction('enable', [!disable]);
        },

        disableKeyboard: function() {
            const select = this.$el;
            const selectContainer = this.getContainer();
            const isSearchHidden = selectContainer.find('.select2-search-hidden').length;
            const minimumResultsForSearch = this.initializeOptions.minimumResultsForSearch;
            const optionsLength = select.find('option').length;

            if ((tools.isMobile() || tools.isIOS()) && (isSearchHidden || optionsLength < minimumResultsForSearch)) {
                selectContainer.find('.select2-search').hide();
                selectContainer.find('.select2-focusser').attr('readonly', true);
            }
        }
    });

    return Select2InputWidgetView;
});
