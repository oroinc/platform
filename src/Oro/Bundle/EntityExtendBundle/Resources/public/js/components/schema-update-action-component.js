define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const mediator = require('oroui/js/mediator');
    const Modal = require('oroui/js/modal');
    const routing = require('routing');
    const BaseComponent = require('oroui/js/app/components/base/component');

    const ShemaUpdateActionComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            delimiter: ';'
        },

        /**
         * @inheritdoc
         */
        constructor: function ShemaUpdateActionComponent(options) {
            ShemaUpdateActionComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            const el = this.options._sourceElement;
            const self = this;

            $(el).on('click', function(e) {
                const title = __('Schema update confirmation');
                const content = '<p>' + __('Your config changes will be applied to schema.') + '<br/>' +
                    __('It may take few minutes...') + '</p>';
                /** @type oro.Modal */
                const confirmUpdate = new Modal({
                    className: 'modal modal-primary',
                    cancelText: __('Cancel'),
                    okText: __('Yes, Proceed'),
                    title: title,
                    content: content
                });

                function execute() {
                    const url = routing.generate(self.options.route);
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
                    modal.$el.find('.modal-body').append(progress);
                    modal.$el.find('.modal-footer').html('');
                    progress.show();

                    $.post({
                        url: url,
                        errorHandlerMessage: function(event, xhr) {
                            let message = __('oro.entity_extend.schema_update_failed');
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                message += ' ' + xhr.responseJSON.message;
                            }
                            return message;
                        }
                    }).done(function() {
                        modal.close();
                        mediator.execute(
                            'showFlashMessage',
                            'success',
                            __('oro.entity_extend.schema_updated'),
                            {afterReload: true}
                        );
                        mediator.execute('showMessage', 'info', __('Please wait for the page to reload...'));
                        mediator.execute('showLoading');
                        // force reload of the application to make sure 'js/routes' is reloaded
                        if (typeof self.options.redirectRoute !== 'undefined') {
                            window.location.href = routing.generate(self.options.redirectRoute);
                        } else {
                            window.location.reload();
                        }
                    }).fail(function() {
                        modal.close();
                    });
                }

                confirmUpdate.on('ok', execute);
                confirmUpdate.open();

                return false;
            });
        }
    });

    return ShemaUpdateActionComponent;
});
