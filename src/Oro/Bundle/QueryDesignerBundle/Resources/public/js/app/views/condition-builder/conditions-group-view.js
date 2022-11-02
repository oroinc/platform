define(function(require) {
    'use strict';

    const _ = require('underscore');
    const tools = require('oroui/js/tools');
    const AbstractConditionContainerView =
        require('oroquerydesigner/js/app/views/condition-builder/abstract-condition-container-view');
    const template = require('tpl-loader!oroquerydesigner/templates/condition-builder/conditions-group.html');

    const ConditionsGroupView = AbstractConditionContainerView.extend({
        template: template,

        /**
         * @inheritdoc
         */
        constructor: function ConditionsGroupView(options) {
            ConditionsGroupView.__super__.constructor.call(this, options);
        },

        render: function() {
            ConditionsGroupView.__super__.render.call(this);
            _.each(this.subviews, function(view) {
                if (view.deferredRender && !this.deferredRender) {
                    this._deferredRender();
                }
                this.$content.append(view.$el);
            }, this);
            this._checkValueChange();
            if (this.deferredRender) {
                // in fact deferredRender will be resolved once all subviews have resolved their deferredRender objects
                this._resolveDeferredRender();
            }
            return this;
        },

        _collectValue: function() {
            return _.map(this.$('>.conditions-group>[data-condition-cid]'), function(elem) {
                const cid = elem.getAttribute('data-condition-cid');
                const conditionView = this.subview('condition:' + cid);
                return conditionView && conditionView.getValue();
            }.bind(this));
        },

        _checkValueChange: function() {
            let isEmptyValue;
            const value = this._collectValue();
            if (!tools.isEqualsLoosely(value, this.value)) {
                this.value = value;
                isEmptyValue = _.isEmpty(this.value);
                this.$('>input[name^=condition_item_]').prop('checked', !isEmptyValue);
                this.trigger('change', this, this.value);
            }
        },

        getValue: function() {
            return _.clone(this.value);
        },

        assignConditionSubview: function(conditionView) {
            this.subview('condition:' + conditionView.cid, conditionView);
            this.listenTo(conditionView, {
                change: this._checkValueChange
            });
            this._checkValueChange();
        },

        unassignConditionSubview: function(conditionView) {
            const name = 'condition:' + conditionView.cid;
            const index = _.indexOf(this.subviews, conditionView);
            if (index !== -1) {
                this.subviews.splice(index, 1);
            }
            delete this.subviewsByName[name];
            this.stopListening(conditionView, 'change');
            this._checkValueChange();
        }
    });

    return ConditionsGroupView;
});
