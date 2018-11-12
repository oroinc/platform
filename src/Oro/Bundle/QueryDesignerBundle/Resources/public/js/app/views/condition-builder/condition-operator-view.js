define(function(require) {
    'use strict';

    var ConditionOperatorView;
    var _ = require('underscore');
    var template = require('tpl!oroquerydesigner/templates/condition-builder/condition-operator.html');
    var BaseView = require('oroui/js/app/views/base/view');

    require('jquery.select2');

    ConditionOperatorView = BaseView.extend({
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
         * @inheritDoc
         */
        constructor: function ConditionOperatorView() {
            ConditionOperatorView.__super__.constructor.apply(this, arguments);
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
            var data = ConditionOperatorView.__super__.getTemplateData.call(this);

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
