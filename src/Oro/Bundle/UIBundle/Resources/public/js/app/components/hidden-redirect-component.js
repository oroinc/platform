define(function(require) {
    'use strict';

    const $ = require('jquery');
    const __ = require('orotranslation/js/translator');
    const mediator = require('oroui/js/mediator');
    const Modal = require('oroui/js/modal');
    const BaseComponent = require('oroui/js/app/components/base/component');

    /**
     * @export oroui/js/app/components/hidden-redirect-component
     * @extends oroui.app.components.base.Component
     * @class oroui.app.components.HiddenRedirectComponent
     */
    const HiddenRedirectComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        element: null,

        /**
         * @property {string}
         */
        type: 'info',

        /**
         * @property {Boolean}
         */
        showLoading: false,

        /**
         * @inheritdoc
         */
        constructor: function HiddenRedirectComponent(options) {
            HiddenRedirectComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.element = options._sourceElement;
            if (!this.element) {
                return;
            }

            if (options.type) {
                this.type = options.type;
            }

            if (options.showLoading) {
                this.showLoading = options.showLoading;
            }

            const self = this;
            this.element.on('click.' + this.cid, function(e) {
                self._showLoading();

                e.preventDefault();

                if (mediator.execute('isPageStateChanged')) {
                    const confirmModal = self.createModal();
                    confirmModal.once('ok', function() {
                        self.saveAndRedirect();
                        setTimeout(function() {
                            confirmModal.dispose();
                        }, 0);
                    });
                    confirmModal.once('cancel', function() {
                        setTimeout(function() {
                            confirmModal.dispose();
                        }, 0);
                    });
                    confirmModal.once('buttonClick', function(id) {
                        if (id === 'secondary') {
                            self.startRedirect();
                        }
                        setTimeout(function() {
                            confirmModal.dispose();
                        }, 0);
                    });
                    confirmModal.open();
                    return false;
                }
                self.startRedirect();
                return false;
            });
        },

        saveAndRedirect: function() {
            const form = $('form[data-collect=true]');
            const actionInput = form.find('input[name="input_action"]');
            $.ajax({
                url: this.element.attr('href'),
                type: this.element.data('request-method') || 'GET',
                success: response => {
                    this._hideLoading();
                    actionInput.val(JSON.stringify({
                        redirectUrl: response.url
                    }));
                    form.trigger('submit');
                },
                error: xhr => {
                    this._hideLoading();
                }
            });
        },

        startRedirect: function() {
            mediator.execute('showLoading');
            $.ajax({
                url: this.element.attr('href'),
                type: this.element.data('request-method') || 'GET',
                success: response => {
                    this._hideLoading();
                    this._processResponse(response.url, response.message);
                },
                error: xhr => {
                    this._hideLoading();
                }
            });
        },

        createModal: function() {
            return new Modal({
                title: __('oro.ui.leave_page_save_data_or_discard_title'),
                content: __('oro.ui.leave_page_save_data_or_discard'),
                okText: __('Save'),
                className: 'modal modal-primary',
                cancelText: __('Cancel'),
                template: require('tpl-loader!oroui/templates/three-buttons-modal.html')
            });
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed || !this.element) {
                return;
            }

            this.element.off('.' + this.cid);

            HiddenRedirectComponent.__super__.dispose.call(this);
        },

        /**
         * @param {string|null} url
         * @param {string|null} message
         */
        _processResponse: function(url, message) {
            if (url) {
                if (message) {
                    const self = this;
                    mediator.once('page:afterChange', function() {
                        self._showMessage(self.type, message);
                    });
                }

                if (mediator.execute('compareUrl', url)) {
                    mediator.execute('refreshPage');
                } else {
                    mediator.execute('redirectTo', {url: url});
                }
            } else if (message) {
                this._showMessage(this.type, message);
            }
        },

        /**
         * @param {string} type
         * @param {string} message
         */
        _showMessage: function(type, message) {
            mediator.execute('showFlashMessage', type, message);
        },

        _showLoading: function() {
            if (!this.showLoading) {
                return;
            }

            mediator.execute('showLoading');
        },

        _hideLoading: function() {
            if (!this.showLoading) {
                return;
            }

            mediator.execute('hideLoading');
        }
    });

    return HiddenRedirectComponent;
});
