/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

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
        initialize: function() {
            ButtonWidgetAction.__super__.initialize.apply(this, arguments);

            this.buttonManager = new ButtonManager(this.configuration);
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
