define(function(require) {
    'use strict';

    var ConditionItemView;
    var $ = require('jquery');
    var _ = require('underscore');
    var tools = require('oroui/js/tools');
    var AbstractConditionContainerView =
        require('oroquerydesigner/js/app/views/condition-builder/abstract-condition-container-view');
    var template = require('tpl!oroquerydesigner/templates/condition-builder/condition-item.html');

    ConditionItemView = AbstractConditionContainerView.extend({
        template: template,

        requiredOptions: AbstractConditionContainerView.prototype.requiredOptions.concat(['view', 'viewOptions']),

        /**
         * @inheritDoc
         */
        constructor: function ConditionItemView() {
            ConditionItemView.__super__.constructor.apply(this, arguments);
        },

        render: function() {
            ConditionItemView.__super__.render.call(this);
            var view = this._createCriterionView();
            this.subview('criterion', view);
            this.listenTo(view, {
                close: this.closeCondition,
                change: this.onConditionChange
            });
            this.$content.append(view.$el);
            view.render();
            if (view.deferredRender) {
                this._deferredRender();
                this.$el.addClass('loading');
                view.deferredRender.then(function() {
                    this.$el.removeClass('loading');
                }.bind(this));
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
            var value = this.subview('criterion').getValue();
            var isEmptyValue = _.isEmpty(_.omit(value, 'criteria'));
            if (!tools.isEqualsLoosely(value, this.value)) {
                this.value = !isEmptyValue ? value : {};
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
