define(function(require) {
    'use strict';

    var ConditionItemView;
    var $ = require('jquery');
    var _ = require('underscore');
    var tools = require('oroui/js/tools');
    var BaseConditionView = require('oroquerydesigner/js/app/views/condition-builder/base-condition-view');
    var template = require('tpl!oroquerydesigner/templates/condition-builder/condition-item.html');

    ConditionItemView = BaseConditionView.extend({
        template: template,

        requiredOptions: BaseConditionView.prototype.requiredOptions.concat(['view', 'viewOptions']),

        render: function() {
            ConditionItemView.__super__.render.call(this);
            var view = this._createCriterionView();
            this.subview('criterion', view);
            this.listenTo(view, {
                'close': this.closeCondition,
                'change': this.onConditionChange
            });
            this.$content.append(view.$el);
            view.render();
            if (view.deferredRender) {
                this._deferredRender();
                // in fact deferredRender will be resolved once all subviews have resolved their deferredRender objects
                this._resolveDeferredRender();
            }
            return this;
        },

        _createCriterionView: function() {
            var CriterionView = this.view;
            var options = _.extend({
                el: this.$content,
                value: this.value
            }, this.viewOptions);
            return new CriterionView(options);
        },

        onConditionChange: function() {
            var isEmptyValue;
            var value = this.subview('criterion').getValue();
            if (!tools.isEqualsLoosely(value, this.value)) {
                this.value = value;
                isEmptyValue = _.isEmpty(this.value);
                this.$('>input[name^=condition_item_]').prop('checked', !isEmptyValue);
                if (!isEmptyValue && this.criteria !== 'condition-item') {
                    this.value.criteria = this.criteria;
                }
                this.trigger('change', this, this.value);
            }
        },

        getValue: function() {
            return $.extend(true, {}, this.value);
        }
    });

    return ConditionItemView;
});
