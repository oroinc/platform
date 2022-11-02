define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const tools = require('oroui/js/tools');
    const AbstractConditionContainerView =
        require('oroquerydesigner/js/app/views/condition-builder/abstract-condition-container-view');
    const template = require('tpl-loader!oroquerydesigner/templates/condition-builder/condition-item.html');

    const ConditionItemView = AbstractConditionContainerView.extend({
        template: template,

        requiredOptions: AbstractConditionContainerView.prototype.requiredOptions.concat(['view', 'viewOptions']),

        /**
         * @inheritdoc
         */
        constructor: function ConditionItemView(options) {
            ConditionItemView.__super__.constructor.call(this, options);
        },

        render: function() {
            ConditionItemView.__super__.render.call(this);
            const view = this._createCriterionView();
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
            const CriterionView = this.view;
            const options = _.extend({
                el: this.$content,
                value: this.value
            }, this.viewOptions);
            return new CriterionView(options);
        },

        onConditionChange: function() {
            const value = this.subview('criterion').getValue();
            const isEmptyValue = _.isEmpty(_.omit(value, 'criteria'));
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
