define(function(require) {
    'use strict';

    var BaseSetupView;
    var _ = require('underscore');
    var tools = require('oroui/js/tools');
    var BaseView = require('oroui/js/app/views/base/view');
    require('jquery.validate');

    BaseSetupView = BaseView.extend({
        tagName: 'form',

        listen: {
            'ok': 'onOk'
        },

        validation: {},

        /**
         * @inheritDoc
         */
        render: function() {
            BaseSetupView.__super__.render.call(this);
            // bind validation rules and init validator
            _.each(this.validation, function(rules, fieldName) {
                this.$('[name=' + fieldName + ']').data('validation', rules);
            }, this);
            this.$el.validate({
                submitHandler: _.bind(this.onSubmit, this)
            });
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
            var settings = this.fetchFromData();
            if (!tools.isEqualsLoosely(settings, this.model.get('settings'))) {
                this.model.set('settings', settings);
            }
            this.trigger('close');
        }
    });

    return BaseSetupView;
});
