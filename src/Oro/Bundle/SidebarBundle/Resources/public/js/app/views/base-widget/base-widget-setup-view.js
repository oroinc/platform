define(function(require) {
    'use strict';

    const _ = require('underscore');
    const tools = require('oroui/js/tools');
    const BaseView = require('oroui/js/app/views/base/view');
    const mediator = require('oroui/js/mediator');
    require('jquery.validate');

    const BaseSetupView = BaseView.extend({
        tagName: 'form',

        listen: {
            ok: 'onOk'
        },

        validation: {},

        /**
         * @inheritdoc
         */
        constructor: function BaseSetupView(options) {
            BaseSetupView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        render: function() {
            BaseSetupView.__super__.render.call(this);
            // bind validation rules and init validator
            _.each(this.validation, function(rules, fieldName) {
                this.$('[name=' + fieldName + ']').data('validation', rules);
            }, this);
            this.$el.validate({
                submitHandler: this.onSubmit.bind(this)
            });

            if (!_.isMobile()) {
                mediator.execute('layout:adjustLabelsWidth', this.$el);
            }

            return this;
        },

        /**
         * Handles click on Ok button
         */
        onOk: function() {
            this.$el.submit();
        },

        /**
         * Reads data from form
         *
         * @return {Object}
         */
        fetchFromData: function() {
            return tools.unpackFromQueryString(this.$el.serialize());
        },

        /**
         * Handles from submit after validation
         */
        onSubmit: function() {
            const settings = this.fetchFromData();
            if (!tools.isEqualsLoosely(settings, this.model.get('settings'))) {
                this.model.set('settings', settings);
            }
            this.trigger('close');
        }
    });

    return BaseSetupView;
});
