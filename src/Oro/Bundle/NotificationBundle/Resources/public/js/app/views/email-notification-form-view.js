define(function(require) {
    'use strict';

    var EmailNotificationFormView;
    var $ = require('jquery');
    var BaseView = require('oroui/js/app/views/base/view');

    EmailNotificationFormView = BaseView.extend({
        elements: {
            form: 'form[name="emailnotification"]',
            entityName: '[data-ftid="emailnotification_entityName"]',
            additionalEmailAssociations: '[data-ftid="emailnotification_recipientList_additionalEmailAssociations"]'
        },

        events: {
            'change [data-ftid="emailnotification_entityName"]': 'onEntityNameChange'
        },

        /**
         * @inheritDoc
         */
        constructor: function EmailNotificationFormView() {
            EmailNotificationFormView.__super__.constructor.apply(this, arguments);
        },

        onEntityNameChange: function() {
            var that = this;
            var $form = $(this.elements.form);
            var $entityName = $(this.elements.entityName);
            var $additionalEmailAssociations = $(this.elements.additionalEmailAssociations);

            var data = {};
            data[$entityName.attr('name')] = $entityName.val();

            $.ajax({
                url: $form.attr('action'),
                type: $form.attr('method'),
                data: data,
                success: function(html) {
                    $additionalEmailAssociations.replaceWith(
                        $(html).find(that.elements.additionalEmailAssociations)
                    );
                }
            });
        }
    });

    return EmailNotificationFormView;
});
