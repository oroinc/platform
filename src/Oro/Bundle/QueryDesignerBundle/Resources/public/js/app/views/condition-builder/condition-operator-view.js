define(function(require) {
    'use strict';

    const _ = require('underscore');
    const template = require('tpl-loader!oroquerydesigner/templates/condition-builder/condition-operator.html');
    const BaseView = require('oroui/js/app/views/base/view');

    require('jquery.select2');

    const ConditionOperatorView = BaseView.extend({
        template: template,
        className: 'operator',
        operations: null,
        selectedOperation: void 0,
        /**
         * Label of select control
         * @type {string}
         */
        label: '',

        optionNames: BaseView.prototype.optionNames.concat([
            'operations', 'selectedOperation', 'label'
        ]),

        /**
         * @inheritdoc
         */
        constructor: function ConditionOperatorView(options) {
            ConditionOperatorView.__super__.constructor.call(this, options);
        },

        events: {
            'change select': '_onChange'
        },

        render: function() {
            ConditionOperatorView.__super__.render.call(this);

            this.$el.attr('data-condition-cid', this.cid);
            this.$('select').inputWidget('create', 'select2');

            return this;
        },

        getTemplateData: function() {
            const data = ConditionOperatorView.__super__.getTemplateData.call(this);

            _.extend(data, _.pick(this, 'label', 'operations', 'selectedOperation'));

            return data;
        },

        _onChange: function(e) {
            this.trigger('change', e.val);
        },

        getValue: function() {
            return this.$('select').inputWidget('val');
        },

        setValue: function(value) {
            this.$('select').inputWidget('val', value);
            this.trigger('change', value);
        }
    });

    return ConditionOperatorView;
});
