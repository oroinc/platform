define(function(require) {
    'use strict';

    var BaseConditionView;
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');

    BaseConditionView = BaseView.extend({
        tagName: 'li',
        className: 'condition controls',

        /**
         * @type {string}
         */
        criteria: '',

        /**
         * @type {Object}
         */
        value: undefined,

        /**
         * @type {Object}
         */

        validation: undefined,

        /**
         * @type {Backbone.Events}
         */
        eventBus: undefined,

        requiredOptions: ['criteria', 'value', 'validation', 'eventBus'],

        attributes: function() {
            var attrs = {};
            attrs['data-condition-cid'] = this.cid;
            attrs['data-criteria'] = this.criteria;
            return attrs;
        },

        events: {
            'click >.close': 'onCloseClick'
        },

        constructor: function(options) {
            _.extend(this, _.pick(options, this.requiredOptions));
            if (!this.criteria) {
                throw new Error('Missing required option `criteria` of ConditionView');
            }
            if (!this.eventBus) {
                throw new Error('Missing required option `eventBus` of ConditionView');
            }
            BaseConditionView.__super__.constructor.call(this, options);
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            _.each(this.requiredOptions, function(name) {
                delete this[name];
            }, this);
            delete this.$content;

            BaseConditionView.__super__.dispose.call(this);
        },

        getTemplateData: function() {
            var data = BaseConditionView.__super__.getTemplateData.call(this);
            _.extend(data, _.pick(this, 'criteria', 'value', 'validation'));
            return data;
        },

        render: function() {
            BaseConditionView.__super__.render.call(this);
            this.$content = this.$('[data-role="condition-content"]:first');
            return this;
        },

        onCloseClick: function(e) {
            this.closeCondition();
        },

        closeCondition: function() {
            var eventBus = this.eventBus;
            this.dispose();
            eventBus.trigger('condition:closed', this);
        },

        getValue: function() {
            return this.value;
        }
    });

    return BaseConditionView;
});
