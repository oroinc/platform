define(function(require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');

    /**
     * Initialize component
     *
     * @param {Object} options
     * @param {string} options.elementNamePrototype
     */
    return function(options) {
        var self = this;

        this.options = options;

        var processChange = function (e) {
            var url = this.options.url;

            $.ajax({
                url : url,
                method: "POST",
                data: {
                    'type':$(e.target).val()
                },
                success: function(response) {
                    $('.responsive-section.responsive-section-no-blocks').last().find('.row-fluid').html(response.html);
                    mediator.trigger('init');
                }
            });
        };

        $('form[name="oro_user_user_form"]').on(
            'change',
            'select[name="oro_user_user_form[imapAccountTppe][accountType]"]',
            _.bind(processChange, self)
        );
    };
});
