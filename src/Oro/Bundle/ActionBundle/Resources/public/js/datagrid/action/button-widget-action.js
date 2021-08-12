define(function(require) {
    'use strict';

    const _ = require('underscore');
    const ModelAction = require('oro/datagrid/action/model-action');
    const ButtonManager = require('oroaction/js/button-manager');

    const ButtonWidgetAction = ModelAction.extend({

        /**
         * @property {Object}
         */
        options: {
            operationName: null
        },

        /**
         * @property {ButtonManager}
         */
        buttonManager: null,

        /**
         * @inheritdoc
         */
        constructor: function ButtonWidgetAction(options) {
            ButtonWidgetAction.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            ButtonWidgetAction.__super__.initialize.call(this, options);
            const buttonOptions = _.extend({action: _.pick(this, 'name', 'label')}, this.configuration);
            this.buttonManager = new ButtonManager(buttonOptions);
        },

        /**
         * @inheritdoc
         */
        run: function() {
            this.buttonManager.execute();
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            if (_.isFunction(this.buttonManager.dispose)) {
                this.buttonManager.dispose();
            }

            delete this.buttonManager;

            ButtonWidgetAction.__super__.dispose.call(this);
        }
    });

    return ButtonWidgetAction;
});
