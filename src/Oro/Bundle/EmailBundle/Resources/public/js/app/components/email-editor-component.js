/*global define*/
define(function (require) {
    'use strict';

    var EmailEditorComponent,
        BaseComponent = require('oroui/js/app/components/base/component'),
        $ = require('jquery'),
        routing = require('routing'),
        __ = require('orotranslation/js/translator'),
        messenger = require('oroui/js/messenger'),
        mediator = require('oroui/js/mediator');

    EmailEditorComponent = BaseComponent.extend({
        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function (options) {
            this.options = options;
            this.bindEvents();
        },

        bindEvents: function () {
            var self = this,
                $subject = this.options._sourceElement.find('[name$="[subject]"]'),
                $body = this.options._sourceElement.find('[name$="[body]"]'),
                $type = this.options._sourceElement.find('[name$="[type]"]'),
                $template = this.options._sourceElement.find('[name$="[template]"]');

            $template.on('change.' + this.cid, function (e) {
                if (!$(this).val()) {
                    return;
                }
                var url = routing.generate(
                    'oro_api_get_emailtemplate_compiled',
                    {'id': $(this).val(), 'entityId': self.options.entityId}
                );

                mediator.execute('showLoading');

                $.ajax(url, {
                    success: function (res) {
                        $subject.val(res.subject);
                        $body.val(res.body);
                        $type.find('input[value=' + res.type + ']')
                            .prop('checked', true)
                            .trigger('change');
                    },
                    error: function () {
                        messenger.notificationMessage('error', __('oro.email.emailtemplate.load_failed'));
                    },
                    dataType: 'json'
                }).always(function () {
                    mediator.execute('hideLoading');
                });
            });

            $type.on('change.' + this.cid, function() {
                var type = $(this).val(),
                    bodyEditorComponent = self.parent.pageComponent('bodyEditor');

                if (bodyEditorComponent) {
                    bodyEditorComponent.view.setEnabled(type === 'html');
                }
            });
        },

        unbindEvents: function (e) {
            var $template = this.options._sourceElement.find('[name$="[template]"]'),
                $type = this.options._sourceElement.find('[name$="[type]"]');
            $template.off('change.' + this.cid);
            $type.off('change.' + this.cid);
        },

        dispose: function () {
            if (this.disposed) {
                return;
            }
            this.unbindEvents();
            EmailEditorComponent.__super__.dispose.call(this);
        }
    });

    return EmailEditorComponent;
});
