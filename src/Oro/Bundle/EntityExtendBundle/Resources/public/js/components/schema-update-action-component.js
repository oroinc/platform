define(function(require) {
    'use strict';

    var ShemaUpdateActionComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var mediator = require('oroui/js/mediator');
    var Modal = require('oroui/js/modal');
    var routing = require('routing');
    var BaseComponent = require('oroui/js/app/components/base/component');

    ShemaUpdateActionComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            delimiter: ';'
        },

        /**
         * @inheritDoc
         */
        constructor: function ShemaUpdateActionComponent() {
            ShemaUpdateActionComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            var el = this.options._sourceElement;
            var self = this;

            $(el).on('click', function(e) {
                var title = __('Schema update confirmation');
                var content = '<p>' + __('Your config changes will be applied to schema.') + '<br/>' +
                    __('It may take few minutes...') + '</p>';
                /** @type oro.Modal */
                var confirmUpdate = new Modal({
                    className: 'modal modal-primary',
                    cancelText: __('Cancel'),
                    okText: __('Yes, Proceed'),
                    title: title,
                    content: content
                });

                function execute() {
                    var url = routing.generate(self.options.route);
                    var progress = $('#progressbar').clone();
                    progress
                        .removeAttr('id')
                        .find('h3').remove()
                        .end()
                        .find('[role="progressbar"]')
                        .attr('aria-valuetext', __('oro.entity_extend.schema_updating'));

                    var modal = new Modal({
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
                        errorHandlerMessage: __('oro.entity_extend.schema_update_failed')
                    }).done(function() {
                        modal.close();
                        mediator.execute(
                            'showFlashMessage',
                            'success',
                            __('oro.entity_extend.schema_updated'),
                            {afterReload: true}
                        );
                        mediator.execute('showMessage', 'info', __('Please wait until page will be reloaded...'));
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
