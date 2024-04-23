define(function(require) {
    'use strict';

    const EntityFieldModel = require('oroquerydesigner/js/app/models/entity-field-model');

    const GroupingModel = EntityFieldModel.extend({
        fieldAttribute: 'name',

        defaults: {
            name: null
        },

        groupingDynamicEntityFieldsCollection: null,

        /**
         * @inheritdoc
         */
        constructor: function GroupingModel(...args) {
            GroupingModel.__super__.constructor.apply(this, args);
        },

        initialize(attributes, {groupingDynamicEntityFieldsCollection, ...options} = {}) {
            Object.assign(this, {groupingDynamicEntityFieldsCollection});
            GroupingModel.__super__.initialize.call(this, attributes, options);

            this.listenTo(this, `change:${this.fieldAttribute}`, this.onChangeField);
        },

        resolveFunction(attrs) {
            this.groupingDynamicEntityFieldsCollection.resolveFunction(attrs, this.fieldAttribute);

            Object.keys(attrs).forEach(key => {
                if (key.startsWith('temp-validation-name-')) {
                    delete attrs[key];
                }
            });

            return attrs;
        },

        onChangeField() {
            this.set('func', {});
        },

        toJSON(...args) {
            return this.resolveFunction(GroupingModel.__super__.toJSON.apply(this, args));
        },

        validate(attrs) {
            let error;
            try {
                this.dataProvider.pathToEntityChain(
                    this.groupingDynamicEntityFieldsCollection.extractName(attrs[this.fieldAttribute])
                );
            } catch (e) {
                error = e.message;
            }
            return error;
        }
    });

    return GroupingModel;
});
