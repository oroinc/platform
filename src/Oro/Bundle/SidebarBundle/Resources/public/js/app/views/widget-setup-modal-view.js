define(function(require) {
    'use strict';

    var WidgetSetupModalView;
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var ModalView = require('oroui/js/modal');

    WidgetSetupModalView = ModalView.extend({
        /** @property {String} */
        className: 'modal oro-modal-normal widget-setup',

        /**
         * @inheritDoc
         */
        constructor: function WidgetSetupModalView(options) {
            WidgetSetupModalView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            options.snapshot = options.snapshot || {};

            options.content = new options.contentView({
                className: 'sidebar-widget-setup form-horizontal',
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
