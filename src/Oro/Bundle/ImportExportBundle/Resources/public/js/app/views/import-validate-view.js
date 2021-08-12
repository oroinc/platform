define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const $ = require('jquery');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const WidgetManager = require('oroui/js/widget-manager');
    const Messenger = require('oroui/js/messenger');
    const DeleteConfirmation = require('oroui/js/standart-confirmation');

    const ImportValidateView = BaseView.extend({
        autoRender: true,

        optionNames: BaseView.prototype.optionNames.concat([
            'importProcessorAliasesToConfirmMessages', 'wid'
        ]),

        /**
         * @inheritdoc
         */
        constructor: function ImportValidateView(options) {
            ImportValidateView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            ImportValidateView.__super__.initialize.call(this, options);

            WidgetManager.getWidgetInstance(this.wid, this.onWidgetLoad.bind(this));
        },

        render: function() {
            this.refreshActiveInputWidgets();

            return ImportValidateView.__super__.render.call(this);
        },

        onImportButtonClick: function() {
            const $form = this.getCurrentlyActiveTabContent().find('form');
            const importProcessorAliasesToConfirmMessages = this.importProcessorAliasesToConfirmMessages;

            $form.find('input[name=isValidateJob]').val(false);

            const currentlyChosenProcessorAlias = this.getCurrentlyChosenProcessorAlias();

            if (importProcessorAliasesToConfirmMessages[currentlyChosenProcessorAlias] !== undefined) {
                const confirm = new DeleteConfirmation({
                    content: importProcessorAliasesToConfirmMessages[currentlyChosenProcessorAlias]
                });

                confirm.on('ok', function() {
                    $form.submit();
                });

                confirm.open();
            } else {
                $form.submit();
            }
        },

        onValidateImportButtonClick: function() {
            const $form = this.getCurrentlyActiveTabContent().find('form');

            $form.find('input[name=isValidateJob]').val(true);
            $form.submit();
        },

        onWidgetLoad: function(widget) {
            this.resetWidgetFormToActiveTabForm(widget);

            this.$('.nav-tabs a').on('shown.bs.tab', function() {
                this.resetWidgetFormToActiveTabForm(widget);
                this.refreshActiveInputWidgets();
            }.bind(this));

            widget.getAction('import', 'adopted', function(action) {
                action.on('click', this.onImportButtonClick.bind(this));
            }.bind(this));

            widget.getAction('validate_import', 'adopted', function(action) {
                action.on('click', this.onValidateImportButtonClick.bind(this));
            }.bind(this));

            widget._onContentLoad = function(content) {
                if (_.has(content, 'success')) {
                    if (content.success) {
                        const message = _.has(content, 'message')
                            ? content.message
                            : __('oro.importexport.import.success.message');
                        Messenger.notificationFlashMessage('success', message);
                    } else {
                        Messenger.notificationFlashMessage('error', __('oro.importexport.import.form_fail.message'));
                    }
                    this.remove();
                } else {
                    delete this.loading;
                    this.disposePageComponents();
                    this.setContent(content, true);
                    this._triggerContentLoadEvents();
                }
            };

            widget._onContentLoadFail = function() {
                Messenger.notificationFlashMessage('error', __('oro.importexport.import.fail.message'));
                this.remove();
            };
        },

        resetWidgetFormToActiveTabForm: function(widget) {
            const $tabContent = this.getCurrentlyActiveTabContent();

            widget.form = $tabContent.find('form');
        },

        getCurrentlyActiveTabContent: function() {
            return this.$('.tab-pane.active');
        },

        getCurrentlyChosenProcessorAlias: function() {
            const form = this.getCurrentlyActiveTabContent().find('form');

            return form.find('select[name="oro_importexport_import[processorAlias]"]').val();
        },

        // this needs to be done because select2 does not work well with hidden selects in mobile version
        refreshActiveInputWidgets: function() {
            this.getCurrentlyActiveTabContent().find('select').each(function() {
                if (this.isRefreshed) {
                    return;
                }

                $(this).inputWidget('refresh');
                this.isRefreshed = true; // so that each select is not refreshed every time
            });
        }

    });

    return ImportValidateView;
});
