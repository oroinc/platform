define(function(require) {
    'use strict';

    var WidgetSetupModalView;
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var Modal = require('oroui/js/modal');

    WidgetSetupModalView = Modal.extend({
        /** @property {String} */
        className: 'modal oro-modal-normal widget-setup',

        initialize: function(options) {
            options.snapshot = options.snapshot || {};

            options.content = new options.contentView({
                className: 'sidebar-widget-setup',
                model: this.model
            });

            options.title = _.result(options.content, 'widgetTitle') || __('oro.sidebar.widget.setup.dialog.title');

            WidgetSetupModalView.__super__.initialize.apply(this, arguments);

            this._bindEventHandlers();
        },

        _bindEventHandlers: function() {
            this.listenTo(this.model, 'change:settings', function() {
                this.model.save();
            });

            this.options.content.once('close', function() {
                this.close();
            }, this);
        }
    });

    return WidgetSetupModalView;
});
