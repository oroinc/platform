define(function(require) {
    'use strict';

    var _ = require('underscore');
    var ModelAction = require('oro/datagrid/action/model-action');
    var ButtonManager = require('oroaction/js/button-manager');

    var ButtonWidgetAction = ModelAction.extend({

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
         * @inheritDoc
         */
        constructor: function ButtonWidgetAction() {
            ButtonWidgetAction.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function() {
            ButtonWidgetAction.__super__.initialize.apply(this, arguments);
            var buttonOptions = _.extend({action: _.pick(this, 'name', 'label')}, this.configuration);
            this.buttonManager = new ButtonManager(buttonOptions);
        },

        /**
         * @inheritDoc
         */
        run: function() {
            this.buttonManager.execute();
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            delete this.buttonManager;

            ButtonWidgetAction.__super__.dispose.call(this);
        }
    });

    return ButtonWidgetAction;
});
