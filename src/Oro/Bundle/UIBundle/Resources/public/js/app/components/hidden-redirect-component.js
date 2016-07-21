define(function(require) {
    'use strict';

    var HiddenRedirectComponent;
    var $ = require('jquery');
    var __ = require('orotranslation/js/translator');
    var mediator = require('oroui/js/mediator');
    var Modal = require('oroui/js/modal');
    var BaseComponent = require('oroui/js/app/components/base/component');

    /**
     * @export oroui/js/app/components/hidden-redirect-component
     * @extends oroui.app.components.base.Component
     * @class oroui.app.components.HiddenRedirectComponent
     */
    HiddenRedirectComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        element: null,

        /**
         * @property {string}
         */
        type: 'info',

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.element = options._sourceElement;
            if (!this.element) {
                return;
            }

            if (options.type) {
                this.type = options.type;
            }

            var self = this;
            this.element.on('click.' + this.cid, function(e) {
                e.preventDefault();
                var pageStateView = mediator.execute('composer:retrieve', 'pageState', true);

                if (pageStateView.isStateChanged()) {
                    var confirmModal = self.createModal();
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
            var form = $('form[data-collect=true]');
            var actionInput = form.find('input[name="input_action"]');
            $.ajax({
                url: this.element.attr('href'),
                type: 'GET',
                success: function(response) {
                    actionInput.val(JSON.stringify({
                        redirectUrl: response.url
                    }));
                    form.trigger('submit');
                },
                error: function(xhr) {
                    Error.handle({}, xhr, {enforce: true});
                }
            });
        },

        startRedirect: function() {
            var _this = this;
            $.ajax({
                url: this.element.attr('href'),
                type: 'GET',
                success: function(response) {
                    _this._processResponse(response.url, response.message);
                },
                error: function(xhr) {
                    Error.handle({}, xhr, {enforce: true});
                }
            });
        },

        createModal: function() {
            return new Modal({
                title: __('oro.ui.leave_page_save_data_or_discard_title'),
                content: __('oro.ui.leave_page_save_data_or_discard'),
                okText: __('Save'),
                secondaryText: __('Discard'),
                className: 'modal modal-primary',
                okButtonClass: 'btn-primary btn-large',
                cancelText: __('Cancel'),
                template: require('tpl!oroui/templates/three-buttons-modal.html')
            });
        },

        /**
         * @inheritDoc
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
                    var self = this;
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
        }
    });

    return HiddenRedirectComponent;
});
