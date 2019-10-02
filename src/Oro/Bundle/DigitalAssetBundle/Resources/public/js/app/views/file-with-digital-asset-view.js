define([
    'jquery',
    'underscore',
    'orotranslation/js/translator',
    'routing',
    'oro/dialog-widget',
    'oroui/js/app/views/base/view'
], function($, _, __, routing, DialogWidget, BaseView) {
    'use strict';

    var FileWithDigitalAssetView;

    FileWithDigitalAssetView = BaseView.extend({
        autoRender: true,

        fieldLabel: '',

        urlParts: null,

        emptyFileInputSelector: null,

        dialogWidget: null,

        events: {
            'click [data-role="select-data-asset"]': 'onSelect',
            'click [data-role="remove"]': 'onRemove'
        },

        /**
         * @inheritDoc
         */
        constructor: function FileWithDigitalAssetView() {
            FileWithDigitalAssetView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            FileWithDigitalAssetView.__super__.initialize.apply(this, arguments);
            _.extend(this, _.pick(options, 'fieldLabel', 'urlParts', 'emptyFileInputSelector'));
        },

        onSelect: function(e) {
            e.preventDefault();

            this.dialogWidget = new DialogWidget({
                title: __('Select {{ field }}', {field: this.fieldLabel}),
                url: routing.generate(
                    this.urlParts.widget.route,
                    this.urlParts.widget.parameters
                ),
                stateEnabled: false,
                incrementalPosition: true,
                desktopLoadingBar: true,
                dialogOptions: {
                    modal: true,
                    allowMaximize: true,
                    width: 1280,
                    height: 650,
                    close: _.bind(this.onDialogClose, this)
                }
            });

            this.dialogWidget.once('grid-row-select', _.bind(this.onGridRowSelect, this));
            this.dialogWidget.render();
        },

        onDialogClose: function() {
            // TODO: Render input widget
        },

        onGridRowSelect: function(data) {
            this.setEmptyFile(false);

            // TODO: Select data asset
        },

        onRemove: function(e) {
            e.preventDefault();
            this.setEmptyFile(true);

            // TODO: render template for file selector
        },

        setEmptyFile: function(isEmpty) {
            if (this.emptyFileInputSelector) {
                this.$(this.emptyFileInputSelector).val(true);
            }
        }
    });

    return FileWithDigitalAssetView;
});
