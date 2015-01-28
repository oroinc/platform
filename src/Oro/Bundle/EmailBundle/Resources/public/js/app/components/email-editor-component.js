/*global define*/
define(function (require) {
    'use strict';

    var EmailEditorComponent,
        BaseComponent = require('oroui/js/app/components/base/component'),
        $ = require('jquery'),
        select2 = require('jquery.select2'),
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
            $('input.taggable-field').each(function(key, elem){
                $(elem).select2({
                    containerCssClass: 'taggable-email',
                    separator: ";",
                    tags: [],
                    tokenSeparators: [";", ",", " "]
                });
            });
            if (!this.options.bcc.length || !this.options.cc.length) {
                $('#oro_email_email_to').parents('.controls').find('ul.select2-choices').after(
                    '<div id="cc-bcc-holder"/>'
                );
            }
            if (!this.options.bcc.length) {
                $('#oro_email_email_bcc').parents('.control-group.taggable-field').css('display', 'none');
                $('#cc-bcc-holder').append('<span id="showBcc">Bcc</span>');
                $('#showBcc').on('click', function(e){
                    e.stopPropagation();
                    var target = e.target || window.event.target;
                    $(target).remove();
                    $('#oro_email_email_bcc').parents('.control-group.taggable-field').css('display', 'block');
                    $('#oro_email_email_to').parents('.control-group.taggable-field').find('label').html(
                        __("To") + '<em>*</em>'
                    );
                });
            }
            if (!this.options.cc.length) {
                $('#oro_email_email_cc').parents('.control-group.taggable-field').css('display', 'none');
                $('#cc-bcc-holder').append('<span id="showCc">Cc</span>');
                $('#showCc').on('click', function(e){
                    e.stopPropagation();
                    var target = e.target || window.event.target;
                    $(target).remove();
                    $('#oro_email_email_cc').parents('.control-group.taggable-field').css('display', 'block');
                    $('#oro_email_email_to').parents('.control-group.taggable-field').find('label').html(
                        __("To") + '<em>*</em>'
                    );
                });
            }
            if (!this.options.to.length || !this.options.to[0]) {
                $('#oro_email_email_to').parents('.control-group.taggable-field').find('label').html(
                    __("Recipients") + '<em>*</em>'
                );
            }
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
