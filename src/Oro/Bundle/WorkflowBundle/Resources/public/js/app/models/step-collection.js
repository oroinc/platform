define(function(require) {
    'use strict';

    const BaseCollection = require('oroui/js/app/models/base/collection');
    const StepModel = require('./step-model');

    const StepCollection = BaseCollection.extend({
        model: StepModel,

        comparator: 'order',

        orderIncrement: 10,

        /**
         * @inheritdoc
         */
        constructor: function StepCollection(...args) {
            StepCollection.__super__.constructor.apply(this, args);
        },

        preinitialize(data) {
            if (data.filter(item => item.order === 0).length > 1) {
                data.forEach((item, index) => {
                    if (item.order === 0) {
                        item.order = this.orderIncrement * index;
                    }
                });
            }
        },

        // Return max order value of the steps collection
        getOrderForNew() {
            return Math.max(...this.map(
                model => (!model.get('_is_start') && model.get('order')) || 0)
            ) + this.orderIncrement;
        }
    });

    return StepCollection;
});
