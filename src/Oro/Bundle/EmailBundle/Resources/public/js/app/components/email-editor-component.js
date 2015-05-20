/*global define*/
define(function (require) {
    'use strict';

    var EmailEditorComponent,
        BaseComponent = require('oroui/js/app/components/base/component'),
        $ = require('jquery'),
        select2 = require('jquery.select2'),
        routing = require('routing'),
        __ = require('orotranslation/js/translator'),
        mediator = require('oroui/js/mediator'),
        messenger = require('oroui/js/messenger'),
        ApplyTemplateConfirmation = require('oroemail/js/app/apply-template-confirmation');

    function showField(fieldName, $fieldset) {
        var field = fieldName.toLowerCase(),
            $field = $fieldset.find('[data-ftid=oro_email_email_' + field + ']');
        $field.parents('.control-group.taggable-field').css('display', 'block');
        $field.parents('.controls').find('input.select2-input')
            .unbind('focusout')
            .on('focusout', function(e) {
                setTimeout(function(){
                    if (!$field.val()) {
                        hideField(fieldName, $fieldset);
                    }
                }, 200);
            })
            .focus();

        $fieldset.find('[data-ftid=oro_email_email_to]')
            .parents('.control-group.taggable-field')
            .find('label').html(__('oro.email.to'));
        addForgedAsterisk();

    }

    function hideField(fieldName, $fieldset) {
        var field = fieldName.toLowerCase(),
            $field = $fieldset.find('[data-ftid=oro_email_email_' + field + ']');
        $field.parents('.control-group.taggable-field').css('display', 'none');

        if ($fieldset.find('span.show' + fieldName).length > 0) {
            return;
        }
        $fieldset.find('.cc-bcc-holder').append('<span class="show' + fieldName + '">' + fieldName +  '</span>');
        $fieldset.find('.show' + fieldName).on('click', function(e) {
            e.stopPropagation();
            var target = e.target || window.event.target;
            $(target).remove();
            showField(fieldName, $fieldset);
        });
    }

    function addForgedAsterisk() {
        var label_tab = $('.forged-required').find('label'),
            em_tag = label_tab.find('em');

        if (em_tag.length <= 0) {
            label_tab.append('<em>*</em>')
        } else {
            em_tag.html('*');
        }
    }

    EmailEditorComponent = BaseComponent.extend({
        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function (options) {
            this.options = options;
            this.init();
        },

        init: function () {
            var self = this,
                $fieldset = this.options._sourceElement,
                $subject = this.options._sourceElement.find('[name$="[subject]"]'),
                $body = this.options._sourceElement.find('[name$="[body]"]'),
                $type = this.options._sourceElement.find('[name$="[type]"]'),
                $template = this.options._sourceElement.find('[name$="[template]"]'),
                $bodyFooter = this.options._sourceElement.find('[name$="[bodyFooter]"]'),
                $parentEmailId = this.options._sourceElement.find('[name$="[parentEmailId]"]'),
                $signature = this.options._sourceElement.find('[name$="[signature]"]'),
                $addSignatureButton = this.options._sourceElement.find('#add-signature');

            $addSignatureButton.on('click', function() {
                if ($signature.val()) {
                    var bodyEditorComponent = self.parent.pageComponent('bodyEditor');
                    if (bodyEditorComponent.view.tinymceConnected) {
                        var tinyMCE = bodyEditorComponent.view.tinymceInstance;
                        tinyMCE.execCommand('mceInsertContent', false, $signature.val());
                    } else {
                        $body.focus();
                        var caretPos = $body.getCursorPosition();
                        var body = $body.val();
                        $body.val(body.substring(0, caretPos) + $signature.val().replace(/(<([^>]+)>)/ig, "") + body.substring(caretPos));
                    }
                } else {
                    var url = routing.generate('oro_user_profile_update');
                    if (self.options.isSignatureEditable) {
                        mediator.execute('showFlashMessage', 'info', __('oro.email.thread.no_signature', {url: url}));
                    } else {
                        mediator.execute('showFlashMessage', 'info', __('oro.email.thread.no_signature_no_permission'));
                    }
                }
            });

            var initBody = function(body, appendSignature) {
                appendSignature = typeof appendSignature !== 'undefined' ? appendSignature : true;
                var signature = $signature.val();
                if (self.options.appendSignature && appendSignature) {
                    if (signature && body.indexOf(signature) < 0) {
                        body += '<br/><br/>' + $signature.val();
                    }
                }
                if ($bodyFooter.val()) {
                    body += $bodyFooter.val();
                }

                return body;
            };
            $body.val(initBody($body.val()));

            $template.on('change.' + this.cid, function (e) {
                if (!$(this).val()) {
                    return;
                }

                var confirm = new ApplyTemplateConfirmation({
                    content: __('oro.email.emailtemplate.apply_template_confirmation_content')
                });
                confirm.on('ok', _.bind(function () {
                    var url = routing.generate(
                        'oro_api_get_emailtemplate_compiled',
                        {'id': $(this).val(), 'entityId': self.options.entityId}
                    );

                    mediator.execute('showLoading');

                    $.ajax(url, {
                        success: function (res) {
                            if (!$parentEmailId.val() || !$subject.val()) {
                                $subject.val(res.subject);
                            }

                            var body = initBody(res.body, false);
                            $body.val(body);
                            $type.find('input[value=' + res.type + ']')
                                .prop('checked', true)
                                .trigger('change');
                        },
                        error: function () {
                            messenger.showErrorMessage(__('oro.email.emailtemplate.load_failed'));
                        },
                        dataType: 'json'
                    }).always(function () {
                        mediator.execute('hideLoading');
                    });
                }, this));
                confirm.open();
            });

            $type.on('change.' + this.cid, function() {
                var type = $(this).val(),
                    bodyEditorComponent = self.parent.pageComponent('bodyEditor');

                if (bodyEditorComponent) {
                    bodyEditorComponent.view.setEnabled(type === 'html');
                }
            });

            addForgedAsterisk();
            this.bindFieldEvents($fieldset);
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
        },

        bindFieldEvents: function($fieldset) {
            $('input.taggable-field').each(function(key, elem) {
                var select2Config = {
                    containerCssClass: 'taggable-email',
                    separator: ";",
                    tags: [],
                    tokenSeparators: [";", ","]
                };
                if ($(elem).hasClass('from')) {
                    select2Config.maximumSelectionSize = 1;
                }
                $(elem).select2(select2Config);
            });
            if (!this.options.bcc.length || !this.options.cc.length) {
                $fieldset.find('[id^=oro_email_email_to]').parents('.controls').find('ul.select2-choices').after(
                    '<div class="cc-bcc-holder"/>'
                );
            }
            if (!this.options.cc.length) {
                hideField('Cc', $fieldset);
            }
            if (!this.options.bcc.length) {
                hideField('Bcc', $fieldset);
            }
        }
    });

    (function ($, undefined) {
        $.fn.getCursorPosition = function() {
            var el = $(this).get(0);
            var pos = 0;
            if('selectionStart' in el) {
                pos = el.selectionStart;
            } else if('selection' in document) {
                el.focus();
                var Sel = document.selection.createRange();
                var SelLength = document.selection.createRange().text.length;
                Sel.moveStart('character', -el.value.length);
                pos = Sel.text.length - SelLength;
            }
            return pos;
        }
    })(jQuery);

    return EmailEditorComponent;
});
