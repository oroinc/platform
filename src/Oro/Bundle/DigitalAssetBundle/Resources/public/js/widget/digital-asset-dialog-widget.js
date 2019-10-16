define(function(require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var DialogWidget = require('oro/dialog-widget');
    var errorHandler = require('oroui/js/error');

    var DigitalAssetDialogWidget;

    DigitalAssetDialogWidget = DialogWidget.extend({
        options: _.extend({}, DialogWidget.prototype.options, {
            alias: 'dam-dialog',
            title: __('oro.digitalasset.dam.dialog.select_file'),
            url: null,
            stateEnabled: false,
            incrementalPosition: true,
            desktopLoadingBar: true,
            moveAdoptedActions: false,
            dialogOptions: {
                allowMaximize: false,
                allowMinimize: false,
                modal: true,
                maximizedHeightDecreaseBy: 'minimize-bar',
                width: 1100
            }
        }),

        /**
         * @inheritDoc
         */
        constructor: function DigitalAssetDialogWidget() {
            DigitalAssetDialogWidget.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            DigitalAssetDialogWidget.__super__.initialize.apply(this, arguments);

            // Adds Cancel button to the actions container at the bottom of dialog window.
            this.listenTo(this, 'widgetReady', (function($el) {
                var cancelButton = $el.find('[type="reset"]').clone();
                cancelButton.text(__('oro.digitalasset.dam.dialog.cancel.label'));
                cancelButton.on('click', (function() {
                    this.remove();
                }).bind(this));

                this.addAction('cancel', 'main', cancelButton);
            }).bind(this));
        },

        /**
         * @inheritDoc
         */
        initializeWidget: function(options) {
            DigitalAssetDialogWidget.__super__.initializeWidget.apply(this, arguments);

            this.on('formReset', _.bind(this._onFormReset, this));
        },

        /**
         * @inheritDoc
         */
        _onAdoptedFormResetClick: function(form) {
            this._onFormReset(form);
        },

        /**
         * @param {jQuery.Element} [form]
         * @private
         */
        _onFormReset: function(form) {
            form = form || this.form;

            $(form).trigger('reset');
            $(form).find('[type="file"]').trigger('change');
        },

        /**
         * @inheritDoc
         *
         * Overrides parent method to enable JSON-only handling on content load - prevents dialog window from blanking.
         */
        _onContentLoad: function(content) {
            var json = this._getJson(content);

            delete this.loading;

            if (json) {
                this._onJsonContentResponse(json);
            } else {
                this.disposePageComponents();
                this.setContent(content, true);
            }

            if (this.deferredRender) {
                this.deferredRender
                    .done(_.bind(this._triggerContentLoadEvents, this, content))
                    .fail(_.bind(function(error) {
                        if (!this.disposing && !this.disposed) {
                            if (error) {
                                errorHandler.showErrorInConsole(error);
                            }
                            this._triggerContentLoadEvents();
                        }
                    }, this));
            } else {
                this._triggerContentLoadEvents();
            }
        }
    });

    return DigitalAssetDialogWidget;
});
