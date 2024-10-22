define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const mediator = require('oroui/js/mediator');
    const Modal = require('oroui/js/modal');
    const routing = require('routing');
    const BaseComponent = require('oroui/js/app/components/base/component');

    const SchemaUpdateActionComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            delimiter: ';'
        },

        /**
         * @inheritdoc
         */
        constructor: function SchemaUpdateActionComponent(options) {
            SchemaUpdateActionComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            const el = this.options._sourceElement;
            const title = __('Schema update confirmation');
            const content = `
                <p>${__('Your config changes will be applied to schema.')}
                <br>${__('It may take few minutes...')}</p>
            `;

            $(el).on('click', e => {
                const confirmUpdate = new Modal({
                    className: 'modal modal-primary',
                    cancelText: __('Cancel'),
                    okText: __('Yes, Proceed'),
                    title: title,
                    content: content
                });

                confirmUpdate.on('ok', () => {
                    const progress = $('#progressbar').clone();
                    progress
                        .removeAttr('id')
                        .find('h3').remove()
                        .end()
                        .find('[role="progressbar"]')
                        .attr('aria-valuetext', __('oro.entity_extend.schema_updating'));

                    const modal = new Modal({
                        allowCancel: false,
                        className: 'modal modal-primary',
                        title: title,
                        content: content
                    });
                    modal.open();
                    this.once('schema-update:finished', () => {
                        modal.close();
                    });
                    modal.$el.find('.modal-body').append(progress);
                    modal.$el.find('.modal-footer').html('');

                    progress.show();
                    this._updateSchema();
                });
                confirmUpdate.open();

                return false;
            });
        },

        /**
         * Sends request to update schema
         * @private
         */
        _updateSchema() {
            $.post({
                url: routing.generate(this.options.route),
                errorHandlerMessage: false
            }).done((data = {}, textStatus, jqXHR) => {
                // Considering schema update will be delayed
                if (Object.keys(data).length) {
                    this._postponeSchemaUpdate(data);
                } else {
                    this._updateSchemaDone(data, textStatus, jqXHR);
                }
            }).fail((jqXHR, textStatus, errorThrown) => {
                this._updateSchemaFail(jqXHR);
            });
        },

        /**
         * @param {Object} data
         * @private
         */
        _postponeSchemaUpdate(data) {
            const {status, postponeRequest} = data;
            if (!status) {
                throw new Error('Option "status" is required');
            }

            // Considering an operation is finished, so nothing to do
            if (status === 'failed' || status === 'success') {
                return;
            }

            if (!postponeRequest) {
                throw new Error('Option "postponeRequest" is required');
            }
            const {
                url: postponeRequestUrl,
                method: postponeRequestMethod,
                contentType: postponeRequestContentType,
                content: postponeRequestContent,
                timeout: postponeRequestTimeout
            } = postponeRequest;
            if (!postponeRequestUrl) {
                throw new Error('Option "postponeRequest.url" is required');
            }
            if (!postponeRequestMethod) {
                throw new Error('Option "postponeRequest.method" is required');
            }
            if (!postponeRequestContentType) {
                throw new Error('Option "postponeRequest.contentType" is required');
            }
            if (!postponeRequestContent) {
                throw new Error('Option "postponeRequest.content" is required');
            }
            if (!postponeRequestTimeout) {
                throw new Error('Option "postponeRequest.timeout" is required');
            }

            const waitingTime = Date.now() + postponeRequestTimeout * 1000;
            // Time before next reconnection attempt, 10 sec
            const retryDelay = 10000;
            let retryCount = 0;

            const checkStatus = () => {
                return $.ajax({
                    type: postponeRequestMethod,
                    url: postponeRequestUrl,
                    contentType: postponeRequestContentType,
                    data: postponeRequestContent,
                    // Max time to wait until response
                    timeout: postponeRequestTimeout * 1000,
                    errorHandlerMessage: false
                }).done((data, textStatus, jqXHR) => {
                    switch (data.status) {
                        case 'success':
                            this._updateSchemaDone(data, textStatus, jqXHR);
                            break;
                        case 'failed':
                            this._updateSchemaFail(jqXHR);
                            break;
                        case 'new':
                        case 'running':
                            if (Date.now() < waitingTime) {
                                retryCount += 1;
                                setTimeout(() => {
                                    checkStatus();
                                }, retryCount * retryDelay);
                            } else {
                                this._updateSchemaFail(jqXHR);
                            }
                            break;
                        default:
                            throw new Error('Unknown operation type');
                    }
                }).fail((jqXHR, textStatus, errorThrown) => {
                    // Considering any server error responses as a server under maintenance
                    if (jqXHR.status >= 500 && Date.now() < waitingTime) {
                        retryCount += 1;
                        setTimeout(() => {
                            checkStatus();
                        }, retryCount * retryDelay);
                    } else {
                        this._updateSchemaFail(jqXHR);
                    }
                });
            };

            checkStatus();
        },

        /**
         *
         * @param {Object} data
         * @param {string} textStatus
         * @param {XMLHttpRequest} jqXHR
         * @private
         */
        _updateSchemaDone(data, textStatus, jqXHR) {
            this.trigger('schema-update:finished', {isSuccessful: true});
            mediator.execute(
                'showFlashMessage',
                'success',
                __('oro.entity_extend.schema_updated'),
                {afterReload: true}
            );
            mediator.execute('showMessage', 'info', __('Please wait for the page to reload...'));
            mediator.execute('showLoading');
            // force reload of the application to make sure 'js/routes' is reloaded
            if (this.options.redirectRoute !== void 0) {
                window.location.href = routing.generate(this.options.redirectRoute);
            } else {
                window.location.reload();
            }
        },

        /**
         * @param {XMLHttpRequest} jqXHR
         * @private
         */
        _updateSchemaFail(jqXHR) {
            this.trigger('schema-update:finished', {isSuccessful: false});
            let message = __('oro.entity_extend.schema_update_failed');
            if (jqXHR?.responseJSON?.message) {
                message += ' ' + jqXHR.responseJSON.message;
            }

            mediator.execute('showFlashMessage', 'error', message.trim());
        }
    });

    return SchemaUpdateActionComponent;
});
